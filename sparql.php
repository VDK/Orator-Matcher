<html><head>

<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.11.4/jquery-ui.js"></script>
<script src="https://tools-static.wmflabs.org/cdnjs/ajax/libs/tinysort/3.2.2/tinysort.js"></script>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://tools-static.wmflabs.org/cdnjs/ajax/libs/jqueryui/1.11.4/themes/smoothness/theme.css">



<script type="text/javascript" src="plugins/deference/js/deference.js"></script>
<script type="text/javascript" src="query.js"></script>
<title>Orator matcher</title>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
</head>
<body>
<form class="form-wrapper"  id="form"	>
<a href="index.php">< back home</a>
  <div>
        <input type="checkbox" id="sportsPersonCheck" name="feature"
               value="sportsPersonCheck" checked />
        <label for="sportsPersonCheck">include sportspeople</label>
    </div>
</form>
<div id='progressbar'></div>
<ul id='names'>
<?php

$names = $_GET['name'];
$names = array_unique($names);
foreach ($names as $key => $name) {
	$name = trim($name);
	if ($name != ''){
		echo "<li class='name' weight='-1'>".$name."<ul class='response' ></ul></li>";
	}
}


?>
</ul>
</body>
</html>	
