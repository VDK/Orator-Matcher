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


<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.11.4/jquery-ui.js"></script>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/tinysort/3.2.2/tinysort.js"></script>
<script type="text/javascript" src="slider.js"></script>
<script type="text/javascript" src="plugins/deference/js/deference.js"></script>
<script type="text/javascript" src="query.js"></script>
</head>
<body>

  <!-- Primary Page Layout
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->
  <div class="container" style="margin-top: 10px">
    <div class="row">

<form class="form-wrapper"  id="form"	>
<a href="index.php">< back home</a>
  <div>

<?php
if ((!isset($_GET['name']) || !is_array($_GET['name'])) && !isset($_GET['names'])){
	echo "<p style='color:red'>bad input</p></div></form>";
}
else{ ?>

  <!-- <div id="slider">
        <div> earliest / latest year of birth </div>
            <input type="text" id="amount1" value="<?php echo $y1; ?>" data-index="0" class="sliderValue" name="y1" />
            <div id="slider-range"></div>
            <input type="text" id="amount2" value="<?php echo $y2; ?>" data-index="1" class="sliderValue" name="y2" />
          
        </div> -->
        <div class="analyse">
        <p>Include</p>
        <input type="checkbox" id="sportsPersonCheck" name="feature"
               value="sportsPersonCheck" checked />
        <label for="sportsPersonCheck">Athletes</label>
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
			echo "<li class='name' weight='-1'>".urldecode($name)."<ul class='response' ></ul></li>";
		}
	}
	echo "</ul>";
}


?> 	 </div>
  </div>

<!-- End Document
  –––––––––––––––––––––––––––––––––––––––––––––––––– -->

	
</body>

</html>