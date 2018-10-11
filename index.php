<?php
// header("Content-type: html/text; charset=utf-8");
include_once('vendor/autoload.php');
include_once('variables.php');
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
header('Content-Type: text/html; charset=utf-8');
$tags = array('</p>','<br />','<br>','<hr />','<hr>','</h1>','</h2>','</h3>','</h4>','</h5>','</h6>', '</div>');
$blacklist = array('UNIVERSITY', 'UNITED STATES', "UNIVERSITEIT", "LIBRARY");
$error = '';
$result = '';
if (isset($_POST['url']) && $_POST['url'] != '' ){
	$url = trim($_POST['url']);
	if (!filter_var($url, FILTER_VALIDATE_URL)){
		$url = 'https://'.$url;
	}
	if (!filter_var($url, FILTER_VALIDATE_URL)){
		$error = 'That doesn\'t look like a url';
	}
	elseif(preg_match("/(?:https?:\/\/)?(?:www\.)?flickr\.com\/photos\/[^\/]+\/(albums|sets)\/(\d+)/", $url, $matches )){
		
		$params = array(
			'api_key'		=> $flickrAPIkey,
			'method'		=> 'flickr.photosets.getInfo',
			'photoset_id'	=> end($matches),
			'format'		=> 'php_serial',
		);
		$response = unserialize(file_get_contents('https://api.flickr.com/services/rest/?'.http_build_query($params)));
		if (isset($response['photoset'])){
			$result = array();
			$tags   = array();
			$result[] = $response['photoset']['title']['_content'];
			$result[] = $response['photoset']['description']['_content'];
	
			$params['method'] = 'flickr.photosets.getPhotos';
			$params['extras'] = 'description, tags';
	
			$response = unserialize(file_get_contents('https://api.flickr.com/services/rest/?'.http_build_query($params)));

			
			$params['method'] = 'flickr.tags.getListPhoto';
			unset($params['extras']);
			unset($params['photoset_id']);

			foreach ($response['photoset']['photo'] as $photo) {
				$result[] = $photo['title'];
				$result[] = $photo['description']['_content'];
				$newTags  = explode(" ", $photo['tags']); 
				$newTags  = array_diff($newTags, $tags);
				$tagLengths = array();
				foreach ($newTags as $tag) {
					$tagLengths[] = strlen($tag);
				}
				rsort($tagLengths);
				if (count($newTags) > 0 && $tagLengths[0] >= 8 ){
					$params['photo_id'] = $photo['id'];
					$photoTags = unserialize(file_get_contents('https://api.flickr.com/services/rest/?'.http_build_query($params)))["photo"]["tags"]['tag'];
					foreach ($photoTags as $tag) {
						if (strpos($tag['raw'], " ")){
							$result[] = $tag['raw'];
						}
						$tags[] = $tag['_content'];
					}
				}
			}
			$result = array_unique($result);
			$result = trim(implode("\n", $result));
		}
		else{
			$error .= 'photoset ID not recognized<br/>';
		}
	}
	elseif (is_404($url) && $result == '') {
		$error .= '404 page not found';
	}
	else{
		$readability = new Readability(new Configuration());
		$opts = array('http'=>array('header' => "User-Agent:Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0"));
		$context = stream_context_create($opts);
		$html = file_get_contents($url, false, $context);

		try {
		    $readability->parse($html);
		    $result = $readability->getContent();
		    
		} catch (ParseException $e) {
			//might as well use the old method if Readability fails:
			$ch = curl_init();
			$request_headers = array();
			$request_headers[] = 'x-api-key: ' . $postlightAPI;
			$request_headers[] = "Content-Type: application/json; charset=utf-8";
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_URL, 'https://mercury.postlight.com/parser?url=' . $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
			$output = json_decode(curl_exec($ch));
			curl_close($ch);

		    echo sprintf('Error processing text: %s', $e->getMessage());
		    $result = $output->content;
		}
		
		if ($result == ''){
			$result = $html;
		}
	}
	if ($result != ''){

		$result = str_replace($tags,"\n",$result);
		$result = strip_tags($result);
		$result = html_entity_decode($result);
		// $result = mb_convert_encoding( $result, 'UTF-8');

		preg_match_all("/[A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòùìâêôûßîãñõA-Z]+(-[A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëößüïàèòùìâêôûîãñõA-Z]+)?( [A-Z]\.?)?([ \-]([A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòùìâêôûîãñõA-Z]+))?( (van|der?|van der?|el|'t|tot|ter|op|tot|uij?t|bij|aan|voor|von|Mac|Ó))? (O')?[A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëßöüïàèòùìâêôûîãñõA-Z]+([ \-]([A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòßùìâêôûîãñõA-Z]+))?/", $result, $matches);
		
		if ($matches){
			$names = $matches[0];
			$names = array_unique($names);
			
			foreach ($names as $key => $name) {
				foreach ($blacklist as $item) {
					if(strpos(strtoupper($name), $item)){
						unset($names[$key]);
					}
				}
			}
		}

	
	}
}
elseif (isset($_POST['names']) && $_POST['names'] != ''){
	$names = $_POST['names'];
	$names = preg_replace('/VM\d+:\d/', '', $names);
	// $names = mb_convert_encoding($names, 'utf-8');
	$names = explode("\n", $names);
	$names = array_unique($names);
	if (($key = array_search("", $names)) !== false) {
	    unset($names[$key]);
	}
	$query = http_build_query(['name' => $names],null, ini_get( 'arg_separator.output' ));
	$query = preg_replace('/\%5B\d+\%5D/', '[]', $query);
	$query = str_replace('%0D', '', $query);
	header( "Location: sparql.php?".$query."" );
}
elseif(isset($_POST['names']) && $_POST['names'] == ''|| isset($_POST['url']) && $_POST['url'] ==''){
 $error = 'no input?';
}

function is_404($url) {
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($handle, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0");
    /* Get the HTML or whatever is linked in $url. */
    $response = curl_exec($handle);

    /* Check for 404 (file not found). */
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);

    /* If the document has loaded successfully without any redirection or error */
    if ($httpCode >= 200 && $httpCode < 300 ) {
        return false;
    } else {
        return true;
    }
}
?>
<html>
<head>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<link rel="stylesheet" href="style.css">
<script type="text/javascript" src="script.js"></script>
<title>Orator Matcher</title>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta charset="UTF-8">
</head>
<body>
	<form class="form-wrapper"  method="POST"  target='_self' id="form"	>
	<div style='color:red; clear:both;'><?php echo $error; ?></div>
   <div> <input type="text" id="url" placeholder="URL to the list of conference speakers" name='url' >


<div id='showTex' style='clear:both; '>OR a list of names:</div>
<textarea rows='10'  cols='50' name='names' form='form' class='<?php echo (isset($names)? 'retracted' :'expand'); ?>'></textarea>


    <input type="submit" class='button' value="go" id="submit"></div> 	
</form>
<div>

<?php
if (isset($names)){
	echo '<form class="form-wrapper" action="sparql.php" >';
	echo "<ul id='names'>";
	foreach ($names as $key => $value) {

		echo "<li><span class='remove button'>x</span><span class='word'>".str_replace(" ", "</span> <span class='word'>", $value)."</span><input name='name[]' type='hidden' class='hiddenNames' value='".$value."'</li>";
	}
	echo "</ul>";
	echo "<input type='submit' class='button' id='go' value='go'/>";	
	echo "</form>";
}

?>

</div>
</body>

</html>