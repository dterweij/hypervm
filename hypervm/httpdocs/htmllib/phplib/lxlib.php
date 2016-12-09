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

// TODO SET THIS IN php.ini
date_default_timezone_set("Europe/Amsterdam");

include_once "htmllib/lib/linuxlib.php";
include_once "lib/linuxproglib.php";

/**
 *
 */
function remotetestfunc()
{
}

/**
 *
 */
define('S_IFDIR', 00040000);
/**
 *
 */
define('S_ISUID', 00004000);
/**
 *
 */
define('S_ISGID', 00002000);

// This is the only function that executes during the initialization...
// The rest of the whole library exists as functions that can be called...
// Nothing gets executed on their own... Except this..
// So it makes this sort of special... very special..

init_global();

/**
 *
 */
function init_global()
{
	global $gbl, $sgbl, $ghtml;
	global $g_demo;

	$sgbl = new Sgbl();
	$gbl = new Gbl();
	$gbl->get();

	// TODO needs otherway! check php.ini first
    date_default_timezone_set("Europe/Amsterdam");

//
// Turn on demo version by putting a empty file called demo in the etc dir.
// More info about enable a demo server soon at our wiki
//
	if (lfile_exists("__path_program_etc/demo")) {
		$g_demo = 1;
	}

	DEBUG_Mode("/usr/local/lxlabs/hypervm/httpdocs/commands.php");
    $sgbl->method = ($sgbl->dbg >= 1) ? "get" : "post";

//
// ### LxCenter
//
// Check for Development/Debug version
// If file not exists, Production mode (-1)
// If file exists it can have the following numbers to enable
// 1  = Debug mode 1
// 2  = Debug mode 2 = lxportmonitor.php data
// 3  = Debug mode 3
// 4  = Debug mode 4
// 5  = Debug mode 5
// -1 = Turn Off and go to production mode

}

/**
 * @return null
 * @desc Read and check debug settings
 */
function DEBUG_Settings()
{
	global $gbl, $sgbl, $login, $ghtml;
    DEBUG_Mode("/usr/local/lxlabs/hypervm/httpdocs/commands.php");
	if ($sgbl->isDebug()) {
		return null;
	}
    DEBUG_Mode("/usr/local/lxlabs/hypervm/httpdocs/backend.php");
    return null;
}

/**
 * @param $file
 * @return null
 * @desc Set debug mode 
 */
function DEBUG_Mode($file)
{
	global $gbl, $sgbl, $login, $ghtml;
	if (lfile_exists($file)) {
		$sgbl->dbg = trim(file_get_contents($file));
		if ($sgbl->dbg != "1" && $sgbl->dbg != "2" && $sgbl->dbg != "3" && $sgbl->dbg != "4" && $sgbl->dbg != "5") {
			$sgbl->dbg = -1;
		}
	} else {
		$sgbl->dbg = -1;
	}

	if ($sgbl->dbg > 0) {
		ini_set("error_reporting", E_ALL);
		ini_set("display_errors", "On");
		ini_set("log_errors", "On");
	} else {
		ini_set("error_reporting", E_ERROR);
		ini_set("display_errors", "On"); // temp on
		ini_set("log_errors", "On");
	}
    return null;
}

/**
 * @return bool
 */
function isUpdating()
{
	return lx_core_lock_check_only("update.php");
}

/**
 * Class lxException
 */
class lxException extends Exception
{
    /**
     * @var
     */
    public $syncserver;
    /**
     * @var
     */
    public $class;
    /**
     * @var string
     */
    public $variable;
    /**
     * @var
     */
    public $error;
    /**
     * @var string
     */
    public $message;

    /**
     * @return string
     */
    function getClass()
	{
		return lget_class($this);
	}

    /**
     * lxException constructor.
     * @param string $message
     * @param string $variable
     * @param null $value
     */
    function __construct($message, $variable = 'nname', $value = null)
	{
		$this->message = $message;
		$this->variable = $variable;
		$this->value = $value;
		$this->__full_message = "$message: $variable: $value";
		//log_log("exception", "$message: $variable: $value");
	}

    /**
     * @return string
     */
    function getlMessage()
	{
		return "$this->message: $this->variable: $this->value";
	}
}

/**
 * @return mixed
 */
function getAllOperatingSystemDetails()
{
	$ret = findOperatingSystem();
	$ret['loadavg'] = os_getLoadAvg();
	dprintr($ret);
	return $ret;
}

/**
 * @param null $type
 * @return mixed
 */
function findOperatingSystem($type = null)
{
   
	if (file_exists("/etc/fedora-release")) {
		$ret['os'] = 'fedora';
		$ret['version'] = file_get_contents("/etc/fedora-release");
		$ret['pointversion'] = find_os_pointversion();
	} else if (file_exists("/etc/redhat-release")) {
		$ret['os'] = 'rhel';
		$ret['version'] = file_get_contents("/etc/redhat-release");
		$ret['pointversion'] = find_os_pointversion();
	}

	if (lxfile_exists("__path_program_etc/install_xen") || lxfile_exists("/proc/xen")) {
		$ret['vpstype'] = "xen";
		$ret['xenlocation'] = vg_complete();
	}

	if ($type) {
		return $ret[$type];
	}
	return $ret;
}

/**
 * @return mixed|string
 */
function find_os_pointversion()
{
	return find_os_selecttype('pointversion');
}

/**
 * @param $select
 * @return mixed|string
 */
function find_os_selecttype($select)
{
        // list os support
        $ossup = array('redhat' => 'rhel', 'fedora' => 'fedora', 'centos' => 'centos');

        foreach(array_keys($ossup) as $k) {
                $osrel = file_get_contents("/etc/{$k}-release");

                if ($osrel) {
                                if ($select === 'release') {
                                        return $osrel;
                                }

                                $osrel = strtolower(trim($osrel));

                                break;
                }
        }

        // specific for 'red hat'
        $osrel = str_replace('red hat', 'redhat', $osrel);

        $osver = explode(" ", $osrel);

        $verpos = sizeof($osver) - 2;

        if (array_key_exists($osver[0], $ossup)) {
                // specific for 'red hat'
                if ($osrel === 'redhat') {
                        $oss = $osver[$verpos];
                }
                else {
                        $mapos = explode(".", $osver[$verpos]);
                        $oss = $mapos[0];
                }

                if ($select === 'distro') {
                        return $ossup[$osver[0]];
                }
                else if ($select === 'pointversion') {
                        return $ossup[$osver[0]]."-".$oss;
                }
        }
}

/**
 * @param $arg
 * @param bool $dotflag
 * @return mixed
 */
function lscandir_without_dot($arg, $dotflag = false)
{  
	$list = lscandir($arg);

	if (!$list) {
		return $list;
	}

	foreach ($list as $k => $v) {
		if ($v === ".." || $v === "." || $v === '.svn' || $v === '.git')  {
			unset($list[$k]);
		}
		if ($dotflag && csb($v, '.')) {
			unset($list[$k]);
		}
	}
	return $list;
}

/**
 * @param $arg
 * @param bool $dotflag
 * @return mixed
 */
function lscandir_without_dot_or_underscore($arg, $dotflag = false)
{
	$list = lscandir($arg);

	if (!$list) {
		return $list;
	}

	foreach ($list as $k => $v) {
		if ($v === ".." || $v === "." || $v === '.svn' || $v === '.git') {
			unset($list[$k]);
		}
		if ($dotflag && csb($v, '.')) {
			unset($list[$k]);
		}
		if (csb($v, "__")) {
			unset($list[$k]);
		}
	}
	return $list;
}

/**
 * @param $arg
 * @return mixed
 */
function lscandir($arg)
{
	return lx_redefine_func("scandir", $arg);
}

/**
 * @param $arg
 * @return mixed
 */
function lunlink($arg)
{
	return lx_redefine_func("unlink", $arg);
}

/**
 * @param $arg
 * @return mixed
 */
function ltouch($arg)
{
	return lx_redefine_func("touch", $arg);
}

/**
 * @param $arg
 * @return mixed
 */
function lchdir($arg)
{
	return lx_redefine_func("chdir", $arg);
}

/**
 * @param $fp
 */
function takeToStartOfLine($fp)
{
	while (fgetc($fp) == "\n" && ftell($fp) != 1 && ftell($fp) != 0) {
		fseek($fp, -2, SEEK_CUR);
	}

	while (($c = fgetc($fp)) != "\n" && ftell($fp) != 1 && ftell($fp) != 0) {
		fseek($fp, -2, SEEK_CUR);
	}
}

/**
 * @param $file
 * @param $lines
 * @return null|string
 */
function tail_func($file, $lines)
{
	$fp = fopen($file, "r");

	if (!$fp) {
		return null;
	}

	dprint("in Tail Func\n");

	fseek($fp, 0, SEEK_END);

	// Go back onece and read the line.
	takeToStartOfLine($fp);
	$arr[] = fgets($fp);

	$n = 0;
	while ($n < $lines && ftell($fp) !== 1) {
		$n++;
		//dprint($n . "\n");
		// You have to go back twice.
		print(' Before: ' . ftell($fp) . "\n");
		fseek($fp, -3, SEEK_CUR);
		takeToStartOfLine($fp);
		fseek($fp, -3, SEEK_CUR);
		print(' aaFter: ' . ftell($fp) . "\n");
		takeToStartOfLine($fp);
		print(' aaaaFter: ' . ftell($fp) . "\n");
		$arr[] = fgets($fp);
	}
	return implode("", array_reverse($arr));
}

/**
 * @param $file
 * @return mixed|null
 */
function lfile_get_json_unserialize($file)
{
	if (!lxfile_exists($file)) {
		return null;
	}
	return json_decode(lfile_get_contents($file), true);
}

/**
 * @param $file
 * @param $var
 * @return bool
 */
function lfile_put_json_serialize($file, $var)
{
	return lfile_put_contents($file, json_encode($var));
}

/**
 * @param $file
 * @return mixed|null
 */
function lfile_get_unserialize($file)
{
	if (!lxfile_exists($file)) {
		return null;
	}
	return unserialize(lfile_get_contents($file));
}

/**
 * @param $file
 * @param $var
 * @return bool
 */
function lfile_put_serialize($file, $var)
{
	return lfile_put_contents($file, serialize($var));
}

/**
 * @param $arg
 * @return mixed
 */
function lfile_get_contents($arg)
{
	return lx_redefine_func("file_get_contents", $arg);
}

/**
 * @param $arg1
 * @param $arg2
 * @return mixed
 */
function lrename($arg1, $arg2)
{
	return lx_redefine_func("rename", $arg1, $arg2);
}

/**
 * @param $arg1
 * @param $arg2
 * @return mixed
 */
function lfopen($arg1, $arg2)
{
	return lx_redefine_func("fopen", $arg1, $arg2);
}

/**
 * @param $arg
 * @return mixed
 */
function lfilesize($arg)
{
	return lx_redefine_func("filesize", $arg);
}

/**
 * @param $arg1
 * @param $arg2
 * @return mixed
 */
function ltempnam($arg1, $arg2)
{
	return lx_redefine_func("tempnam", $arg1, $arg2);
}

/**
 * @param $file
 * @param $data
 * @param $user
 */
function lfile_write_content($file, $data, $user)
{
	if (csa($user, ":")) {
		$realuser = strtil($user, ":");
	} else {
		$realuser = $user;
	}

	if (!check_file_if_owned_by($file, $realuser)) {
		return;
	}
	lfile_put_contents($file, $data);
	lxfile_unix_chown($file, $user);
}

/**
 * @param $filename
 * @param $username
 * @throws lxException
 */
function check_file_if_owned_by_and_throw($filename, $username)
{
	if (!check_file_if_owned_by($filename, $username)) {
		throw new lxexception('file_exists_not_owned', '', $filename);
	}
}

/**
 * @param $file
 * @return bool
 */
function lis_hardlink($file)
{
	$file = expand_real_root($file);
	if (is_dir($file)) {
		return false;
	}
	$stat = stat($file);
	if ($stat['nlink'] >= 2) {
		return true;
	}
	return false;
}

/**
 * @param $file
 * @return bool
 */
function is_soft_or_hardlink($file)
{
	if (!lxfile_exists($file)) {
		return false;
	}

	if (lis_link($file) || lis_hardlink($file)) {
		return true;
	}
	return false;
}

/**
 * @param $user
 * @param $src
 * @param $dst
 */
function new_process_mv_rec($user, $src, $dst)
{
	$src = expand_real_root($src);
	$dst = expand_real_root($dst);
	new_process_cmd($user, null, "mv $src $dst");
}

/**
 * @param $user
 * @param $file
 * @param $perm
 */
function new_process_chmod_rec($user, $file, $perm)
{
	$file = expand_real_root($file);
	$cmd = "chmod -R $perm '$file'";
	new_process_cmd($user, null, $cmd);
}

/**
 * @param $user
 * @param $src
 * @param $dst
 */
function new_process_cp_rec($user, $src, $dst)
{
	$src = expand_real_root($src);
	$dst = expand_real_root($dst);
	$cmd = "cp -a '$src' '$dst'";
	new_process_cmd($user, null, $cmd);
}

/**
 * @param $user
 * @param $dir
 * @param $cmd
 * @return mixed
 */
function new_process_cmd($user, $dir, $cmd)
{
	global $sgbl;

	if (csa($user, ':')) {
		list($user, $group) = explode(':', $user);
	} else {
		$group = $user;
	}

	if ($user === 'root') {
		$user = '__system__';
	}

	if ($dir) {
		$olddir = getcwd();
		chdir($dir);
	}
	if ($user !== '__system__') {
		$uid = is_numeric($user) ? (int) $user : os_get_uid_from_user($user);
		$gid = is_numeric($group) ? (int) $group : os_get_gid_from_user($user);
		exec("{$sgbl->__path_php_path} {$sgbl->__path_program_root}/bin/phpexec.php $uid $gid $cmd 2>&1", $output, $retval);
	} else {
		exec("$cmd 2>&1", $output, $retval);
	}

	if ($dir) {
		chdir($olddir);
	}

	$output = implode("\n", $output);
	log_log('user_cmd', "($dir) $user $cmd $output");

	return $retval;
}

/**
 * @param $file
 * @param $data
 * @param null $flag
 * @return bool|void
 */
function lfile_put_contents($file, $data, $flag = null)
{
	$file = expand_real_root($file);
	
	if (is_soft_or_hardlink($file)) {
		log_log("link_error", "$file is hard or symlink. Not writing\n");
		return;
	}
	
	if (char_search_a($data, "__path_")) {
		dprint("<font color=red>Warning : Trying to write __path into a file $file: </font> $data <br> \n", 3);
	}


	lxfile_mkdir(dirname($file));

	if(file_exists($file))
	{
		if(is_readable($file))
		{
			if(is_writable($file)){
				$result = file_put_contents($file, $data, $flag);
				chown($file, 'lxlabs');
				return $result;
			}
			else{
				$posix_data = posix_getpwuid(fileowner($file));
				$error_msg = 'Could not write the file \'' . $file . '\' with permissions: ' .
				substr(sprintf('%o', fileperms($file)), -4) .
                ' UID: ' . $posix_data['name'] .  ':' . $posix_data['uid'] .
                ' GID: ' . $posix_data['gecos'] .  ':' . $posix_data['gid'] .
				( PHP_SAPI !== 'cgi-fcgi' ? PHP_EOL : '<br />');

				dprint($error_msg);
				//log_log('filesys', $error_msg);
				return false;
			}
		}
		else
		{
			$error_msg = 'Could not read the file \''.$file.'\' with permissions: '.substr(sprintf('%o', fileperms($file)), -4) . ( PHP_SAPI !== 'cgi-fcgi' ? PHP_EOL : '<br />');
			dprint($error_msg);
			//log_log('filesys', $error_msg);
			return false;
		}
	}
	else
	{
		$result = file_put_contents($file, $data, $flag);
		chown($file, 'lxlabs');
		 
		if($result === false)
		{
			$error_msg = 'File \''.$file.'\' could not be created.';
			dprint($error_msg);
			//log_log('filesys', $error_msg);
			return false;
		}
		return true;
	}
}

/**
* @return void
* @param unknown
* @param unknown
* @desc Redefining php functions ... sort of.. Stupid php doesn't allow that. So we do the next best thing.. We add an 'l' to all system functions and then use these functions instead of the php ones... In a way, is a better idea too, since, there might always be some cases where we might want to override this crap. :-)
*/
function lmkdir($dir)
{
	return lx_redefine_func("mkdir", $dir);
}

/**
 * @param $file
 * @return mixed
 */
function lis_executable($file)
{
	return lx_redefine_func("is_executable", $file);
}

/**
 * @param $file
 * @return mixed
 */
function lis_readable($file)
{
	return lx_redefine_func("is_readable", $file);
}

/**
 * @param $file
 * @return mixed
 */
function lreadlink($file)
{
	return lx_redefine_func("readlink", $file);
}

/**
 * @param $file
 * @return mixed
 */
function lis_link($file)
{
	return lx_redefine_func("is_link", $file);
}

/**
 * @param $file
 * @return mixed
 */
function lis_dir($file)
{
	return lx_redefine_func("is_dir", $file);
}

/**
 * @param $src
 * @param $dst
 */
function cp_if_not_exists($src, $dst)
{
	if (lxfile_exists($dst)) {
		return;
	}
	if (!lxfile_exists($src)) {
		return;
	}
	lxfile_cp($src, $dst);
}

/**
 * @param $src
 * @param $dst
 */
function cp_rec_if_not_exists($src, $dst)
{
	if (lxfile_exists($dst)) {
		return;
	}
	if (!lxfile_exists($src)) {
		return;
	}
	lxfile_cp_rec($src, $dst);
}

/**
 * @param $src
 * @param $dst
 */
function mv_rec_if_not_exists($src, $dst)
{
	if (lxfile_exists($dst)) {
		return;
	}
	if (!lxfile_exists($src)) {
		return;
	}
	lxfile_mv_rec($src, $dst);
}

/**
 * @param $file
 * @return mixed
 */
function llstat($file)
{
	return lx_redefine_func("lstat", $file);
}

/**
 * @param $arg
 * @return array
 */
function lx_merge_good($arg)
{
	global $gbl, $sgbl, $login, $ghtml;

	$start = 0;
	$transforming_func = null;

	$arglist = array();
	for ($i = 0; $i < func_num_args(); $i++)
	$arglist[] = func_get_arg($i);

	//dprintr($arglist);

	$list = $arglist;

	foreach ($list as &$l) {
		if (!$l) {
			$l = array();
		}
	}

	$ret = array();
	foreach ($list as $nl) {
		//dprintr($nl);
		if (is_array($nl)) {
			$ret = array_merge($ret, $nl);
		} else {
			$ret[] = $nl;
		}
	}
	return $ret;
}

/**
 * @param $list
 * @return array
 */
function lx_array_merge($list)
{
	foreach ($list as &$l) {
		if (!$l) {
			$l = array();
		}
	}
	$ret = array();
	foreach ($list as $nl) {
		//dprintr($nl);
		if (is_array($nl)) {
			$ret = array_merge($ret, $nl);
		} else {
			$ret[] = $nl;
		}
	}
	return $ret;
}

/**
 * @param $mess
 * @param int $id
 */
function log_switch($mess, $id = 1)
{
	log_log('switch', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_error($mess, $id = 1)
{
	log_log('error', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_bdatabase($mess, $id = 1)
{
	log_log('bdatabase', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_restore($mess, $id = 1)
{
	log_log('restore', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_database($mess, $id = 1)
{
	log_log('database', $mess, $id);
}

/**
 *
 */
function myPcntl_reaper()
{
	pcntl_wait($status, WNOHANG);
}

/**
 *
 */
function myPcntl_wait()
{
	if (!WindowsOs()) {
		pcntl_wait($status);
	}
}

/**
 * @return int
 */
function myPcntl_fork()
{
	global $gbl, $sgbl, $login, $ghtml;
	if (!WindowsOs()) {
		$pid = pcntl_fork();
	} else {
		// make it child
		$pid = 0;
	}
	return $pid;
}

/**
 * @param $file
 * @param $mess
 * @param null $id
 */
function log_log($file, $mess, $id = null)
{
	if (!is_string($mess)) {
		$mess = var_export($mess, true);
	}
	$mess = trim($mess);
	$rf = "__path_program_root/log/$file";

	lfile_put_contents($rf, @ date("H:i M/d/Y") . ": $mess" . PHP_EOL, FILE_APPEND);
}

/**
 * @param $mess
 * @param int $id
 */
function log_ajax($mess, $id = 1)
{
	log_log('ajax', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_redirect($mess, $id = 1)
{
	log_log('redirect_error', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_message($mess, $id = 1)
{
	log_log('message', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_security($mess, $id = 1)
{
	// get IP
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	$user_agent = $_SERVER["HTTP_USER_AGENT"];
	
	if (empty($_SERVER["HTTP_USER_AGENT"])) {
		$user_agent = "Not a browser";
	}

	log_log('security', $mess . " IP: $ip; User agent: $user_agent", $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_filesys_err($mess, $id = 1)
{
	log_log('filesyserr', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_filesys($mess, $id = 1)
{
	global $global_dontlogshell;
	if ($global_dontlogshell) {
		log_log('nonfilesys', $mess, $id);
	} else {
		log_log('filesys', $mess, $id);
	}
}

/**
 * @param $mess
 * @param int $id
 */
function log_shell($mess, $id = 1)
{
	log_log('shell_exec', $mess, $id);
}

/**
 * @param $mess
 * @param int $id
 */
function log_shell_error($mess, $id = 1)
{
	log_log('shell_error', $mess, $id);
}

/**
 * @param $arg
 * @return mixed
 */
function lfile_trim($arg)
{
	$list = lfile($arg);
	foreach ($list as &$s) {
		$s = trim($s);
	}
	return $list;
}

/**
 * @param $src
 * @param $dst
 * @return mixed
 */
function lcopy($src, $dst)
{
	return lx_redefine_func("copy", $src, $dst);
}

/**
 * @param $file
 * @return mixed
 */
function lreadfile($file)
{
	return lx_redefine_func("readfile", $file);
}

/**
 * @param $file
 * @return mixed
 */
function lmd5_file($file)
{
	return lx_redefine_func("md5_file", $file);
}

/**
 * @param $file
 * @return mixed
 */
function lfile_exists($file)
{
	return lx_redefine_func("file_exists", $file);
}

/**
 * @param $file
 * @return mixed
 */
function lsqlite_open($file)
{
	return lx_redefine_func("sqlite_open", $file);
}

/**
* @return void
* @param unknown
* @param unknown
* @desc This function is the core of the the path abstraction. It converts the paths of the form '__path.../dir' to '$sgbl->__path.../dir'. This is used in all the redefined functions to convert their arguments.
*/
function expand_real_root($root)
{
	global $gbl, $sgbl, $login, $ghtml;
	if (char_search_beg($root, "__path")) {
		if (char_search_a($root, "/")) {
			$var = substr($root, 0, strpos($root, "/"));
			$root = $sgbl->$var . "/" . substr($root, strpos($root, "/") + 1);
		} else {
			$root = $sgbl->$root;
		}
	}
	$root = remove_extra_slash($root);
	return $root;
}

/**
 * @param $name
 * @desc Kill old running PID process and save a new PID
 */
function OS_PID_Kill_and_Save($name)
{
	OS_PID_Kill($name);
	usleep(100);
	OS_PID_Save($name);
}

/**
 * @param $name
 * @desc Save actual PID to file
 */
function OS_PID_Save($name)
{
	lfile_put_contents("__path_program_root/pid/$name.pid", os_getpid());
}

/**
 * @param $name
 * @desc Read PID from file and Kill it
 */
function OS_PID_Kill($name)
{
	$pid = lfile_get_contents("__path_program_root/pid/$name.pid");
	os_killpid($pid); // linuxlib.php
}

/**
 * @param $func
 * @return mixed
 */
function lx_redefine_func($func)
{
	global $gbl, $sgbl, $login, $ghtml;

	global $gbl, $sgbl, $login, $ghtml;
	
	$arglist = array();
	for ($i = 1; $i < func_num_args(); $i++)
	$arglist[] = expand_real_root(func_get_arg($i));
	
	return call_user_func_array($func, $arglist);
}

/**
 * @param $stat
 */
function remove_unnecessary_stat(&$stat)
{
	foreach ($stat as $k => $v) {
		if (is_numeric($k)) {
			unset($stat[$k]);
		}
	}
}

/**
 * @param $cmd
 * @param $arglist
 * @return mixed|string|void
 */
function getShellCommand($cmd, $arglist)
{
	global $gbl, $sgbl, $login, $ghtml;
	$args = null;
	$q = $sgbl->__var_quote_char;
	$cmd = expand_real_root($cmd);
	$cmd = str_replace(";", "", $cmd);
	$cmd = "{$q}$cmd{$q}";

	foreach ($arglist as $a) {
		if ($a === "") {
			continue;
		}
		if (is_array($a)) {
			foreach ($a as $aa) {
				$aa = str_replace(";", "", $aa);
				$args .= " $q" . expand_real_root($aa) . "$q";
			}
		} else {
			$a = str_replace(";", "", $a);
			$args .= " $q" . expand_real_root($a) . "$q";
		}
	}
	$cmd .= " " . $args;
	return $cmd;
}

/**
 * Class Remote
 */
class Remote
{
	/*
public $ddata;
public $message;
public $exception;
*/
}

/**
 * @param $var
 * @param int $type
 */
function dprintoa($var, $type = 0)
{
	global $sgbl, $login, $ghtml;
	if ($type > $sgbl->dbg) {
		return;
	}
	if (!is_array($var)) {
		return;
	}
	foreach ($var as $k => $v) {
		dprint("$k =>");
		dprinto($var, $type);
		dprint("\n");
	}
}

/**
 * @param $var
 * @param int $type
 */
function dprinto($var, $type = 0)
{
	global $sgbl;

	if ($type > $sgbl->dbg) {
		return;
	}

	if (!is_object($var)) {
		return;
	}

	$newob = clone($var);
	$newob->__parent_o = null;
	dprintr($newob);
}

/**
 * @param $var
 * @param int $type
 */
function dprintr($var, $type = 0)
{
	global $sgbl;

	if ($type > $sgbl->dbg) {
		return;
	}

	if (is_object($var) && method_exists($var, "clearChildrenAndParent")) {
		$newvar = myclone($var);
		lxclass::clearChildrenAndParent($newvar);
		$newvar->driverApp = 'unset for printing';
		$newvar->__parent_o = 'unset for printing';

		$class = $newvar->get__table();
		if (csb($class, "sp_")) {
			$bclass = strfrom($class, "sp_") . "_b";
			$newvar->$bclass->__parent_o = 'unset for printing';
		}
	} else {
		$newvar = $var;
	}

	if ($sgbl->isBlackBackground()) {
		print("<font color=gray>");
	}
	print_r($newvar);
	if ($sgbl->isBlackBackground()) {
		print("</font> ");
	}
}

/**
 * @param $var
 * @param int $type
 */
function dprint($var, $type = 0)
{
	global $sgbl;
	if ($type <= $sgbl->dbg) {
		if (is_string($var)) {
			if ($sgbl->isBlackBackground()) {
				print("<font color=gray>");
			}
			print($var);
			if ($sgbl->isBlackBackground()) {
				print("</font> ");
			}
		}
	}
}

/**
 * @param $var
 * @param int $type
 */
function dprint_r($var, $type = 0)
{
	global $sgbl;
	if ($type <= $sgbl->dbg) {
		print_r($var);
	}
}

/**
 * @param $socket
 * @return string
 */
function lx_local_socket_read($socket)
{
	return @ socket_read($socket, 2048);
	//$res=socket_recv($MsgSock,$buffer,1024,0);
}

/**
 * @param $haystack
 * @param $needle
 * @param int $insensitive
 * @return bool
 */
function csa($haystack, $needle, $insensitive = 0)
{
	return char_search_a($haystack, $needle, $insensitive);
}

/**
 * @param $haystack
 * @param $needle
 * @param int $insensitive
 * @return bool
 */
function char_search_a($haystack, $needle, $insensitive = 1)
{

	if (is_array($haystack)) {
		//dprint("Got array in Char Search ");
		//dprintr($haystack);
	}

	if (is_object($haystack)) {
		$v = debugBacktrace(true);
		log_log("error", $v);
	}
	if ($insensitive) {
		return (false !== stristr($haystack, $needle)) ? true : false;
	} else {
		return (false !== strpos($haystack, $needle)) ? true : false;
	}
}

/**
 * @param $string
 * @param $needle
 * @return string
 */
function strtil($string, $needle)
{
	if (strrpos($string, $needle) !== false) {
		return substr($string, 0, strrpos($string, $needle));
	} else {
		return $string;
	}
}

/**
 * @param $string
 * @param $needle
 * @return string
 */
function strtilfirst($string, $needle)
{
	if (strpos($string, $needle)) {
		return substr($string, 0, strpos($string, $needle));
	} else {
		return $string;
	}
}

/**
 * @param $string
 * @param $needle
 * @return string
 */
function strfrom($string, $needle)
{
	if (!csa($string, $needle)) {
		return $string;
	}
	return substr($string, strpos($string, $needle) + strlen($needle));
}

/**
 * @param $array
 * @param $value
 * @return array
 */
function array_push_unique($array, $value)
{
	if (!$array) {
		$array = array();
	}
	foreach ($array as $var) {
		if ($var === $value) {
			return $array;
		}
	}
	$array[] = $value;
	return $array;
}

/**
 * @param $array
 * @param $element
 * @return array|null
 */
function array_remove($array, $element)
{
	$ret = null;
	foreach ($array as $value) {
		if ($value !== $element) {
			$ret[] = $value;
		}
	}
	return $ret;
}

/**
 * @param $haystack
 * @param $needle
 * @param int $insensitive
 * @return bool
 */
function csb($haystack, $needle, $insensitive = 1)
{ /* Char Search Begin */
	return char_search_beg($haystack, $needle, $insensitive);
}

/**
 * @param $haystack
 * @param $needle
 * @param int $insensitive
 * @return bool
 */
function char_search_beg($haystack, $needle, $insensitive = 1)
{
	if (is_array($haystack)) {
		//debugBacktrace();
	}
	if (strpos($haystack, $needle) === 0) {
		return true;
	}
	return false;
}

/**
 * @param $haystack
 * @param $needle
 * @param int $insensitive
 * @return bool
 */
function cse($haystack, $needle, $insensitive = 1)
{ /* Char Search End */
	return char_search_end($haystack, $needle, $insensitive);
}

/**
 * @param $haystack
 * @param $needle
 * @param int $insensitive
 * @return bool
 */
function char_search_end($haystack, $needle, $insensitive = 1)
{
	if (strpos($haystack, $needle) === false) {
		return false;
	}

	if ((strrpos($haystack, $needle) + strlen($needle)) === strlen($haystack)) {
		return true;
	} else {
		return false;
	}
}

/**
 * @param $needle
 * @param $haystack
 * @param bool $strict
 * @return bool
 */
function array_search_bool($needle, $haystack, $strict=false)
{
	if (!$haystack) {
		return false;
	}
	if (array_search($needle, $haystack, $strict) !== false) {
		return true;
	}

	return false;
}

/**
 * @param $var
 * @return bool
 */
function isLicensed($var)
{
	global $gbl, $sgbl, $login, $ghtml;

	if ($var == 'lic_client') {
		return true;
	}

	$lic = $login->getObject('license')->licensecom_b;
	if (!isset($lic->$var)) {
		return false;
	}
	return isOn($lic->$var);
}

/**
 * @param $class
 * @return bool
 */
function is_composite($class)
{
	return false;
}

/**
 * @param $class
 * @return array
 */
function get_composite($class)
{
	return array(null, null, $class);
}

// Set unlicensed to Unlimited usage
/**
 *
 */
function setLicenseTodefault()
{
	global $gbl, $sgbl, $login, $ghtml;
	$license = $login->getObject('license');
	$license->parent_clname = $login->getClName();
	$lic = $license->licensecom_b;
	$def = array("maindomain_num" => "Unlimited", "vps_num" => "Unlimited", "pserver_num" => "Unlimited", "client_num" => "Unlimited");
	$list = get_license_resource();
	foreach ($list as $l) {
		$licv = "lic_$l";
		$lic->$licv = $def[$l];
	}
	$license->setUpdateSubaction();
	$license->write();
}

/**
 * @param $list
 * @return mixed
 */
function remove_dot_dot($list)
{
	foreach ($list as $k => $v) {
		if ($v === "." || $v === "..") {
			unset($list[$k]);
		}
	}
	return $list;
}

/**
 * @param $file
 * @return string
 */
function lx_tmp_file($file)
{
	global $gbl, $sgbl, $login, $ghtml;
	$file = expand_real_root($file);
	$n = preg_replace("+/+i", "_", $file);
	return tempnam("$sgbl->__path_tmp/", "lxtmp_$n");
	//return "/tmp/" . $n;
}

/**
 * @param $list
 * @return array
 */
function lx_array_keys($list)
{
	if (!$list) {
		$list = array();
	}
	return array_keys($list);
}

/**
 * @param $full
 * @param $need
 * @return mixed
 */
function array_filter_key($full, $need)
{
	if (!$need) {
		return $full;
	}

	foreach ($full as $key => $value) {
		if (array_search_bool($key, $need)) {
			$ret[$key] = $value;
		}
	}
	return $ret;
}

/**
 *
 */
function gethtmllibversion()
{
}

/**
 * @param $var
 * @return bool
 */
function isOn($var)
{
	return (($var === 'on') || ($var === 'On')) ? true : false;
}

// Function that is called to test whther the remote server is working fine or not. Used while adding.
/**
 * @return bool
 */
function test_remote_func()
{
	return true;
}

/**
 * @param $mess
 * @param int $id
 */
function log_clicks($mess, $id = 1)
{
	global $gbl, $sgbl, $login, $ghtml;
	if (!if_demo()) {
		return;
	}
	$ip = $gbl->c_session->ip_address;
	$mess = trim($mess);
	$file = "__path_program_root/log/clicks";
	lfile_put_contents($file, "$id: $ip: " . @date("H:i:s M/d/Y") . ": $mess\n", FILE_APPEND);
}

// Version Comparison Returns 1 if version1 is greater, and -1 if version2 is greater.
/**
 * @param $version1
 * @param $version2
 * @return int
 */
function version_cmp($version1, $version2)
{
	$l1 = explode(".", $version1);
	$l2 = explode(".", $version2);

	for ($i = 0; $i < 3; $i++) {
		if ($l2[$i] === $l1[$i]) {
			continue;
		}
		if ($l1[$i] > $l2[$i]) {
			return 1;
		}
		if ($l1[$i] < $l2[$i]) {
			return -1;
		}
	}
}

/**
 * @param $version1
 * @param $version2
 * @return int
 */
function app_version_cmp($version1, $version2)
{
	$l1 = explode(".", $version1);
	$l2 = explode(".", $version2);

	for ($i = 0; $i < 4; $i++) {
		if (!isset($l2[$i])) {
			$l2[$i] = 0;
		}
		if (!isset($l1[$i])) {
			$l1[$i] = 0;
		}
	}

	//dprintr($l1);
	//dprintr($l2);

	for ($i = 0; $i < 4; $i++) {
		if ($l2[$i] === $l1[$i]) {
			continue;
		}
		if ($l1[$i] > $l2[$i]) {
			return 1;
		}
		if ($l1[$i] < $l2[$i]) {
			return -1;
		}
	}
}

/**
 * @param $file
 * @param $string
 */
function fput_content_with_lock($file, $string)
{
	lfile_put_contents($file, $string);
}

/**
 * @param $list
 * @param $rule
 * @return null
 */
function filter_object_list($list, $rule)
{
	$nlist = null;
	foreach ((array) $list as $o) {
		if ($o->eeval($rule)) {
			$nlist[$o->nname] = $o;
		}
	}
	return $nlist;
}

/**
 * @param $var
 * @return bool
 */
function is_assoc_array($var)
{
	if (!is_array($var)) {
		return false;
	}
	return array_keys($var) !== range(0, sizeof($var) - 1);
}

/**
 * @param $ol
 * @param null $key
 * @param null $val
 * @return array|void
 */
function get_namelist_from_objectlist($ol, $key = null, $val = null)
{
	if (!$ol) {
		return;
	}

	$name = array();
	if (!$key) {
		$key = "nname";
	}

	if ($val === null) {
		$val = $key;
	}

	foreach ($ol as $o) {
		if (!is_object($o)) {
			debugBacktrace();
		}
		$name[$o->$key] = $o->display($val);
	}
	return $name;
}

/**
 * @param $ar
 * @return mixed
 */
function convert_to_associate($ar)
{
	foreach ($ar as $k => $v)  $ret[$v] = $v;
	return $ret;
}

/**
 * @param $ol
 * @param null $key
 * @param null $val
 * @return array
 */
function get_namelist_from_arraylist($ol, $key = null, $val = null)
{
	$name = array();
	if (!$key) {
		$key = "nname";
	}

	if ($val === null) {
		$val = $key;
	}

	foreach ((array) $ol as $o) $name[$o[$key]] = $o[$val];

	return $name;
}

/**
 * @param $used
 * @param $priv
 * @return bool
 */
function isQuotaGreaterThanOrEq($used, $priv)
{
	if (is_unlimited($priv)) {
		return false;
	}
	if (is_unlimited($used)) {
		return true;
	}
	if (isOn($priv)) {
		return false;
	}
	if (isOn($used)) {
		return true;
	}
	return ($used >= $priv) ? true : false;
}

/**
 * @param $used
 * @param $priv
 * @return bool
 */
function isQuotaGreaterThan($used, $priv)
{
	if (is_unlimited($priv)) {
		return false;
	}
	if (is_unlimited($used)) {
		return true;
	}
	if (isOn($priv)) {
		return false;
	}
	if (isOn($used)) {
		return true;
	}
	return ($used > $priv) ? true : false;
}

/**
 * Checks if a resource is no limited.
 * @author Anonymous <anonymous@lxcenter.org>
 * @author Ángel Guzmán Maeso <angel.guzman@lxcenter.org>
 * @param string $resource The name resource property to check
 * @return boolean True if $resource is 'unlimited' or 'na' string
 */
function is_unlimited($resource)
{
	return strtolower($resource) === 'unlimited' || strtolower($resource) === 'na';
}

/**
 * @throws lxException
 */
function if_demo_throw()
{
	if (if_demo()) {
		throw new lxException ("demo", '');
	}
}

/**
 * @return mixed
 */
function if_demo()
{
	global $gbl, $sgbl, $g_demo;
	return $g_demo;
}

/**
 *
 */
function lx_phpdebug()
{
	global $gbl, $sgbl;

	if ($sgbl->dbg <= 0) {
		return;
	}
	if (!lfile_exists("/tmp/.php_debug")) {
		return;
	}

	$fp = lfopen("/tmp/.php_debug", "r");
	$s = fgets($fp, 1024);
	fclose($fp);

	$s = preg_replace("/--noraise /i", "", $s);
	$arr = parse_url($s);
	parse_str($arr['query'], $out);

	$ghtml->print_input("hidden", "start_debug", "1");
	$ghtml->print_input("hidden", "debug_port", $out['debug_port']);
	$ghtml->print_input("hidden", "debug_text_mode", "1");
	$ghtml->print_input("hidden", "debug_no_cache", "1095197145");
}

/**
 * @param $arglist
 * @return Remote
 */
function create_simpleObject($arglist)
{
	$obj = new Remote();

	foreach ($arglist as $k => $v) {
		$obj->$k = $v;
	}
	return $obj;
}

/**
 *
 */
function lx_sync()
{
	global $gbl, $sgbl, $login;
	$login->was();
}

/**
 * @return string
 */
function get_current_file()
{
	$n = basename(dirname($_SERVER['PHP_SELF']));
	return $n;
}

/**
 * @param $a
 * @param null $pref
 * @return array
 */
function array_flatten($a, $pref = null)
{
	$ret = array();
	foreach ($a as $i => $j) {
		if (is_array($j)) {
			$ret = array_merge($ret, array_flatten($j, "$pref$i"));
		} else {
			$ret["$pref$i"] = $j;
		}
	}
	return $ret;
}

/**
 * @param null $v
 */
function get_general_image_path($v = null)
{

	return add_http_host("/img/general/$v");
}

/**
* @return void
* @param
* @param
* @desc  part of the getting-image-through-http (to enable caching) madness.
*/
function add_http_host($elem)
{
	return $elem;
}

/**
 * @param null $path
 * @return string
 */
function get_image_path($path = null)
{
	global $gbl, $sgbl, $login;

	//Return path of the encrypted images in the deployment version.

	return "/img/image/{$login->getSpecialObject('sp_specialplay')->icon_name}/$path";
}

/**
 * @param $length
 * @return string
 */
function randomString($length)
{
	$randstr = '';
	$chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
	for ($rand = 0; $rand <= $length; $rand++) {
		$random = rand(0, count($chars) - 1);
		$randstr .= $chars[$random];
	}
	return $randstr;
}

/**
 * @param $traceArr
 * @return string|void
 */
function DBG_GetBacktrace($traceArr)
{
	if ($sgbl->dbg < 0) {
		return;
	}

	$s = '';
	$MAXSTRLEN = 64;

	$s = '<pre align=left>';
	array_shift($traceArr);
	$tabs = sizeof($traceArr) - 1;
	foreach ($traceArr as $arr) {
		for ($i = 0; $i < $tabs; $i++) {
			$s .= ' &nbsp; ';
		}

		$tabs -= 1;
		$s .= '<font face="Courier New,Courier">';
		if (isset($arr['class'])) {
			$s .= $arr['class'] . '.';
		}
		$args = array();
		foreach ((array) $arr['args'] as $v) {
			if (is_null($v)) {
				$args[] = 'null';
			} else {
				if (is_array($v)) {
					$args[] = 'Array[' . sizeof($v) . ']';
				} else {
					if (is_object($v)) {
						$args[] = 'Object:' . get_class($v);
					} else {
						if (is_bool($v)) {
							$args[] = $v ? 'true' : 'false';
						} else {
							$v = (string) @$v;
							$str = htmlspecialchars(substr($v, 0, $MAXSTRLEN));
							if (strlen($v) > $MAXSTRLEN) $str .= '...';
							$args[] = "\"" . $str . "\"";
						}
					}
				}
			}
		}
		$s .= $arr['function'] . '(' . implode(',
		', $args) . ')</font>';
		$Line = (isset($arr['line']) ? $arr['line'] : "unknown");
		$File = (isset($arr['file']) ? $arr['file'] : "unknown");
		$s .= sprintf("<font color=#808080 size=-1> # line
		%4d, file: <a href=\"file:/%s\">%s</a></font>", $Line, $File, $File);
		$s .= "\n";
	}
	$s .= '</pre>';
	return $s;
}

/**
 * @param $b
 * @return string
 */
function getInfo($b)
{
	if (is_object($b) && is_subclass_of($b, 'lxclass')) {
		return $b->get__table() . ':' . $b->nname;
	} else {
		return $b;
	}
}

/**
 * @return null|string|void
 */
function backtrace_once()
{
	global $gbl, $sgbl, $login, $ghtml;
	if ($sgbl->dbg < 2) {
		return;
	}

	$v = debug_backtrace();
	$count = 0;
	$string = null;
	foreach ($v as $q) {
		$count++;
		if ($count === 1) {
			continue;
		}

		if ($count > 2) break;
		if ($count === 2 && (basename($q['file']) === 'sqlite.php')) {
			return null;
			continue;
		}
		$string .= $q['file'] . ":" . $q['line'] . ": " . $q['function'] . '(';
		if (isset($q['args'])) {
			foreach ($q['args'] as $a) {
				if (is_array($a)) {
					foreach ($a as $b) {
						if (is_array($b)) {
							foreach ($b as $c) {
								$string .= ', ' . getInfo($c);
							}
						} else {
							$string .= ', ' . getInfo($b);
						}
					}
				} else {
					$string .= $a;
				}
			}
		}
		$string .= ")<br>\n";
	}

	return $string;
}

/**
 * @param bool $flag
 * @return null|string|void
 */
function debugBacktrace($flag = false)
{
	global $gbl, $sgbl, $login, $ghtml;
	$string = null;

	if ($sgbl->dbg < 2) {
		return;
	}
	$v = debug_backtrace();
	foreach ($v as $q) {
		$string .= $q['file'] . ":" . $q['line'] . ": " . $q['function'] . '(';
		if (isset($q['args'])) {
			foreach ($q['args'] as $a) {
				if (is_array($a)) {
					foreach ($a as $b) {
						if (is_array($b)) {
							foreach ($b as $c) {
								$string .= ', ' . getInfo($c);
							}
						} else {
							$string .= ', ' . getInfo($b);
						}
					}
				} else {
					if (is_string($a)) {
						$string .= $a;
					}
				}
			}
		}
		$string .= ")<br>\n";
	}
	if ($flag) {
		return $string;
	}

	dprintr($string);
}

/**
 * @param $str
 * @return mixed|string
 */
function lx_strip_tags($str)
{
	$nstr = strip_tags($str);

	$nstr = preg_replace("/\s+/", " ", $nstr);
	return $nstr;
}

/**
 * Class Language_Mes
 */
class Language_Mes
{

}

/**
 * @return string
 */
function get_language()
{
	global $gbl, $sgbl, $login, $ghtml;
	if (is_object($login) && isset($login->getSpecialObject('sp_specialplay')->language)) {
		$lan = $login->getSpecialObject('sp_specialplay')->language;
	} else {
		$lan = 'en';
	}
	return $lan;
}

/**
 * @return mixed|string
 */
function get_charset()
{
	$lang = get_language();
	$charset = @ lfile_get_contents("lang/$lang/charset");
	$charset = trim($charset);
	return $charset;
}

/**
 *
 */
function initLanguageCharset()
{
	global $gbl, $sgbl, $login, $ghtml;
	$lan = get_language();
	$charset = @ lfile_get_contents("lang/$lan/charset");
	$charset = trim($charset);
	print("<head>");
	if ($charset) {
		print("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\"  />");
	} else {
		print("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"  />");
	}
}

/**
 *
 */
function initLanguage()
{
	global $gbl, $sgbl, $login, $ghtml;
	global $g_language_mes, $g_language_desc;

	$language = get_language();

		if (lxfile_exists("lang/$language/messagelib.php")) {
			include_once("lang/$language/messagelib.php");
		} else {
			include_once("lang/en/messagelib.php");
		}

		if (lxfile_exists("lang/$language/langfunctionlib.php")) {
			include_once("lang/$language/langfunctionlib.php");
		} else {
			include_once("lang/en/langfunctionlib.php");
		}

		if (lxfile_exists("lang/$language/langkeywordlib.php")) {
			include_once("lang/$language/langkeywordlib.php");
		} else {
			include_once("lang/en/langkeywordlib.php");
		}

		if (lxfile_exists("lang/$language/desclib.php")) {
			include_once("lang/$language/desclib.php");
		} else {
			include_once("lang/en/desclib.php");
		}

	include_once("htmllib/lib/commonmessagelib.php");

	date_default_timezone_set('America/New_York');
	$g_language_mes = new Language_Mes();
	$g_language_mes->__information = $__information;
	$g_language_mes->__emessage = $__emessage;
	$g_language_mes->__keyword = $__keyword;
	if(!isset($__help))  $__help = NULL;
	$g_language_mes->__help = $__help;
	if(!isset($__helpvar))  $__helpvar = NULL;
	$g_language_mes->__helpvar = $__helpvar;
	$g_language_mes->__commonhelp = $g_commonhelp;

	$g_language_desc = new Remote();
	$g_language_desc->__description = $__description;

}

/**
 * @param $errno
 * @param $errstr
 * @param $file
 * @param $line
 */
function lx_error_handler($errno, $errstr, $file, $line)
{
	global $gbl, $sgbl, $login, $ghtml;
	global $last_error;

	$last_error = $errstr;

	if ($sgbl->dbg < 0) {
		return;
	}

	if (error_reporting() === 0) {
		return;
	}

	static $error = "";
	$pos = "$file:$line: $errstr";
	$error .= $pos . "\n";
	lfile_put_contents($sgbl->__var_error_file, $error);

	dprint("\n### PHP Error detected\n");
	dprint("### Notice: $errstr\n");
		dprint("### File:$file\n");
		dprint("### Line number: $line\n");
	dprint("### End PHP Error information\n\n");
}

/**
* @return void
* @param
* @param
* @desc Truly random and insane way to encrypt the image/form names so that hackers won't easily understand teh program structure, (which is clearly visible in these names...)
*/
function createEncName($name)
{
	return $name;
}

/**
 * @param $unenc
 * @param $enc
 * @return bool
 */
function check_password($unenc, $enc)
{
	//Old Stuff Not reached
	if (crypt($unenc, $enc) === $enc) {
		return true;
	}

	return false;
}

/**
 * @param $e
 */
function lx_exception_handler($e)
{
	global $gbl, $sgbl, $login, $ghtml;

	print("Notice : The resource you have requested doesn't exist. The server returned the error message: <br> ");

	print(" {$e->getMessage()} $e->variable $e->value ");
	print("<br>\n\n");

	if ($sgbl->dbg <= 0) {
		return;
	}
	$tr = $e->getTrace();
	$trace = "";
	foreach ($tr as $a) {
		$trace .= $a["file"] . ":" . $a["line"] . ":";
		if (isset($a["class"])) {
			$trace .= $a["class"] . ":";
		}
		$trace .= $a["function"] . "(";
		$trace .= ")\n";
	}
	lfile_put_contents($sgbl->__var_error_file, $trace);
}

/**
 * @param $class
 * @param $client
 * @param $pass
 * @return bool
 */
function check_raw_password($class, $client, $pass)
{
	//return true;
	
	if (!$class || !$client || !$pass) {
		return false;
	}

	$rawdb = new Sqlite(null, $class);
        $password = $rawdb->rawquery("select password from ".$rawdb->real_escape_string($class)." where nname = '".$rawdb->real_escape_string($client)."'");
	$enp = $password[0]['password'];
	
	if ($enp && check_password($pass, $enp)) {
		return true;
	}
	return false;
	//$rawdb->close();
}

/**
* @return void
* @param
* @param
* @desc  Checks if the client is disabled and exits immedeiately showing a message.
*/
function check_if_disabled_and_exit()
{
	global $gbl, $sgbl, $login, $ghtml;

	$contact = "administrator";

	if (!$login->isOn('cpstatus')) {
		Utmp::updateUtmp($gbl->c_session->nname, $login, 'disabled');
		$ghtml->print_css_source("/htmllib/css/common.css");

		if ($sgbl->isLxlabsClient()) {
			$ghtml->__http_vars['frm_emessage'] = "This login has been Disabled due to non-payment. Please pay the invoice below, and your account will automatically get enabled.";
			$ghtml->print_message();
			$login->print_invoice();
		} else {
			$ghtml->__http_vars['frm_emessage'] = "This login has been Disabled. Please contact the $contact";
			$ghtml->print_message();
		}

		$gbl->c_session->delete();
		$gbl->c_session->was();
		exit(0);
	}
}

/**
 *
 */
function delete_expired_ssessions()
{
	global $gbl, $sgbl, $login, $ghtml;

	$s_l = $login->getList("ssessionlist");

	foreach ($s_l as $s) {
		if (!is_object($s)) {
			continue;
		}
		$timeout = $s->last_access + $login->getSpecialObject('sp_specialplay')->ssession_timeout;
		dprint($s->nname);
		if ($timeout < time()) {
			$s->delete();
			Utmp::updateUtmp($s->nname, $login, "Session Expired");
		}
	}
}

/**
 * @param $name
 * @param $img
 * @param $imgstr
 * @param $url
 * @param $open
 * @param $help
 * @param $alt
 * @return Tree
 */
function createTreeObject($name, $img, $imgstr, $url, $open, $help, $alt)
{
	static $val;

	$imgstr = str_replace("'", "\'", $imgstr);
	$help = str_replace("'", "\'", $help);
	$alt = str_replace("'", "\'", $alt);
	$img = str_replace("'", "\'", $img);
	$val++;
	$name = $name . $val;
	$tobj = new Tree(null, null, $name);
	$tobj->img = $img;
	$tobj->imgstr = $imgstr;
	$tobj->url = $url;
	$tobj->open = $open;
	$tobj->help = $help;
	$tobj->alt = $alt;
	return $tobj;
}

/**
* @return void
* @param
* @param
* @desc A generic function, that can be used by all programs. Does all the basic login stuff.
*/
function initProgramlib($ctype = null)
{
	global $gbl, $sgbl, $login, $ghtml;

	if ($sgbl->is_this_slave()) {
		print("This is a Slave Server. Operate it at the Master server.\n");
		exit;
	}
	static $var = 0;
	$var++;
	
	$progname = $sgbl->__var_program_name;
	lfile_put_contents($sgbl->__var_error_file, "");
	set_exception_handler("lx_exception_handler");
	//xdebug_disable();
	set_error_handler("lx_error_handler");

	//setcookie("XDEBUG_SESSION", "sess");
	
	if ($var >= 2) {
		dprint("initProgramlib called twice \n <br> ");
	}

	if ($ctype === 'superadmin') {
		$sgbl->__var_dbf = $sgbl->__path_supernode_db;
		$sgbl->__path_admin_pass = $sgbl->__path_super_pass;
		$sgbl->__var_admin_user = $sgbl->__var_super_user;
		$login = new SuperClient(null, null, 'superadmin', 'login', 'forced');
		$login->get();
		return;
	} else if ($ctype === "guest") {
		$login = new Client(null, null, "____________", "guest");
		$login->get();
		return;
	} else if ($ctype != "") {
		
		$login = new Client(null, null, $ctype, "login", "forced");
		$login->get();
		return;
	}
	
	$sessobj = null;
	if ($ghtml->frm_consumedlogin === 'true') {
		$clientname = $_COOKIE["$progname-consumed-clientname"];
		$classname = $_COOKIE["$progname-consumed-classname"];
		$session_id = $_COOKIE["$progname-consumed-session-id"];
		get_login($classname, $clientname);
		$login->__session_id = $session_id;
		$sessobj = $login->getObject('ssession');
	} else {
		
		if (isset($_COOKIE["$progname-session-id"])) {
			$clientname = $_COOKIE["$progname-clientname"];
			$classname = $_COOKIE["$progname-classname"];
			$session_id = $_COOKIE["$progname-session-id"];
			if ($classname === 'superclient') {
				$sgbl->__var_dbf = $sgbl->__path_supernode_db;
				$sgbl->__path_admin_pass = $sgbl->__path_super_pass;
				$sgbl->__var_admin_user = $sgbl->__var_super_user;
			}

			if ($classname === 'slave') {
				$sgbl->__var_dbf = $sgbl->__path_slave_db;
			}
			if ($classname) {
				get_login($classname, $clientname);
				$login->__session_id = $session_id;
				$sessobj = $login->getObject('ssession');
			}
		}
	}
	
	if (!$sessobj || $sessobj->dbaction === 'add') {
		if ($ghtml->frm_ssl) {
			$ssl = unserialize(base64_decode($ghtml->frm_ssl));
			$string = $ssl['string'];
			$ssl_param = $ssl['ssl_param'];
			$encrypted_string = base64_decode($ssl['encrypted_string']);
			if (!$string || !checkPublicKey($string, $encrypted_string)) {
				print("SSL Connection Failed <br> \n");
				exit;
			}
			$class = 'client';
			$clientname = 'admin';
			get_login($class, $clientname);
			do_login($class, $clientname, $ssl_param);
			$sessobj = $gbl->c_session;
			$sessobj->write();
			$sessobj->dbaction = 'clean';
		}
	}
	
	//get_savedlogin($classname, $clientname);
	//print_time('login_get', "Login Get");
	//dprintr($login);

//avoid some php warnings
if (isset($login)) {
	$gbl->client = $login->nname;
	$gbl->client_ttype = $login->cttype;
}

	//dprintr($login->hpfilter);

	// This means the session object got created fresh.
	if (!$sessobj || $sessobj->dbaction === 'add') {
		dprint("no session id");
		clear_all_cookie();
		$ghtml->print_redirect_self("/login/");
	}
	
	$gbl->c_session = $sessobj;

	if ($login->getClName() !== $sessobj->parent_clname) {
		dprint_r($login->ssession_l);
		dprint(" <br> $session_id <br> <br> <br> ");
		print("Session error! Login again.");
		clear_all_cookie();
		$ghtml->print_redirect_self("/login/?frm_emessage=sessionname_not_client");
	}
	
	$gen = $login->getObject('general')->generalmisc_b;

	if (!$gen->isOn('disableipcheck') && $_SERVER['REMOTE_ADDR'] != $sessobj->ip_address) {
		$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		log_message("An attempt to hack from $hostname (" . $_SERVER['REMOTE_ADDR'] . ") with sess $sessobj->nname, session ip: $sessobj->ip_address");

		if ($gen->isOn('disableipcheck')) {
		} else {
			clear_all_cookie();
			if ($gbl->c_session->ssl_param) {
				$sessobj->delete();
				$sessobj->write();
				$ghtml->print_redirect("{$gbl->c_session->ssl_param['backurl']}&frm_emessage=ipaddress_changed_amidst_session");
			} else {
				$ghtml->print_redirect_self("/login/?frm_emessage=ipaddress_changed_amidst_session");
			}
		}
	}
	
	if (intval($login->getSpecialObject('sp_specialplay')->ssession_timeout) <= 100) {
		$login->getSpecialObject('sp_specialplay')->ssession_timeout = 100;
		$login->setUpdateSubaction();
	}

	$timeout = $sessobj->last_access + $login->getSpecialObject('sp_specialplay')->ssession_timeout;
	$sessobj->timeout = $timeout;
	//$timeout  =  $sessobj->last_access + 4;
	$sessobj->last_access = time();
	$sessobj->setUpdateSubaction();
	
	if ($sessobj->auxiliary_id) {
		$aux = new Auxiliary(null, null, $sessobj->auxiliary_id);
		$aux->get();
		$login->__auxiliary_object = $aux;
	}

	if (time() > $timeout) {
		$sessobj->delete();
		$sessobj->write();
		//print("session error timeout");
		if ($gbl->c_session->ssl_param) {
			$ghtml->print_redirect("{$gbl->c_session->ssl_param['backurl']}&frm_emessage=session_timeout");
		} else {
			$ghtml->print_redirect_self("/login/?frm_emessage=session_timeout");
		}
	}
	
	addToUtmp($sessobj, 'update');
}

/**
 *
 */
function clear_all_cookie()
{
	global $gbl, $sgbl, $login, $ghtml;
	$progname = $sgbl->__var_program_name;

	$search = $progname;
	if ($ghtml->frm_consumedlogin === 'true') {
		$search .= "-consumed";
	}

	foreach ($_COOKIE as $k => $v) {
		if (csb($k, $search)) {
			setcookie($k, "", time() - 360000000);
		}
	}
}

/**
 * @param $string
 * @param $encrypted_string
 * @return bool
 */
function checkPublicKey($string, $encrypted_string)
{
	$res = lfile_get_contents("__path_program_root/etc/authorized_keys");
	$public_key = $res;
	$pubkey_res = openssl_get_publickey($public_key);
	openssl_public_decrypt($encrypted_string, $decrypted_string, $public_key);
	if ($decrypted_string === $string) {
		return true;
	}

	return false;
}

/**
 * @param $object
 * @param $ssl_param
 * @param $consuming_parent
 * @return Ssession
 */
function initSession($object, $ssl_param, $consuming_parent)
{
	global $gbl, $sgbl, $login, $ghtml;
	$progname = $sgbl->__var_program_name;

	$session = randomString(50);

	//clear_all_cookie();
	// Making cookie persistent, otherwise IE will not pass it to new windows. Needed in the file selector. Set the expiration to 10 years in the future. Needed for brain damaged ie, which cannot recognize server time.
	$cookietime = time() + 24 * 60 * 60 * 365 * 80;
	//$cookietime = 0;
	header('P3P: CP="CAO PSA OUR"');

	$ckstart = $progname;

	if ($consuming_parent) {
		$ckstart .= "-consumed";
	}

	if ($object->isAuxiliary()) {
		$name = $object->__auxiliary_object->nname;
		$class = $object->__auxiliary_object->getClass();
	} else {
		$name = $object->nname;
		$class = $object->getClass();
	}

	setcookie("$ckstart-clientname", $name, $cookietime, '/');
	setcookie("$ckstart-classname", $class, $cookietime, '/');
	setcookie("$ckstart-session-id", $session, $cookietime, '/');

	dprint("Set cookies\n");

	$hostname = $_SERVER['REMOTE_ADDR'];

	$sessobj = new Ssession(null, null, $session);
	$sessa['nname'] = $session;
	$sessa['ip_address'] = $_SERVER['REMOTE_ADDR'];
	$sessa['cttype'] = $object->getLoginType();
	$sessa['hostname'] = $hostname;
	$sessa['tsessionid'] = randomString(30);
	// Login time is set to null. This is set to the correct time inside display.php. This allows us to figure out the first run of display.php.
	$sessa['logintime'] = time();
	$sessa['ssession_vars'] = array();
	$sessa['http_vars'] = array();
	$sessa['parent_clname'] = $object->getClName();
	$sessa['consuming_parent'] = $consuming_parent;
	$sessa['auxiliary_id'] = $object->getAuxiliaryId();
	$sessa['ssl_param'] = $ssl_param;

	if (intval($object->getSpecialObject('sp_specialplay')->ssession_timeout) <= 100) {
		$timeout = 100;
	} else {
		$timeout = $object->getSpecialObject('sp_specialplay')->ssession_timeout;
	}

	$sessa['timeout'] = time() + $timeout;
	$sessa['last_access'] = time();
	$sessobj->create($sessa);
	return $sessobj;
}

/**
 * @param $classname
 * @param $cgi_clientname
 * @param null $ssl_param
 */
function do_login($classname, $cgi_clientname, $ssl_param = null)
{
	global $gbl, $sgbl, $login, $ghtml;
	$url = "/display.php?frm_action=show";

	$progname = $sgbl->__var_program_name;

	if (!$classname) {
		$classname = 'client';
	}

	$sessobj = initSession($login, $ssl_param, null);

	$gbl->c_session = $sessobj;
	$login->addToList("ssession", $sessobj);
	$login->createSessionProperties();
	addToUtmp($sessobj, 'add');

	if ($ghtml->frm_extra_var) {
		$extra = unserialize(base64_decode($ghtml->frm_extra_var));
		$gbl->setSessionV('extra_var', $extra);
	}
	//This is not the way. You have to periodically scan the utmp and delete eveyrthing that had expired.
	delete_expired_ssessions();
}

/**
 * @return bool
 */
function ifSplashScreen()
{
	global $gbl, $sgbl, $login, $ghtml;
	if (array_search_bool(strtolower($ghtml->frm_action), array('show', 'updateform', 'addform', 'continue'))) {
		return true;
	}
	return false;
}

/**
 * @return bool
 */
function isModifyAction()
{
	global $gbl, $sgbl, $login, $ghtml;
	if (array_search_bool(strtolower($ghtml->frm_action), array('show', 'updateform', 'addform', 'continue', 'list')) || array_search_bool($ghtml->frm_subaction, array("download"))) {
		return false;
	}
	return true;
}

/**
 * @param $object
 * @return string
 */
function lget_class($object)
{
	return strtolower(get_class($object));
}

/**
 *
 */
function exit_programlib()
{
	global $gbl, $sgbl, $login, $ghtml;

	$refer = $gbl->getSessionV("lx_http_referer");
	$current_query_string = $ghtml->get_get_from_post(array('frm_ev_list'), $ghtml->__http_vars);
	$cur_url = $_SERVER['PHP_SELF'] . "?" . $current_query_string;

	if ($refer != $cur_url) {
		$gbl->setSessionV("lx_http_referer_parent", $refer);
		$gbl->setSessionV("lx_http_referer", $cur_url);
	}

	if ($ghtml->frm_hpfilter) {
		$gbl->setSessionV("lx_hpfilter", $ghtml->frm_hpfilter);
	}

	$gbl->setHistory();
	$gbl->c_session->was();
}

/**
 * @param null $cttype
 * @return array|int|null
 */
function getAllclients($cttype = null)
{
	$db = new Sqlite(null, 'client');

	$list = $db->getTable(array('nname', 'cttype'));

	return $list;
}

/**
 *
 */
function delete_login()
{
	global $gbl, $sgbl, $login, $ghtml;
	$class = lget_class($login);
}

/**
 * @return void
 * @param
 * @param
 * @desc  Function can be removed
 */
function save_login()
{
	return;
}

/**
 * @param $var
 * @return bool
 */
function isLocalhost($var)
{
	if ($var && $var !== "localhost") {
		return false;
	}
	return true;
}

/**
 * @param $classname
 * @param $clientname
 * @return int
 */
function get_savedlogin($classname, $clientname)
{
	global $gbl, $sgbl, $login, $ghtml;

	$path = "$sgbl->__path_program_etc/$classname:$clientname";

	if (file_exists($path)) {
		$login = unserialize(lfile_get_contents($path));
		if (!$login) {
			return 0;
		}

		$login->__parent_o = null;

		return 1;
	}
	return 0;
}

/**
 * @param $classname
 * @param $clientname
 * @return mixed
 */
function get_login($classname, $clientname)
{
	global $gbl, $sgbl, $login, $ghtml;

	if (!$classname) {
		print("There is no class. Login Error\n");
		exit;
	}

	$object = new $classname(null, null, $clientname, 'login');

	$ret = $object->get();

	if ($classname === 'auxiliary') {
		$login = $object->getParentO();
		$login->__auxiliary_object = $object;
	} else {
		$login = $object;
	}

	$login->__parent_o = null;
	return $ret;
}

/**
 * @param $word
 * @return mixed
 */
function create_name($word)
{
	$word = str_replace("_", $word);
	return $word;
}

/**
* @return
* @param
* @param
* @desc  Remove unsavoury characters from a string so that it can be used as a variable.
*/
function fix_nname_to_be_variable($var)
{
	if (!$var) {
		return;
	}
	if (is_numeric($var[0])) {
		$var[0] = 'd';
	}
	$var = strtolower($var);
	return preg_replace("/[-\W ]/i", "_", $var);
}

/**
 * @param $var
 * @return mixed|void
 */
function fix_nname_to_be_variable_without_lowercase($var)
{
	if (!$var) {
		return;
	}
	if (is_numeric($var[0])) {
		$var[0] = 'd';
	}
	return preg_replace("/[-\W ]/i", "_", $var);
}

/**
 * @param $stuff
 * @return mixed
 */
function get_description($stuff)
{
	if (is_object($stuff)) {
		$class = lget_class($stuff);
	} else {
		$class = $stuff;
	}

	$descr = get_classvar_description($class);
	return $descr[2];
}

/**
 * @param $class
 * @param null $var
 * @return mixed|null
 */
function get_classvar_description($class, $var = null)
{
	global $gbl, $sgbl, $login, $ghtml;

	global $g_language_desc;
	global $g_language_mes;

	//$var = fix_nname_to_be_variable($var);
	if (csb($var, "__")) {
		$dvar = $var;
	} else {
		if ($var) {
			$dvar = "__desc_$var";
		} else {
			$dvar = "__desc";
		}
	}

	$rvar = strfrom($dvar, "__desc_");
	$rvar = strfrom($rvar, "__acdesc_");

	$class = strtolower($class);
	$ret = get_real_class_variable($class, $dvar);

	if (!$ret) {
		return null;
	}
	$ret['help'] = $ret[2];
	if (cse($dvar, "_o") || cse($dvar, "_l")) {
		return $ret;
	}
	if (!$g_language_desc) {
		return $ret;
	}

	/*
	if ($login->getSpecialObject('sp_specialplay')->isCoreLanguage()) {
		return $ret;
	}
*/

	$k = trim($ret[2], "_\n ");
	if (isset($g_language_desc->__description[$k])) {
		$ret[2] = $g_language_desc->__description[$k][0];
		if (isset($g_language_mes->__helpvar[$rvar])) {
			$ret['help'] = $g_language_mes->__helpvar[$rvar];
		} else if (isset($g_language_mes->__help[$dvar])) {
			$ret['help'] = $g_language_desc->__help[$k];
		} else {
			$ret['help'] = $ret[2];
		}
	}
	return $ret;
}

/**
 * @param $var
 * @return mixed
 */
function get_var_help($var)
{
	global $g_language_mes, $g_language_desc;
	$cvar = strtolower($var);
	if (isset($g_language_mes->__help[$cvar])) {
		return $g_language_mes->__help[$cvar];
	}
	return $var;
}

/**
 * @param $class
 * @param $var
 * @return mixed|null
 */
function get_real_class_variable($class, $var)
{
	//$var = fix_nname_to_be_variable($var);
	//list($iclass, $mclass, $rclass) = get_composite($class);
	$rclass = $class;
	$rclass = ucfirst($rclass);

	if (!class_exists($rclass)) {
		dprint("$rclass doesn't exist\n");
		return null;
	}

	$variable = "$rclass::\$" . $var;
	return eval(" if (isset($variable)) { return $variable ; }  ");
}

/**
 * @param $class
 * @param $var
 * @return mixed
 */
function get_class_variable($class, $var)
{
	//list($iclass, $mclass, $rclass) = get_composite($class);
	$rclass = $class;

	$var = fix_nname_to_be_variable($var);
	$class = ucfirst($class);
	/*
	if (csa($class, '-')) {
		debugBacktrace();
		exit;
	}
*/

	$variable = "$class::\$" . $var;
	return eval(" if (isset($variable)) { return $variable ; }  ");
}

/**
 * @param $class
 * @param $var
 * @param $val
 * @return mixed
 */
function set_class_variable($class, $var, $val)
{
	$var = fix_nname_to_be_variable($var);
	$class = ucfirst($class);
	$variable = "$class::\$" . $var;
	return eval(" $variable = \$val ; ");
}

/**
 * @param $n
 * @return string
 */
function createZeroString($n)
{
	$string = "";
	for ($i = 0; $i < $n; $i++) {
		$string .= "0";
	}
	return $string;
}

/**
* @return
* @param
* @param
* @desc  Execs a method inside a class. Passes all the variables to it. See the use of 2 evals.. Check documentation for lx_redefine_func;
*/
function exec_class_method($class, $func)
{
	global $gbl, $sgbl, $login, $ghtml;

	//list($iclass, $mclass, $rclass) = get_composite($class);
	$rclass = $class;

	$class = strtolower($class);

	//Arg getting string is a function that needs $start to be set.
	$start = 2;

	$arglist = array();
	for ($i = $start; $i < func_num_args(); $i++)
	$arglist[] = func_get_arg($i);

	// workaround for the following php bug:
	//   http://bugs.php.net/bug.php?id=47948
	//   http://bugs.php.net/bug.php?id=51329
	class_exists($class);
	// ---
	return call_user_func_array(array($class, $func), $arglist);
}

/**
 * @param $time
 * @return string
 */
function lxgettimewithoutyear($time)
{
	$curd = @ getdate(time());
	$date = @ getdate($time);

	//$month = ($date['month'] === $curd['month'])? "this Month": $date['month'];
	$month = substr($date['month'], 0, 3);

	if ($date['hours'] > 12) {
		$sess = "pm";
		//$hour = $date['hours'] - 12;
		$hour = $date['hours'];
	} else {
		$sess = "am";
		$hour = $date['hours'];
	}

	$minutes = $date['minutes'];

	if ($minutes < 10) {
		$minutes = "0" . $minutes;
	}

	$string = "$hour:$minutes {$date['mday']} $month";

	return $string;
}

/**
 * @param $time
 * @return string
 */
function lxgettime($time)
{
	$curd = @ getdate(time());
	$date = @ getdate($time);

	$year = ($date['year'] === $curd['year']) ? "" : $date['year'];
	//$month = ($date['month'] === $curd['month'])? "this Month": $date['month'];
	$month = substr($date['month'], 0, 3);

	if ($date['hours'] > 12) {
		$sess = "pm";
		//$hour = $date['hours'] - 12;
		$hour = $date['hours'];
	} else {
		$sess = "am";
		$hour = $date['hours'];
	}

	$minutes = $date['minutes'];

	if ($minutes < 10) {
		$minutes = "0" . $minutes;
	}

	$string = $hour . ":" . $minutes . " " . $date['mday'] . " " . $month . " " . $year;

	return $string;
}

/**
 * @param $obj
 * @return int
 */
function if_search_continue($obj)
{
	global $gbl, $sgbl, $ghtml;
	if ($ghtml->iset("frm_searchstring") && !stristr($obj->{$obj->searchVar()}, $ghtml->frm_searchstring)) {
		return 1;
	} else {
		return 0;
	}
}

/**
 * @param $list
 * @return array
 */
function add_select_one($list)
{
	$newlist[] = "--Select One--";
	$newlist = lx_array_merge(array($newlist, $list));
	return $newlist;
}

/**
 * @param $list
 * @return array
 */
function add_disabled($list)
{
	$newlist[] = "--Disabled--";
	$newlist = lx_merge_good($newlist, $list);
	return $newlist;
}

/**
 * @param $value
 * @param $disabled_val
 * @return mixed
 */
function fix_disabled($value, $disabled_val)
{
	if ($value === "--Disabled--") {
		return $disabled_val;
	} else {
		return $value;
	}
}

/**
 * @param $array
 * @param $element
 */
function array_remove_assoc(&$array, $element)
{
	foreach ($array as $key => $value) {
		if ($value === $element)
			unset($array[$key]);
	}
}

/**
 * @param $objectlist
 * @param $variable
 * @param $value
 * @return mixed
 */
function array_remove_object($objectlist, $variable, $value)
{
	foreach ($objectlist as $object) {
		if ($object->$variable != $value) {
			$ret[$object->nname] = $object;
		}
	}
	return $ret;
}

/**
 * @param $pass
 */
function add_superadmin($pass)
{
	global $gbl, $sgbl, $login, $ghtml;

	$client = new SuperClient(null, null, 'superadmin');
	$client->initThisDef();

	$ddb = new Sqlite(null, "superclient");
	if (!$ddb->existInTable("nname", 'superclient')) {
		$res['password'] = crypt($pass);
		$res['cttype'] = 'superadmin';
		$res['cpstatus'] = 'on';
		if (if_demo()) {
			$res['email'] = "admin@example.org";
		}
		$client->create($res);
		$client->write();
	}
}

/**
 * @param $pass
 */
function add_slave($pass)
{
	global $gbl, $sgbl, $login, $ghtml;

	$client = new Slave(null, null, 'slave');
	$client->initThisDef();

	$ddb = new Sqlite(null, "slave");
	if (!$ddb->existInTable("nname", 'slave')) {
		$res['password'] = $pass;
		$res['cttype'] = 'slave';
		if (if_demo()) {
			$res['email'] = "admin@example.org";
		}
		$client->create($res);
		$client->write();
	}
}

/**
 * @param $pass
 */
function init_supernode($pass)
{
	global $gbl, $sgbl, $login, $ghtml;

	sql_main();
	add_superadmin($pass);
}

/**
 * @param $pass
 */
function init_slave($pass)
{
	global $gbl, $sgbl, $login, $ghtml;
	$rm = new Remote();
	$rm->password = crypt($pass);
	lfile_put_contents('__path_slave_db', serialize($rm));
}

/**
 * @param $socket
 */
function lx_socket_read($socket)
{
	//$res=socket_recv($MsgSock,$buffer,1024,0);
}

/**
 * @param $var
 * @param $len
 * @return string
 */
function pad_to_length($var, $len)
{
	$times = round(strlen($var) / $len) + 1;
	$in = str_pad($var, 2048 * $times - strlen($var));
	return $in;
}

/**
 * @param $server
 * @param $port
 * @param $rmt
 * @return mixed
 * @throws
 */
function remote_http_exec($server, $port, $rmt)
{
	$var = base64_encode(serialize($rmt));
	$res = send_to_some_http_server($server, "", $port, $var);

	$res = unserialize(base64_decode($res));

	if ($res->exception) {
		throw $res->exception;
	}
	return $res->ddata;
}

/**
 * @param $raddress
 * @param $socket_type
 * @param $port
 * @param $var
 * @return mixed|string
 */
function send_to_some_http_server($raddress, $socket_type, $port, $var)
{
	global $gbl, $sgbl, $login, $ghtml;

	print_time('server');

	$ch = curl_init("http://$raddress:$port/lbin/remote.php");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "frm_rmt=$var&");
	$totalout = curl_exec($ch);
	dprint(curl_error($ch));
	$totalout = trim($totalout);
	return $totalout;
}

/**
 * @param $user
 * @param $pass
 * @return bool
 */
function check_remote_pass($user, $pass)
{
	global $gbl, $sgbl, $login, $ghtml;
	return check_password($pass, $login->password);
}

/**
 * @param $arg
 * @return mixed
 */
function lfile($arg)
{
	return lx_redefine_func("file", $arg);
}

/**
 * @param $string
 * @param $num
 * @param string $delim
 * @return mixed
 */
function getNthToken($string, $num, $delim = ':')
{
	if (csa($string, $delim)) {
		$_tlist = explode($delim, $string);
		return $_tlist[$num];
	}
	return $string;
}

/**
* @return void
* @param unknown
* @param unknown
* @desc Recurses a dir tree and execs the '$func' on all the files AND the directories.
*/
function recurse_dir($dir, $func, $arglist = null)
{
	$list = lscandir($dir);
	if (!$list) {
		return;
	}
	foreach ($list as $file) {
		if ($file === "." || $file === "..") {
			continue;
		}
		$path = $dir . "/" . $file;
		if (lis_dir($path)) {
			recurse_dir($path, $func, $arglist);
		}
		/// After a successfuul recursion, you have to call the $func on the directory itself. So $func is called whether $path is both directory OR a file.
		$narglist[] = $path;
		foreach ((array) $arglist as $a) {
			$narglist[] = $a;
		}
		call_user_func_array($func, $narglist);
	}
}

/**
 * @param $dir
 * @param $func
 */
function do_recurse_dir($dir, $func)
{
	$list = lscandir($dir);
	if (!$list) {
		return;
	}
	foreach ($list as $file) {
		if ($file === "." || $file === "..") {
			continue;
		}
		$path = $dir . "/" . $file;
		if (lis_dir($path)) {
			do_recurse_dir($path, $func);
		}
		$func($path);
	}
}

/**
 * @param $list
 * @param $class
 * @param $name
 * @return null
 */
function getFromAny($list, $class, $name)
{
	$v = null;
	foreach ($list as $ob) {
		if (!$ob) {
			continue;
		}
		try {
			$v = $ob->getFromList($class, $name);
			return $v;
		} catch (exception $e) {
		}
	}
	return $v;
}

/**
 * @param $list
 * @return mixed
 */
function arrayGetFirstObject($list)
{
	foreach ($list as $k => $v) break;
	return $list[$k];
}

/**
 * @param $objectlist
 * @param $variable
 * @param $value
 * @return null
 */
function array_get_object($objectlist, $variable, $value)
{
	foreach ($objectlist as $object)
		if ($object->$variable === $value) {
			return $object;
		}

	return NULL;
}

/**
 * @return array
 */
function getBasicServiceList()
{
	global $gbl, $sgbl, $login, $ghtml;
	$serv[] = $sgbl->__var_programname_web;
	$descr[$sgbl->__var_programname_web] = "Web Server";
	$serv[] = $sgbl->__var_programname_dns;
	$descr[$sgbl->__var_programname_dns] = "Name Server";
	$serv[] = "lxmail";
	$descr['lxmail'] = "Mail Server";
	return array($serv, $descr);
}

/**
 * @param $listvar
 * @param $mainvar
 * @return null
 */
function getDbvariable($listvar, $mainvar)
{
	static $var;

	if (!$var) {
		$var = unserialize(lfile_get_contents("__path_dbschema"));
	}

	if (isset($var[$listvar][$mainvar])) {
		return $var[$listvar][$mainvar];
	} else {
		dprint("NO schema for $listvar $mainvar <br> \n");
		return null;
	}
}

/**
 * @param $file
 * @param $user
 * @return bool
 */
function check_file_if_owned_by($file, $user)
{
	if (!lxfile_exists($file)) {
		return true;
	}

	if (csa($user, ":")) {
		$ruser = strtil($user, ":");
	} else {
		$ruser = $user;
	}
	$stat = lxfile_stat($file, false);
	$uid = $stat['uid'];
	$name = os_get_user_from_uid($uid);
	if ($name === $ruser) {
		return true;
	}
	log_log("file_check", "$file not owned by $ruser");
	return false;
}

/**
 * @return bool
 */
function critical_change_db_pass()
{
	$c = trim(lfile_get_contents("__path_admin_pass"));
	if ($c == "21232f297a") {
		try {
			change_db_pass();
		} catch (exception $e) {
		}
        return true;
	}

    return false;
}

/**
 * @throws lxException
 */
function change_db_pass()
{
	global $gbl, $sgbl, $login, $ghtml;
	$pass = randomString(10);
	$newp = client::createDbPass($pass);
	$oldpass = lfile_get_contents("__path_admin_pass");
	$username = $sgbl->__var_program_name;
	$sql = new Sqlite(null, "client");
	//$sql->rawQuery("grant all on kloxo.* to kloxo@'localhost' identified by $newp");
	//$sql->rawQuery("grant all on kloxo.* to kloxo@'%' identified by $newp");
	//$return = $sql->setPassword($newp);
	//exec("mysqladmin -u $username -p$oldpass password $newp 2>&1", $out, $return);
	exec("echo 'set Password=Password(\"$newp\")' | mysql -u $username -p$oldpass 2>&1", $out, $return);
	if ($return) {
		$out = implode(" ", $out);
		log_log("admin_error", "mysql change password Failed $out");
		throw new lxException ("could_not_change_admin_pass", '', $out);
	}
	$return = lfile_put_contents("__path_admin_pass", $newp);
	if (!$return) {
		log_log("admin_error", "Admin pass change failed  $last_error");
		throw new lxException ("could_not_change_admin_pass", '', $last_error);
	}
}

/**
 *
 */
function create_database()
{
	global $gbl, $sgbl, $login, $ghtml;
	$flist = parse_sql_data();
	foreach ($flist as $k => $v) {
		create_table_with_drop($k, $v);
	}
}

/**
 * @return array
 */
function get_default_fields()
{
	$fields = array("nname", "parent_clname", "parent_cmlist");
	return $fields;
}

/**
 * @return null
 */
function parse_sql_data()
{
	global $gbl, $sgbl, $argc, $argv;

	static $_quota_var, $_field_var;

	$_return_value = null;

	$majmin = $sgbl->__ver_major_minor;
	$trel = $sgbl->__ver_release;

	$rpath = $sgbl->__path_sql_file . ".lxsql";
	$pathc = $sgbl->__path_sql_file_common . ".lxsql";
	include $rpath;
	include $pathc;

	$string = $gl_sql_string_common . "\n" . $gl_sql_string;
	$string = explode("\n", $string);

	foreach ($string as $__k => $res) {
		$res = trim($res);
		if (!$res)
			continue;

		if (char_search_beg($res, "//")) {
			continue;
		}

		$res = preg_replace('/\s+/', " ", $res);

		if (csb($res, "#")) {
			$vl = explode(" ", $res);
			$name = array_shift($vl);
			$name = strfrom($name, "#");
			$nvl = null;
			$nnvl = null;
			foreach ($vl as $k => $qv) {
				if (csb($qv, "#")) {
					$_t = strfrom($qv, "#");
					$nvl = lx_array_merge(array($nvl, $_quota_var[$_t]));
				} else {
					$nvl[] = $qv;
				}
			}

			foreach ($nvl as $qv) {
				$nnvl[] = "priv_q_" . $qv;
				$nnvl[] = "used_q_" . $qv;
			}

			$_quota_var[$name] = $nvl;
			$g_qvar[$name] = $nnvl;
			$list = get_class_for_table($name);
			foreach ((array) $list as $l) {
				$_quota_var[$l] = $nvl;
				$g_qvar[$l] = $nnvl;
			}

			continue;
		}

		if (csb($res, "%")) {
			$vl = explode(" ", $res);
			$name = array_shift($vl);
			$nnnnvl = null;
			foreach ($vl as $q) {
				if (csb($q, "%")) {
					$nnnnvl = lx_array_merge(array($nnnnvl, $g_var[$q]));
				} else {
					$nnnnvl[] = $q;
				}
			}
			$g_var[$name] = $nnnnvl;
			continue;
		}

		$list = explode(" ", $res);
		$nlist = null;
		foreach ($list as $k => $l) {
			if (csb($l, "%")) {
				$nlist = array_merge($nlist, $g_var[$l]);
			} else {
				$nlist[] = $l;
			}
		}

		//dprintr($list);
		$list = $nlist;
		$nlist = null;
		foreach ($list as $l) {
			if ($l === '__q_var') {
				if (isset($g_qvar[$list[0]])) {
					$nlist = lx_array_merge(array($nlist, $g_qvar[$list[0]]));
				}
			} else {
				$nlist[] = $l;
			}
		}

		$list = $nlist;

		//dprintr($list);

		$name = array_shift($list);

		$fields = lx_array_merge(array(get_default_fields(), $list));
		if (array_search_bool("syncserver", $fields)) {
			$fields[] = 'oldsyncserver';
			$fields[] = 'olddeleteflag';
		}

		$fields = array_unique($fields);
		$_field_var[$name] = $fields;
		$_return_value[$name] = $fields;
	}

	foreach ($_quota_var as &$__tq) {
		$__tq = array_flip($__tq);
	}
	foreach ($_field_var as &$__tq) {
		$__tq = array_flip($__tq);
	}
	$var['quotavar'] = $_quota_var;
	$var['fieldvar'] = $_field_var;

	lfile_put_contents("__path_dbschema", serialize($var));
	return $_return_value;
}

/**
 * @param $__db
 * @param $tbl_name
 * @param $fields
 */
function mssql_do_create_table($__db, $tbl_name, $fields)
{
	foreach ($fields as $f)
		if ($f === 'nname') {
			$f .= ' Primary Key';
		}

	$fields = implode(', ', $fields);
	$query = "create table $tbl_name ($fields)";
	print("Creating table $tbl_name....\n");
	$ret = $__db->rawQuery($query);
	if (!$ret) {
		print("error \n\n\n\n");
		exit;
	}
	$query = "create index parent_clname_$tbl_name on $tbl_name (parent_clname)";
	$__db->rawQuery($query);
}

/**
 * @param $tbl_name
 * @param $list
 */
function create_table_with_drop($tbl_name, $list)
{
	global $gbl, $sgbl, $login, $ghtml;
	$__db = new Sqlite(null, 'sqlite');
	$__db->rawQuery("drop table $tbl_name");
	create_table($__db, $tbl_name, $list);
}

/**
 * @param $__db
 * @param $tbl_name
 * @param $list
 */
function create_table($__db, $tbl_name, $list)
{
	global $gbl, $sgbl, $login, $ghtml;
	$__db = new Sqlite(null, 'sqlite');

	// For mssql you can talk of primary key initially itself nad not wait till the end of the fields.
	if ($sgbl->__var_database_type === 'mysql') {
		$primary_key = "not null";
	} else {
		$primary_key = "PRIMARY KEY";
	}

	foreach ($list as &$__tl) {
		if (csb($__tl, "nname")) {
			$__tl = "$__tl varchar(255) $primary_key";
		} else if (csb($__tl, "ser_")) {
			$__tl = "$__tl longtext";
		} else if (csb($__tl, "coma_")) {
			$__tl = "$__tl text";
		} else if (csb($__tl, "text_")) {
			$__tl = "$__tl longtext";
		} else if (csb($__tl, "parent_cmlist")) {
			$__tl = "$__tl text";
		} else {
			// Why this madness... How can the general type be varchar. What will happen to all the text messages?
			$__tl = "$__tl varchar(255)";
		}
	}

	if ($sgbl->__var_database_type === 'mysql') {
		mysql_do_create_table($__db, $tbl_name, $list);
	} else if ($sgbl->__var_database_type === 'mssql') {
		mssql_do_create_table($__db, $tbl_name, $list);
	} else {
		sqlite_do_create_table($__db, $tbl_name, $list);
	}
}

/**
 * @param $__db
 * @param $tbl_name
 * @param $fields
 */
function sqlite_do_create_table($__db, $tbl_name, $fields)
{
	$fields = implode(', ', $fields);
	//$query = "create table $tbl_name ($fields, primary key (nname (255)));";
	$query = "create table $tbl_name ($fields);";
	print("Creating table $tbl_name....\n");
	$ret = $__db->rawQuery($query);
	//if (!$ret) {
	//print("\nerror: " . sqlite_error_string(sqlite_last_error()) . "\n\n");
	//}
	$query = "insert into $tbl_name (nname) values ('__dummy__dummy__')";
	$__db->rawQuery($query);
	//$query = "create index parent_clname_$tbl_name on $tbl_name (parent_clname (255));"; $__db->rawQuery($query);
}

/**
 * @param $__db
 * @param $tbl_name
 * @param $fields
 */
function mysql_do_create_table($__db, $tbl_name, $fields)
{
	$fields = implode(', ', $fields);
	$query = "create table $tbl_name ($fields, primary key (nname (255)));";
	print("Creating table $tbl_name....\n");
	$ret = $__db->rawQuery($query);
	if (!$ret) {
		//print("\nerror: " . mysql_error() . "\n\n");
	}
	$query = "create index parent_clname_$tbl_name on $tbl_name (parent_clname (255));";
	$__db->rawQuery($query);
}

// string_main();

/**
 * @param $stuff
 * @param null $res
 * @return mixed
 */
function getQuotaListForClass($stuff, $res = null)
{
	if (is_object($stuff)) {
		$ob = $stuff;
	} else {
		$class = $stuff;
		$name = '__dummy__dummy__';
		$ob = new $class(null, null, $name);
		if ($res) {
			$ob->create($res);
		}
		$ob->priv = new Priv(null, null, $name);
	}
	return $ob->getQuotaVariableList();
}

/**
 *
 */
function lx_xdebug_break()
{
	if (function_exists('xdebug_break')) {
		xdebug_break();
	}
}

/**
 * @param $file
 * @return mixed
 */
function remove_extra_slash($file)
{
	return preg_replace("/\/+/", "/", $file);
}

/**
 * @param null $where
 * @throws lxException
 */
function if_demo_throw_exception($where = null)
{
	global $gbl, $sgbl, $login, $ghtml;

	if ($sgbl->dbg < 0) {
		$where = null;
	}

	if (if_demo()) {
		throw new lxException("$where not_allowed_in_demo_version");
	}
}

/**
 * @param $name
 * @return string
 */
function get_package_version($name)
{
	$cont = curl_general_get("http://download.lxcenter.org/download/version/$name");
	return trim($cont);
}

/**
 * @param $cgi_clientname
 * @return string
 */
function getClassFromName($cgi_clientname)
{
	$classname = "client";

	if (csa($cgi_clientname, "@")) {
		$classname = "mailaccount";
	} elseif (csa($cgi_clientname, ".vps")) {
		$classname = "vps";
	} elseif (csa($cgi_clientname, ".vm")) {
		$classname = "vps";
	} elseif (csa($cgi_clientname, ".aux")) {
		$classname = "auxiliary";
	}
	/*
	Domain user doesn't exist anymore....
	else if (csa($cgi_clientname, ".")) {
		$classname = "domain";
	}
*/
	return $classname;
}

