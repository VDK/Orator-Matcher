<?php

$y1 = 2010;
$y2 = date('Y');

if (isset($_GET['y1']) && isset($_GET['y2'])){
	$y1 = intval($_GET['y1']);
	$y2 = intval($_GET['y2']);
}
elseif(isset($_POST['y1']) && isset($_POST['y2'])){
	$y1 = intval($_POST['y1']);
	$y2 = intval($_POST['y2']);
}
?>
