#!/bin/sh
#
# Started by init.d/hypervm (hypervm.init.program)
#
#
progname=hypervm
source ../bin/common/function.sh
kill_and_save_pid wrapper;
wrapper_main;
