<?php
// header("Content-type: html/text; charset=utf-8");
include_once('vendor/autoload.php');
include_once('variables.php');
include_once('slider_vars.php');
$nameFilters = include('name_filters.php');
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
$error 	= '';
$result = '';

function handleFlickr($set_id){
		$result = "";
		$error = "";
		$params = array(
			'api_key'		=> FLICKRAPIKEY,
			'method'		=> 'flickr.photosets.getInfo',
			'photoset_id'	=> $set_id,
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

			//set up params for raw tag request
			$params['method'] = 'flickr.tags.getListPhoto';
			unset($params['extras']);
			unset($params['photoset_id']);

			foreach ($response['photoset']['photo'] as $photo) {
				$result[] = $photo['title'];
				$result[] = $photo['description']['_content'];
				//determin if it's worth it to do an API request for this photo
				$newTags  = array_diff(explode(" ", $photo['tags']), $tags);
				$tagLengths = array();
				foreach ($newTags as $tag) {
					$tagLengths[] = strlen($tag);
				}
				//if there are new tags and the lenght of the new tag is longer than 8 characters 
				//(not interested in really short tags like #FYI)
				if (count($newTags) > 0 && max($tagLengths) >= 8 ){
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
			$error =  'Photoset ID not recognized<br/>';
		}
		return array("error" => $error, "result" => $result);
}

if (isset($_POST['names']) && $_POST['names'] != ''){
	$names = trim($_POST['names']);
	$names = preg_replace('/VM\d+:\d/', '', $names); //sneaky bit to remove column counts from Chrome Console output
	$result = $names;
	if(isset($_POST['analyse']) && $_POST['analyse'] == 'false'){
		$names = normalizeSubmittedNames($names);
		if (count($names)){
			foreach ($names as $key => $name) {
				$names[$key] = urlencode($name);
			}
			header('Location: sparql.php?names='. implode("|",$names).'&y1='.$y1.'&y2='.$y2);
			exit;
		}
	}
	
}
elseif (isset($_POST['url']) && $_POST['url'] != '' ){
	$url = trim($_POST['url']);
	if (!filter_var($url, FILTER_VALIDATE_URL)){
		$url = 'https://'.$url;
	}
	if (!filter_var($url, FILTER_VALIDATE_URL)){
		$error .=  'That doesn\'t look like a url';
	}
	//retrieve information about Flickr album/set
	elseif(preg_match("/(?:https?:\/\/)?(?:www\.)?flickr\.com\/photos\/[^\/]+\/(?:albums|sets)\/(\d+)/", $url, $matches )){
		$flickr = handleFlickr(end($matches));
		$result = $flickr['result'];
		$error .= $flickr['error'];
		
	}
	elseif (is_404($url)) {
		$error .=  '404 page not found<br/>';
	}
	else{
		$result = extractTextFromUrl($url);
	}
}
if ($result != ''){
	$names = extractCandidateNames($result, $nameFilters);
	if(count($names) == 0){
		$error .=  'no content found<br/>';
	}
}

elseif(isset($_POST['names']) && $_POST['names'] == ''|| isset($_POST['url']) && $_POST['url'] ==''){
 $error .=  'no input?';
}

function extractTextFromUrl($url) {
	$readability = new Readability(new Configuration());
	$opts = array('http'=>array('header' => "User-Agent:Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0"));
	$context = stream_context_create($opts);
	$html = file_get_contents($url, false, $context);
	$result = '';

	try {
	    $readability->parse($html);
	    $result = $readability->getContent();
	} catch (ParseException $e) {
		$result = extractTextWithPostlight($url);
	}
	
	return $result == '' ? $html : $result;
}

function extractTextWithPostlight($url) {
	$ch = curl_init();
	$request_headers = array();
	$request_headers[] = 'x-api-key: ' . POSTLIGHTAPI;
	$request_headers[] = "Content-Type: application/json; charset=utf-8";
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_URL, 'https://mercury.postlight.com/parser?url=' . $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
	$output = json_decode(curl_exec($ch));
	curl_close($ch);

	return isset($output->content) ? $output->content : '';
}

function extractCandidateNames($text, $filters) {
	$text = prepareTextForNameExtraction($text, $filters);

	preg_match_all("/[\p{Lu}][\p{L}'\x{2019}\-]*[\p{L}](( ([\p{Lu}][\p{L}'\x{2019}\-]*[\p{L}]))*)( (([\p{Lu}]|Ph|Ch|Th)\.?)+)?(( ([\p{Lu}][\p{L}'\x{2019}\-]*[\p{L}]))*) ((van|de[rsn]?|van de[srn]?|el|(in)? ['\x{2019}]t|tot|te[rn]?|op|tot|uij?t|bij|aan|voor|von|Mac|\x{00D3}) )?([\p{Lu}][\p{L}'\x{2019}\-]*[\p{L}])+/u", $text, $matches);
	if (!$matches) {
		return array();
	}

	$names = array();
	foreach ($matches[0] as $name) {
		$name = normalizeCandidateName($name);
		if ($name != '' && !rejectCandidateName($name, $filters)) {
			$names[] = $name;
		}
	}
	$names = array_iunique($names);
	$names = array_filter($names);
	sort($names);

	return $names;
}

function prepareTextForNameExtraction($text, $filters) {
	$text = str_replace($filters['htmlBreakTags'], " \n", $text);
	foreach ($filters['prefixWords'] as $value) {
		$text = preg_replace("/\b".$value." \b/m", " ", $text);
	}

	return html_entity_decode($text);
}

function normalizeCandidateName($name) {
	$name = trim($name);
	$name = preg_replace("/^((Prof|Dr|Mr|Ms)\.?)?( ?(Prof|Dr|Mr|Ms)\.?)? (.+)/", "$5", $name);
	return trim($name);
}

function rejectCandidateName($name, $filters) {
	foreach ($filters['rejectWords'] as $item) {
		if(preg_match("/\b".$item."\b/", strtoupper($name))){
			return true;
		}
	}

	return false;
}

function normalizeSubmittedNames($names) {
	$names = explode("\n", $names);
	$names = array_map('trim', $names);
	$names = array_filter($names);
	return array_iunique($names);
}

function is_404($url) {
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
 	curl_setopt($handle, CURLOPT_MAXREDIRS, 5); 
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
   	curl_setopt($handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9');/* Get the HTML or whatever is linked in $url. */
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
//non-case-sensitive array_unique
function array_iunique($array) {
    return array_intersect_key(
        $array,
        array_unique(array_map("StrToLower",$array))
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

  <!-- Basic Page Needs
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta charset="utf-8">
  <title>Orator Matcher</title>
  <meta name="description" content="">
  <meta name="author" content="">

  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />

  <!-- Mobile Specific Metas
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- FONT
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">

  <!-- CSS
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <link rel="stylesheet" href="css/normalize.css">
  <link rel="stylesheet" href="css/skeleton.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.12.1/jquery-ui.css">

 <!--  Favicon
  ––––––––––––––––––––––––––––––––––––––––––––––––––
  <link rel="icon" type="image/png" href="images/favicon.png"> -->
 
 <!-- Javascripts
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->


<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="script.js"></script>

</head>
<body>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <div class="container" style="margin-top: 10">
    <div class="row">
        <form class="form-wrapper"  method="POST"  target='_self' id="form"	>
			<div style='color:red; clear:both;'><?php echo $error; ?></div>
			Search for names in on the following webpage:
		   <div> <input type="text" id="url" placeholder="https://" name='url' >


		<div id='showTex' style='clear:both; '><b>OR</b><br/> direct input:</div>
		<textarea rows='10'  cols='50' name='names' form='form' class='<?php echo (isset($names)? 'retracted' :'expand'); ?>'></textarea>
<div class="analyse">
		<p>Treat the direct input as:</p>
  <input type="radio" id="analyse_true" name="analyse" value="true"
         checked>
  <label for="analyse_true">Text to search for names in</label>

  <input type="radio" id="analyse_false" name="analyse" value="false">
  <label for="analyse_false">List of names</label>
   		
</div>
		    <input type="submit" class='button' value="go" id="submit">
		</div> 	
		</form>
		<div>

<?php
if (isset($names) && count($names)){
	echo '<form class="form-wrapper" method="POST" action="posttoget.php" >';
	echo "Optional: remove false positives before going to matching with Wikidata";
	echo "<ul id='names'>";
	foreach ($names as $key => $value) {

		echo "<li><span class='remove button' alt='remove button'></span><span class='word'>".str_replace(" ", "</span> <span class='word'>", $value)."</span><input name='name[]' type='hidden' class='hiddenNames' value='".urlencode($value)."'</li>";
	}
	echo "</ul>";
	echo "<input type='submit' class='button' id='go' value='go'/>";	
	echo "</form>";
}

?>
 <footer id="footer">Orator Matcher by <a href="https://www.veradekok.nl/" target="_blank">Vera de Kok</a><br/><a href="https://github.com/VDK/Orator-Matcher" target="_blank">Code on GitHub</a>, available under the MIT license</footer>
</div>
      </div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->

	
</body>

</html>
