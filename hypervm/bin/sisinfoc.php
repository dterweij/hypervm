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
  * this file is called by VM_CollectData() lxserverlib.php <-- hypervm.php
  * and forked to the background as standalone process
  
  * dterweij dec 2016
  */
include_once "htmllib/lib/include.php";

$global_dontlogshell = true;

OS_PID_Instance_Check(); // Check if this file is already running
VM_UsageCollect(); // Collect VM data
OS_System_Load(); // Check system load
OS_LxGuard(); // Check for intruders and block them
OS_Check_SMTP(); // Check if local mailserver is reachable
TICKET_Fetch_Mail(); // Fetch mail for tickets

// ToDo: move this to a lib file
/**
 * @desc Collect XEN and OpenVZ data, traffic, CPU and Memory usage.
 */
function VM_UsageCollect()
{
	if (lxfile_exists("/proc/xen") && lxfile_exists("/usr/sbin/xm")) {
		vps__xen::find_traffic();
		vps__xen::find_cpuusage();
	}

	if (lxfile_exists("/proc/vz")) {
		vps__openvz::find_traffic();
		vps__openvz::find_cpuusage();
		vps__openvz::find_memoryusage();
	}
}




