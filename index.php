<?php
// header("Content-type: html/text; charset=utf-8");
include_once('vendor/autoload.php');
include_once('variables.php');
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;
$tags = array('</p>','<br />','<br>','<hr />','<hr>','</h1>','</h2>','</h3>','</h4>','</h5>','</h6>', '</div>');
$blacklist = array('UNIVERSITY', 'UNITED STATES', "UNIVERSITEIT", "LIBRARY");
$error = '';
if (isset($_POST['url']) && $_POST['url'] != '' ){
	if (!filter_var($_POST['url'], FILTER_VALIDATE_URL)){
		$error = 'That doesn\'t look like a url';
	}
	elseif (is_404($_POST['url']) ) {
		$error = '404 page not found';
	}
	else{
		$readability = new Readability(new Configuration());
		$opts = array('http'=>array('header' => "User-Agent:MyAgent/1.0\r\n"));
		$context = stream_context_create($opts);
		$html = file_get_contents($_POST['url'], false, $context);

		try {
		    $readability->parse($html);
		    $result = $readability->getContent();
		    
		} catch (ParseException $e) {
			//might as well use the old method if Readability fails:
			$ch = curl_init();
			$url = $_POST[ 'url']; 
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

		$result = str_replace($tags,"\n",$result);
		$result = strip_tags($result);
		$result = html_entity_decode($result);
		// $result = mb_convert_encoding( $result, 'UTF-8');

		preg_match_all("/[A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòùìâêôûîãñõA-Z]+(-[A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòùìâêôûîãñõA-Z]+)?( [A-Z]\.?)?( ([A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòùìâêôûîãñõA-Z]+))?[ -]((van|der?|van der?|el|'t|tot|ter|op|tot|uij?t|bij|aan|voor|von|Mac|Ó) )?(O')?[A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòùìâêôûîãñõA-Z]+( ([A-ZÁÉÓÚÍÄËÖÜÏÀÈÒÙÌÂÊÔÛÎÃÑÕ][a-záéóúíäëöüïàèòùìâêôûîãñõA-Z]+))?/", $result, $matches);
		
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
    curl_setopt($handle, CURLOPT_USERAGENT, "User-Agent:MyAgent/1.0\r\n");
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
<title>Orator matcher</title>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta charset="UTF-8">
</head>
<body>
	<form class="form-wrapper"  method="POST"  target='_self' id="form"	>
	<div style='color:red; clear:both;'><?php echo $error; ?></div>
   <div> <input type="text" id="url" placeholder="Article URL" name='url' >

<?php
if (!isset($names)){
echo "<div style='clear:both'>Or a list of names:</div>";
echo "<textarea rows='10'  cols='50' name='names' form='form'></textarea>";
}
?>

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