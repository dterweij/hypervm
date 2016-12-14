#!/bin/sh
#
# Used by hypervm-wrapper.sh
# Started by init.d/hypervm (hypervm.init.program)
#
#
__path_php_path="/usr/local/lxlabs/ext/php/php";
__path_program_root="/usr/local/lxlabs/$progname/";
__path_slave_db="/usr/local/lxlabs/$progname/etc/conf/slave-db.db";
__path_server_path="/usr/local/lxlabs/$progname/sbin/$progname.php";
__path_server_exe="/usr/local/lxlabs/$progname/sbin/$progname.exe";
__path_low_memory_file="/usr/local/lxlabs/$progname/etc/flag/lowmem.flag";

kill_and_save_pid() {
	name=$1
	kill_pid $name;
	usleep 100;
	save_pid $name;
}

save_pid() {
	echo $$ > "$__path_program_root/pid/$name.pid";
}

kill_pid() {
	name=$1
	pid=`cat $__path_program_root/pid/$name.pid`;
	kill -9 $pid &>/dev/null
}

wrapper_main() {

	if [ -f $__path_slave_db ] ; then
		string="slave";
	else 
		string="master";
	fi


	mkdir ../log &>/dev/null
	mkdir ../pid &>/dev/null
	while : ; do

		if [ -f $__path_low_memory_file ] ; then
			/bin/cp $__path_server_exe.core $__path_server_exe &>/dev/null
			chmod 755 $__path_server_exe
			$__path_server_exe $string &>/dev/null
		else 
			$__path_php_path $__path_server_path $string &>/dev/null
	 	fi
			sleep 10;
	done
}

