<?php

// parsing an array as a GET variable the standard way makes the URL really really long, 
// this limits the length of this array
// this is a workaround 
// is this bad? maybe. It does work though.

include_once('slider_vars.php');

if( isset($_POST['name']) && is_array($_POST['name'])){
  header('Location: sparql.php?names='. implode("|", $_POST['name']).'&y1='.$y1.'&y2='.$y2);
}

?>
