<?php

// Temp!!
ini_set("error_reporting", E_ALL);
ini_set("display_errors", "On");
ini_set("log_errors", "On");

$path = __FILE__;
$dir = dirname(dirname(dirname($path)));

include_once "$dir/htmllib/lib/includecore.php";

include_once "lib/html.php";
include_once "htmllib/lib/include.php"; 


$ghtml = new Html();

