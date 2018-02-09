<?php 
#
# Show the page in browser
#
include_once "htmllib/coredisplaylib.php";

print_time("Start");
display_init();
print_time("Start", "[display.php] Display page after init took");
display_exec();
print_time("Start", "[display.php] Display page after execution took");
dprint("<br><b>[display.php] --- end debug ---</b>");

