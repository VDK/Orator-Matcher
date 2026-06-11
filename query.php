<?php
header('Content-Type: application/json; charset=utf-8');

main();

function main()
{
	$searchTerm = isset($_GET['srsearch']) ? trim($_GET['srsearch']) : '';
	$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
	$limit = 15;
	if ($searchTerm === '') {
		jsonResponse(searchResponse(array(), $offset, $limit, 0));
	}

	$searchResults = wikidataSearch($searchTerm, $offset, $limit);
	if (!isset($searchResults['searchinfo']['totalhits']) || $searchResults['searchinfo']['totalhits'] < 1) {
		jsonResponse(searchResponse(array(), $offset, $limit, 0));
	}

	$matches = array();
	foreach ($searchResults['search'] as $searchResult) {
		if (!isset($searchResult['title']) || !preg_match('/^Q[0-9]+$/', $searchResult['title'])) {
			continue;
		}

		$data = sparqlQuery(buildPersonQuery($searchResult['title']));
		$result = normalizePersonResult($data, $searchResult['title']);
		if ($result !== null) {
			$matches[] = $result;
		}
	}

	jsonResponse(searchResponse($matches, $offset, $limit, intval($searchResults['searchinfo']['totalhits'])));
}

function searchResponse($matches, $offset, $limit, $totalHits)
{
	return array(
		'matches' => $matches,
		'offset' => $offset,
		'limit' => $limit,
		'totalHits' => $totalHits,
		'hasMore' => ($offset + $limit) < $totalHits,
	);
}

function wikidataSearch($searchTerm, $offset, $limit)
{
	$url = 'https://www.wikidata.org/w/api.php?' . http_build_query(array(
		'action' => 'query',
		'list' => 'search',
		'utf8' => 'true',
		'format' => 'json',
		'srlimit' => $limit,
		'sroffset' => $offset,
		'srsearch' => 'haswbstatement:"P31=Q5" ' . $searchTerm,
	));

	$response = httpGetJson($url, array(
		'User-Agent: Orator-Matcher/2.0 (https://veradekok.nl/contact)',
	));

	return isset($response['query']) ? $response['query'] : array();
}

function buildPersonQuery($qid)
{
	return "
		SELECT ?itemLabel ?itemDescription ?image ?dateOfBirth ?dateOfDeath ?dateOfBaptism ?floruit ?workPeriodStart ?workPeriodEnd ?occupation ?occupationLabel ?country ?countryLabel ?sitelinks ?isSportsPerson ?hasOrcid ?hasPeerageId ?cattitle ?subcat WHERE {
		  {
			  {
			    BIND(wd:".$qid." AS ?item).
	     		?item wdt:P31 wd:Q5
			  }
			  OPTIONAL { ?item wdt:P18 ?image. }
			  OPTIONAL { ?item wdt:P569 ?dateOfBirth. }
			  OPTIONAL { ?item wdt:P570 ?dateOfDeath. }
			  OPTIONAL { ?item wdt:P1636 ?dateOfBaptism. }
			  OPTIONAL { ?item wdt:P1317 ?floruit. }
			  OPTIONAL { ?item wdt:P2031 ?workPeriodStart. }
			  OPTIONAL { ?item wdt:P2032 ?workPeriodEnd. }
			  OPTIONAL { ?item wdt:P106 ?occupation. }
			  OPTIONAL { ?item wdt:P27 ?country. }
			  OPTIONAL { ?link schema:about ?item; schema:isPartOf <https://commons.wikimedia.org/>; schema:name ?cattitle1. }
              OPTIONAL { ?item wdt:P373 ?_cattitle2
                       BIND(CONCAT(\"Category:\", ?_cattitle2) as ?cattitle2)}
              BIND(COALESCE(?cattitle2, ?cattitle1) as ?cattitle)
              OPTIONAL{
                 SERVICE wikibase:mwapi {
					bd:serviceParam wikibase:api \"Generator\".
			    	bd:serviceParam wikibase:endpoint \"commons.wikimedia.org\".
			    	bd:serviceParam mwapi:gcmtitle ?cattitle.
			    	bd:serviceParam mwapi:generator \"categorymembers\".
			    	bd:serviceParam mwapi:gcmlimit \"max\".
			    	bd:serviceParam mwapi:gcmtype \"subcat\".
					?subcat wikibase:apiOutput mwapi:title.
					?ns wikibase:apiOutput \"@ns\".
					?witem wikibase:apiOutputItem mwapi:item.
                 }
              }
			  OPTIONAL {
			    ?item wdt:P31 wd:Q5.
			    { ?item wdt:P641 ?sport. } UNION
			    { ?item wdt:P1532 ?countryForSport. } UNION
			    { ?item wdt:P106/wdt:P279* wd:Q2066131. }
			    BIND(true AS ?isSportsPerson_)
			  }
			  BIND(COALESCE(?isSportsPerson_, false) AS ?isSportsPerson).
			  OPTIONAL {
			    ?item wdt:P496 ?orcid.
			    BIND(true AS ?hasOrcid_)
			  }
			  BIND(COALESCE(?hasOrcid_, false) AS ?hasOrcid).
			  OPTIONAL {
			    ?item wdt:P4638 ?peerageId.
			    BIND(true AS ?hasPeerageId_)
			  }
			  BIND(COALESCE(?hasPeerageId_, false) AS ?hasPeerageId).
			  ?item wikibase:sitelinks ?sitelinks

			  SERVICE wikibase:label { bd:serviceParam wikibase:language \"[AUTO_LANGUAGE],en,nl,de,fr,mul\". }
			}
		}";
}

function sparqlQuery($sparqlQuery)
{
	$url = 'https://query.wikidata.org/sparql?query=' . urlencode($sparqlQuery);
	return httpGetJson($url, array(
		'Accept: application/sparql-results+json',
		'Accept-language: en',
		'User-Agent: Orator-Matcher/2.0 (https://veradekok.nl/contact)',
	));
}

function normalizePersonResult($data, $qid)
{
	if (!isset($data['results']['bindings'][0])) {
		return null;
	}

	$occupations = array();
	$countries = array();
	$categories = array();
	foreach ($data['results']['bindings'] as $item) {
		if (isset($item['occupation']['value']) && isset($item['occupationLabel']['value'])) {
			$occupations[$item['occupation']['value']] = array(
				'id' => qidFromUri($item['occupation']['value']),
				'label' => $item['occupationLabel']['value'],
			);
		}
		if (isset($item['country']['value']) && isset($item['countryLabel']['value'])) {
			$countries[$item['country']['value']] = array(
				'id' => qidFromUri($item['country']['value']),
				'label' => $item['countryLabel']['value'],
			);
		}
		if (isset($item['subcat']['value'])) {
			$categories[] = str_replace('Category:', '', $item['subcat']['value']);
		}
	}

	$categories = array_unique($categories);
	$item = $data['results']['bindings'][0];
	$result = array(
		'qitem' => $qid,
		'itemLabel' => valueOrEmpty($item, 'itemLabel'),
		'description' => valueOrEmpty($item, 'itemDescription'),
		'descriptionLanguage' => languageOrEmpty($item, 'itemDescription'),
		'sitelinks' => intval(valueOrEmpty($item, 'sitelinks')),
		'isSportsPerson' => valueOrEmpty($item, 'isSportsPerson') === 'true',
		'hasOrcid' => valueOrEmpty($item, 'hasOrcid') === 'true',
		'hasPeerageId' => valueOrEmpty($item, 'hasPeerageId') === 'true',
		'occupations' => array_values($occupations),
		'countries' => array_values($countries),
	);

	if (isset($item['image']['value'])) {
		$result['image'] = $item['image']['value'] . '?width=120';
	}
	if (isset($item['dateOfBirth']['value'])) {
		$result['dateOfBirth'] = $item['dateOfBirth']['value'];
	}
	if (isset($item['dateOfDeath']['value'])) {
		$result['dateOfDeath'] = $item['dateOfDeath']['value'];
	}
	if (isset($item['dateOfBaptism']['value'])) {
		$result['dateOfBaptism'] = $item['dateOfBaptism']['value'];
	}
	if (isset($item['floruit']['value'])) {
		$result['floruit'] = $item['floruit']['value'];
	}
	if (isset($item['workPeriodStart']['value'])) {
		$result['workPeriodStart'] = $item['workPeriodStart']['value'];
	}
	if (isset($item['workPeriodEnd']['value'])) {
		$result['workPeriodEnd'] = $item['workPeriodEnd']['value'];
	}
	if (isset($item['cattitle']['value'])) {
		$categories[] = str_replace('Category:', '', $item['cattitle']['value']);
		$categories = array_unique($categories);
	}
	if (count($categories)) {
		$result['categories'] = '-incategory:"' . implode('" -incategory:"', $categories) . '"';
	}

	return $result;
}

function qidFromUri($uri)
{
	$parts = explode('/', $uri);
	return end($parts);
}

function valueOrEmpty($item, $key)
{
	return isset($item[$key]['value']) ? $item[$key]['value'] : '';
}

function languageOrEmpty($item, $key)
{
	return isset($item[$key]['xml:lang']) ? $item[$key]['xml:lang'] : '';
}

function httpGetJson($url, $headers)
{
	$context = stream_context_create(array(
		'http' => array(
			'method' => 'GET',
			'header' => implode("\r\n", $headers) . "\r\n",
		),
	));

	$response = file_get_contents($url, false, $context);
	if ($response === false) {
		return array();
	}

	$data = json_decode($response, true);
	return is_array($data) ? $data : array();
}

function jsonResponse($payload)
{
	echo json_encode($payload);
	exit;
}
?>
