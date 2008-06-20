<?php
print("Begin ST check for reminders\n");

$IP = realpath( dirname( __FILE__ ) . "/../..");
require_once( "$IP/maintenance/commandLine.inc" );

global $smwgIP;
require_once($smwgIP . '/includes/SMW_Factbox.php');

global $stIP;
require_once($stIP . '/ST_Notify_Assignment.php');

//Let's send reminders
fnRemindAssignees();

print("End ST check for reminders\n");
?>
