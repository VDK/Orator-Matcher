<?php

$query = file_get_contents('https://www.wikidata.org/w/api.php?action=query&list=search&utf10=true&format=json&srlimit=6&srsearch='.urlencode("'".$_GET['srsearch']."'"));
$query = json_decode($query, true);
$query = $query['query'];

if ($query['searchinfo']['totalhits'] >= 1){
	$results = array();
	foreach ($query['search'] as $key => $value) {
		$query = "SELECT ?itemLabel ?image ?dateOfDeath ?occupationLabel ?countryLabel ?sitelinks ?isSportsPerson WHERE {
		  {
			  {
			    BIND(wd:".$value['title']." AS ?item).
	     		?item wdt:P31 wd:Q5
			  }
			  OPTIONAL { ?item wdt:P18  ?image. }
			  OPTIONAL { ?item wdt:P570 ?dateOfDeath. }
			  OPTIONAL { ?item wdt:P106 ?occupation .}
			  OPTIONAL { ?item wdt:P27  ?country .}
			  OPTIONAL {
			    ?item wdt:P31 wd:Q5.
			    { ?item wdt:P641 ?sport. } UNION
			    { ?item wdt:P1532 ?countryForSport. } UNION
			    { ?item wdt:P106/wdt:P279* wd:Q2066131. }
			    BIND(true AS ?isSportsPerson_)
			  }
			  BIND(COALESCE(?isSportsPerson_, false) AS ?isSportsPerson)
			  ?item wikibase:sitelinks ?sitelinks 

			  service wikibase:label { bd:serviceParam wikibase:language \"[AUTO_LANGUAGE],en,de\". }            
			}.filter(NOT EXISTS { ?item wdt:P570  [] } 
                  || ?dateOfDeath >= \"".(date("Y")-2)."-01-01T00:00:00Z\"^^xsd:dateTime )
		}";

		$url = 'https://query.wikidata.org/bigdata/namespace/wdq/sparql?' . http_build_query(
    		[ 'query' => $query, 'format' => 'json' ], null, ini_get( 'arg_separator.output' ), PHP_QUERY_RFC3986 );
		$json = file_get_contents( $url );
		if ( $json === false ) { 
			#throw ... 
		}			
		$data = json_decode( $json, true );
		$occupations = array();
		foreach ($data['results']['bindings'] as $key => $item) {
			if(isset($item['occupationLabel'])){
				$occupations[] = $item['occupationLabel']['value'];
			}
		}
		$occupations = array_unique($occupations);
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
			if (isset($item['dateOfDeath'])){
				$result['dateOfDeath'] = $item['dateOfDeath']['value'];
			}
			if (isset($item['occupationLabel'])){
				$result['occupation'] = implode("/", $occupations);
			}
			if (isset($item['countryLabel'])){
				$result['country'] = $item['countryLabel']['value'];
			}

			$results[] = $result;
		}
	}
	if (isset($results)){
		echo json_encode($results);
	}
	else{
		echo json_encode('nee');
	}
}
else{
	echo json_encode('nee');
}

?>
