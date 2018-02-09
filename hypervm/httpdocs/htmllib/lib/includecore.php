<?php

/*
 * Core functions
 * Could be moved in some other file
 */


// TODO: Change debug output to a better output with a new function
/**
 * @param $var
 * @param null $mess
 * @param int $dbg
 * @return string|void
 */
function print_time($var, $mess = null, $dbg = 2)
{
	static $last;

	$now = microtime(true);
	if (!isset($last[$var])) {
		$last[$var] = $now;
		return;
	}
	$diff = round($now - $last[$var], 7);
	$now = round($now, 7);
	$last[$var] = $now;
	if (!$mess) {
		return;
	}
	$diff = round($diff, 2);

	if ($dbg <= -1) {
	} else {
		dprint("<br>[debug] $mess $diff seconds<br>", $dbg);
	}

	return "$mess: $diff seconds";
}

print_time('full');

/**
 * @return bool
 */
function windowsOs()
{
	if (getOs() == "Windows") {
		return true;
	}
	return false;
}

/**
 * @return string
 */
function getOs()
{
	return (substr(php_uname(), 0, 7) == "Windows")? "Windows": "Linux";
}

if(!isset($_SERVER['DOCUMENT_ROOT'])) {
	if (isset($_SERVER['SCRIPT_NAME'])) {
		$n = $_SERVER['SCRIPT_NAME'];
		$f = ereg_replace('\\\\', '/',$_SERVER['SCRIPT_FILENAME']);
		$f = str_replace('//','/',$f);
		$_SERVER['DOCUMENT_ROOT'] = eregi_replace($n, "", $f);
	}
}

if (!$_SERVER['DOCUMENT_ROOT']) {
	$_SERVER['DOCUMENT_ROOT'] = $dir;
}

if (WindowsOs()) {
	//ini_set("include_path", ".;{$_SERVER['DOCUMENT_ROOT']}");
} else {
	ini_set("include_path", "{$_SERVER['DOCUMENT_ROOT']}");
}

/**
 * @param $vpath
 * @return string
 */
function getreal($vpath)
{
     return  $_SERVER["DOCUMENT_ROOT"] . "/". $vpath; 
}

/**
 * @param $vpath
 */
function readvirtual($vpath)
{
     readfile($_SERVER["DOCUMENT_ROOT"] . $vpath);
}
