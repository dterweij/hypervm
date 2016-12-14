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

/*
 *  This is the main startup of HyperVM
 *  Starting communication between master and slave(s) though sockets (forked as child)
 *  Starting Port Monitoring (Executes every 30 mins)
 *  Starting Scavenge Cron (Check time to execute (default is 03:57))
 *  Collecting VM data (forked to the background) (sisinfoc,php)
 *  - Check system load
 *  - Check for intruders and block them
 *  - Check if local mailserver is reachable
 *  - Fetch mail for tickets
 */

include_once "htmllib/lib/include.php";
include_once "htmllib/lib/lxserverlib.php";

OS_PID_Kill_and_Save('hypervm.php');
DEBUG_Settings();

$global_dontlogshell = true;
VM_CollectData();

vpstraffic__openvz::iptables_delete();
vpstraffic__openvz::iptables_create();

system("echo 16536 > /proc/sys/net/ipv4/tcp_max_tw_buckets_ve");
system("echo 256 > /proc/sys/net/ipv4/tcp_max_tw_kmem_fraction");

if($argv[1] === 'master'){
	start_portmonitor();
}

$global_dontlogshell = false;
dprint("Starting Server\n");
startServer();

