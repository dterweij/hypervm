<?php 

include_once "htmllib/lib/displayinclude.php";

$nonsslhash = "#";
$sslport = $sgbl->__var_prog_ssl_port;
$nonsslport = $sgbl->__var_prog_port;

$list = parse_opt($argv);
if (!isset($list['default-port']) && !lxfile_exists("__path_slave_db")) {

	initProgram('admin');
	$gen = (isset($login->getObject('general')->portconfig_b));
	if ($gen) {
		if (isset($gen->isOn('nonsslportdisable_flag'))) {
			$nonsslhash = "";
		}
		if (isset($gen->sslport)) {
			$sslport = $gen->sslport;
		}
		if (isset($gen->nonsslport)) {
			$nonsslport = $gen->nonsslport;
		}
	}
}

$list = lfile("htmllib/filecore/lighttpd.conf");
$user = "server.username            = \"lxlabs\"";
$cgi =  "cgi.assign                 = (\".php\" => \"/usr/local/lxlabs/ext/php/bin/php_cgi\" )\n";

foreach($list as &$l) {

	$l = preg_replace("/__cgi_or_fcgi__/", $cgi, $l);
	$l = preg_replace("/__program_name__/", $sgbl->__var_program_name, $l);
	$l = preg_replace("/__program_disable_nonssl__/", $nonsslhash, $l);
	$l = preg_replace("/__program_port__/", $nonsslport, $l);
	$l = preg_replace("/__program_sslport__/", $sslport, $l);
	$l = preg_replace("/__program_user__/", $user, $l);
}

lfile_put_contents("../file/lighttpd.conf", implode("", $list));

$pemfile = "__path_program_root/etc/program.pem";
$cafile = "__path_program_root/etc/program.ca";

if (!lxfile_exists($pemfile)) {
	lxfile_cp("__path_program_htmlbase/htmllib/filecore/program.pem", $pemfile);
	lxfile_generic_chown($pemfile, "lxlabs");
}

if (!lxfile_exists($cafile)) {
	lxfile_cp("__path_program_htmlbase/htmllib/filecore/program.ca", $cafile);
	lxfile_generic_chown($cafile, "lxlabs");
}

// Merged from 6.1.x/kloxo/bin/common/misc/fixlighty.php	(revision 472)
lxfile_touch("__path_program_root/log/lighttpd_error.log");
lxfile_touch("__path_program_root/log/access_log");
lxfile_generic_chmod("__path_program_root/log", "0700");
lxfile_generic_chown("__path_program_root/log", "lxlabs:lxlabs");
lxfile_generic_chmod("__path_program_root/log/lighttpd_error.log", "0644");
lxfile_generic_chmod("__path_program_root/log/access_log", "0644");
lxfile_generic_chown("__path_program_root/log/lighttpd_error.log", "lxlabs:root");
lxfile_generic_chown("__path_program_root/log/access_log", "lxlabs:root");
//


