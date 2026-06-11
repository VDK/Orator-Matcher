<?php
include_once('slider_vars.php');
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
  <link rel="stylesheet" href="css/slider.css">
  <link rel="stylesheet" href="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.11.4/themes/smoothness/theme.css">

 <!--  Favicon
  ––––––––––––––––––––––––––––––––––––––––––––––––––
  <link rel="icon" type="image/png" href="images/favicon.png"> -->
 
 <!-- Javascripts
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->

<script type="text/javascript">
  var y1 = <?php echo $y1; ?>;
  var y2 = <?php echo $y2; ?>;
</script>

<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.11.4/jquery-ui.js"></script>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/tinysort/3.2.2/tinysort.js"></script>
<script type="text/javascript" src="slider.js"></script>
<script type="text/javascript" src="query.js"></script>
</head>
<body>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <div class="container" style="margin-top: 10px">
    <div class="row">

<form class="form-wrapper matcher-form"  id="form"	>
<a href="index.php" class="pageButton homeButton">Back home</a>
  <div>

<?php
if ((!isset($_GET['name']) || !is_array($_GET['name'])) && !isset($_GET['names'])){
	echo "<p style='color:red'>bad input</p></div></form>";
}
else{ ?>

  <div id="slider">
        <div class="sliderLabel">alive between</div>
        <div class="sliderControls">
            <select id="amount1" data-index="0" class="sliderValue" name="y1">
              <?php echo sliderYearOptions($y1, array($y1, $y2)); ?>
            </select>
            <div id="slider-range"></div>
            <select id="amount2" data-index="1" class="sliderValue" name="y2">
              <?php echo sliderYearOptions($y2, array($y1, $y2)); ?>
            </select>
        </div>
          
        </div>
        <div class="analyse">
        <p>Include</p>
        <div class="filterOptions">
          <label class="filterOption" for="sportsPersonCheck">
            <input type="checkbox" id="sportsPersonCheck" name="feature"
                   value="sportsPersonCheck" checked />
            <span>Sports people <span class="featureCount" id="sportsPersonCount">(0)</span></span>
          </label>
          <label class="filterOption" for="orcidCheck">
            <input type="checkbox" id="orcidCheck" name="feature"
                   value="orcidCheck" checked />
            <span>ORCID people <span class="featureCount" id="orcidCount">(0)</span></span>
          </label>
          <label class="filterOption" for="peerageCheck">
            <input type="checkbox" id="peerageCheck" name="feature"
                   value="peerageCheck" checked />
            <span>Peerage people <span class="featureCount" id="peerageCount">(0)</span></span>
          </label>
        </div>
        <!-- botanists-->
      </div>
    </div>
</form>
<div id='progressbar'></div>
	<ul id='names'>
	<?php
  
  $names =array();
  //backwards compatibility variable name:  

  if (isset($_GET['name'])){
	 $names = array_merge($names, $_GET['name']);
  }
  if (isset($_GET['names'])){
   $names = array_merge($names, explode("|", urldecode($_GET['names'])));
  }
	$names = array_unique($names);
	foreach ($names as $key => $name) {
		$name = trim($name);
		$name = strip_tags($name);
		if ($name != ''){
			$displayName = htmlspecialchars(urldecode($name), ENT_QUOTES, 'UTF-8');
			echo "<li class='name' weight='-1'><span class='searchTerm'>".$displayName."</span><ul class='response' ></ul></li>";
		}
	}
	echo "</ul>";
}


?>
    <footer id="footer">Orator Matcher by <a href="https://www.veradekok.nl/" target="_blank">Vera de Kok</a><br/><a href="https://github.com/VDK/Orator-Matcher" target="_blank">Code on GitHub</a>, available under the MIT license</footer>
 	 </div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->

	
</body>

</html>
