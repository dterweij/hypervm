<?php 

// This file starts the upcp/cleanup process

include_once "htmllib/lib/include.php";
include_once "htmllib/lib/updatelib.php";

// Check if we already are running
OS_PID_Instance_Check();
// Check for debug mode (commands.php / backend.php)
DEBUG_Settings();
// Start main process
updatecleanup_main();
