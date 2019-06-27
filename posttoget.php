<?php
//with the names in the URL, they can be imported in carious ways 
//serializing GET variables, makes the URL really really long, leading to limitations
//this is a work around
if( isset($_POST['name']) && is_array($_POST['name'])){
  	header('Location: sparql.php?names='. implode("|", $_POST['name']));
}

?>