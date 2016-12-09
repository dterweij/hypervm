<?php
//
//    HyperVM, Server Virtualization GUI for OpenVZ and Xen
//
//    Copyright (C) 2000-2009       LxLabs
//    Copyright (C) 2009-2016       LxCenter
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as
//    published by the Free Software Foundation, either version 3 of the
//    License, or (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    You should have received a copy of the GNU Affero General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
/**
 *
 */
function startServer()
{
	global $gbl, $sgbl, $login, $ghtml; 
	global $argv, $argc;
	
	if ($argv[1] === 'slave') {
		$login = new Client(null, null, 'slave');
		//Initthisdef uses the db to load the drivers. NO longer callable in slave.
		//$login->initThisDef();
		$gbl->is_slave = true;
		$gbl->is_master = false;
		$rmt = unserialize(lfile_get_contents("__path_slave_db"));
		$login->password = $rmt->password;
		$argv[1] = "Running as Slave";
	} else if($argv[1] === 'master'){
		$login = new Client(null, null, 'admin');
		$gbl->is_master = true;
		$gbl->is_slave = false;
		$login->get();
		$argv[1] = "Running as Master";
	} else {
		print("Wrong arguments\n");
		exit;
	}
    
	$login->cttype = 'admin';

	//set_error_handler("lx_error_handler");
	//set_exception_handler("lx_exception_handler");
    
    // Set PHP execution time limit to unlimited 
	set_time_limit(0);
    
	createServerstream();

}

/**
 *
 */
function checkCronScavenge()
{
	global $gbl, $sgbl, $login, $ghtml; 
	//dprint("in Do server stuff\n");

	if (if_demo()) {
		return;
	}

	try {
		timed_execution();
		if ($sgbl->is_this_master()) {
			$schour = null;
			$schour = $login->getObject('general')->generalmisc_b->scavengehour;
			$scminute = $login->getObject('general')->generalmisc_b->scavengeminute; 
			//dprint("Cron exec $schour, $scminute\n");
			if ($schour) {
				cronExec($schour, $scminute, "startScavenge");
			} else {
				cronExec("3", "57", "startScavenge");
			}
		}
	} catch (exception $e) {
		print("Caught Exception: ");
		print($e->getMessage());
		print("\n");
	}
}

/**
 * @param $hour
 * @param $minute
 * @param $func
 */
function cronExec($hour, $minute, $func)
{
	static $localvar;

	//dprint("in Cron exec\n");
	//dprintr($localvar);

	$time = mktime($hour, $minute , 0, date('n'), date('j'), date("Y"));
	$now = time();

	if (isset($localvar[$func]) && $localvar[$func]) {
		//dprint("Already execed \n");
		if ($now > $time + 2 * 60) {
			$localvar[$func] = false;
		}
		return ;
		
	}

	if ($now > $time && $now < $time + 2* 60) {
		$localvar[$func] = true;
		log_log("cronExec", "Executing $func");
		$func();
	}
}

/**
 * @param $time
 * @param $func
 */
function timedExec($time, $func)
{
	$v = "global_v$func";
	global $$v;
	$ct = time();
	if (($ct - $$v) >= $time * 30 ) {
		//dprint("Executing at $ct {$$v} rd time $func\n");
		$$v = $ct;
		$func();
	}
}

/**
 *
 */
function startScavenge()
{
	global $gbl, $sgbl, $login, $ghtml; 
	dprint("Executing collect quota\n");
	$olddir = getcwd();
	lchdir("__path_program_htmlbase");
	exec_with_all_closed("$sgbl->__path_php_path ../bin/scavenge.php");
	lchdir($olddir);
}

/**
 *
 */
function checkRestart()
{
	
	if (if_demo()) {
		return;
	}

	$res = lscandir_without_dot("__path_program_etc/.restart");

	if ($res === false) {
		dprint(".restart does not exist... Creating\n");
		lxfile_mkdir("__path_program_etc/.restart");
		lxfile_generic_chown("__path_program_etc/.restart", "lxlabs");
	}

	foreach((array) $res as $r) {
		if (csb($r, "._restart_")) {
			$cmd = strfrom($r, "._restart_");
		}
		lunlink("__path_program_etc/.restart/$r");
		dprint("Restarting $cmd\n");
		// THe 3,4 etc are the tcp ports of this program, and it should be closed, else some programs will grab it.
		//exec("/etc/init.d/$cmd restart  </dev/null >/dev/null 2>&1 3</dev/null 4</dev/null 5</dev/null 6</dev/null &");
		switch($cmd) {
			case 'lxcollectquota':
				exec_justdb_collectquota();
				break;

			case 'openvz_tc':
				exec_openvz_tc();
				break;

			default:
				exec_with_all_closed("/etc/init.d/$cmd restart");
				break;
		}
	}
}

/**
 *
 */
function exec_openvz_tc()
{
	lxshell_background("sh", "__path_program_etc/openvz_tc.sh");
}

/**
 * @param $cmd
 */
function special_bind_restart($cmd)
{
	global $gbl, $sgbl, $login, $ghtml; 

	if (myPcntl_fork() === 0) {
	@	socket_close($sgbl->__local_socket);
	@	exec("/etc/init.d/$cmd restart  </dev/null >/dev/null 2>&1 &");
		exit;
	} else {
	@	myPcntl_wait();
	}
}

/**
 *
 */
function reload_lxserver_password()
{
	global $gbl, $sgbl, $login, $ghtml; 

	static $time;

	$stat = llstat("__path_admin_pass");
	$cur = $stat['mtime'];

	if ($cur > $time) {
		$rmt = lfile_get_contents("__path_admin_pass");
		$login->password = $rmt;
		$time = $cur;
	}
}

/**
 * @param $d
 * @return Remote
 */
function root_main($d)
{
	reload_lxserver_password();

	try {
		$res = do_root_main($d);
		$res->exception = null;
	} catch (exception $e) {
		dprint("Caught Exception: " . $e->getMessage());
		$res = new Remote();
		$res->ret = -1;
		$res->exception = $e;
	}
	return $res;
}

/**
 * @param $data
 * @return Remote
 */
function do_root_main($data)
{

	dprintr("Remote: ");
	dprintr($data);
	return  do_remote($data);
}

/**
 *
 */
function timed_execution()
{
	global $global_dontlogshell;

	$global_dontlogshell = true;
	timedExec(2,  "checkRestart");
	timedExec(10, "VM_CollectData");
	$global_dontlogshell = false;
}

/**
 * @desc Collect VM data like lxguard, VM traffic/CPU/memory, Tickets
 */
function VM_CollectData()
{
	dprint("Starting VM data collection\n");
	lxshell_background("__path_php_path", "../bin/sisinfoc.php");
}

/**
 *
 */
function start_portmonitor()
{
	dprint("Starting portmonitor\n");
	system("pkill -f lxportmonitor.php");
	lxshell_background("__path_php_path", "../bin/common/lxportmonitor.php", "--data-server=localhost");
}

