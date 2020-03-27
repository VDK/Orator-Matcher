<?php
$srsearch = trim($_GET['srsearch']);
$query = file_get_contents('https://www.wikidata.org/w/api.php?action=query&list=search&utf8=true&format=json&srlimit=15&srsearch=haswbstatement:"P31=Q5"%20'.urlencode("'".$srsearch."'"));
$query = json_decode($query, true);
$query = $query['query'];

if ($query['searchinfo']['totalhits'] >= 1){
	$results = array();
	foreach ($query['search'] as $key => $value) {
		$query = "
		SELECT ?itemLabel ?image ?dateOfBirth ?dateOfDeath  ?occupationLabel ?countryLabel ?sitelinks ?isSportsPerson ?cattitle ?subcat WHERE {
		  {
			  {
			    BIND(wd:".$value['title']." AS ?item).
	     		?item wdt:P31 wd:Q5
			  }
			  OPTIONAL { ?item wdt:P18  ?image. }
			  OPTIONAL { ?item wdt:P569 ?dateOfBirth.}
			  OPTIONAL { ?item wdt:P570 ?dateOfDeath. }
			  OPTIONAL { ?item wdt:P106 ?occupation .}
			  OPTIONAL { ?item wdt:P27  ?country .}
			  OPTIONAL { ?link schema:about ?item; schema:isPartOf <https://commons.wikimedia.org/>; schema:name ?cattitle1 . }
              OPTIONAL { ?item wdt:P373 ?_cattitle2 																		
                       BIND(CONCAT(\"Category:\", ?_cattitle2) as ?cattitle2)}
              BIND(COALESCE( ?cattitle2, ?cattitle1) as ?cattitle)
              OPTIONAL{
                 SERVICE wikibase:mwapi {
					bd:serviceParam wikibase:api \"Generator\" .
			    	bd:serviceParam wikibase:endpoint \"commons.wikimedia.org\" .
			    	bd:serviceParam mwapi:gcmtitle ?cattitle .
			    	bd:serviceParam mwapi:generator \"categorymembers\" .
			    	bd:serviceParam mwapi:gcmlimit \"max\" .
			    	bd:serviceParam mwapi:gcmtype \"subcat\" .
					# out
					?subcat wikibase:apiOutput mwapi:title  .
					?ns wikibase:apiOutput \"@ns\" .
					?witem wikibase:apiOutputItem mwapi:item .
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
			  
			  ?item wikibase:sitelinks ?sitelinks 

			  service wikibase:label { bd:serviceParam wikibase:language \"[AUTO_LANGUAGE],en,de\". }            
			}.filter(NOT EXISTS { ?item wdt:P570  [] } 
                  || ?dateOfDeath >= \"".(date("Y")-5)."-01-01T00:00:00Z\"^^xsd:dateTime )
		 	 .filter(NOT EXISTS { ?item wdt:P569  [] } 
                  || ?dateOfBirth >=\"1910-01-01T00:00:00Z\"^^xsd:dateTime )
		}";

			
		$data = sparqlQuery ($query );
		$occupations = array();
		$categories  = array();
		foreach ($data['results']['bindings'] as $key => $item) {
			if(isset($item['occupationLabel'])){
				$occupations[] = $item['occupationLabel']['value'];
			}
			if(isset($item['subcat'])){
				$categories[] = str_replace("Category:", "", $item['subcat']['value']);
			}
		}
		$occupations = array_unique($occupations);
		$categories = array_unique($categories);
		if (isset($data['results']['bindings'][0]) ) {
			$result = array();
			$item = $data['results']['bindings'][0];
			$result['qitem'] = $value['title'];
			$result['itemLabel'] = $item['itemLabel']['value'];
			$result['sitelinks'] = $item['sitelinks']['value'];
			$result['isSportsPerson'] = $item['isSportsPerson']['value'];
			if (isset($item['image'])){
				$result['image'] = $item['image']['value']."?width=100";
			}
			if (isset($item['dateOfBirth'])){
				$result['dateOfBirth'] = $item['dateOfBirth']['value'];
			}
			if (isset($item['dateOfDeath'])){
				$result['dateOfDeath'] = $item['dateOfDeath']['value'];
			}
			if (isset($item['occupationLabel'])){
				$result['occupation'] = implode("/&shy;", $occupations);
			}
			if (isset($item['countryLabel'])){
				$result['country'] = $item['countryLabel']['value'];
			}
			if (isset($item['cattitle'])){
				$categories[] = str_replace("Category:", "", $item['cattitle']['value']);
				$result['categories'] = "-incategory:\"".implode("\" -incategory:\"", $categories)."\"";
			}

			$results[] = $result;
		}
	}
	if (isset($results) && count($results) > 0){
		echo json_encode($results);
	}
	else{
		echo json_encode('nee');
	}
}
else{
	echo json_encode('nee');
}
function sparqlQuery(string $sparqlQuery): array
    {

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    "Accept: application/sparql-results+json\r\n".
                    "Accept-language: en\r\n" .
              		"Cookie: foo=bar\r\n" .  // check function.stream-context-create on php.net
              		"User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n"
                ],
            ],
        ];
        $context = stream_context_create($opts);

        $url = 'https://query.wikidata.org/sparql?query=' . urlencode($sparqlQuery);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
?>
