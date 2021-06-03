<?php

/**
 * This file is necessary to include to use all the in-built libraries of /opt/fmc_repository/Reference/Common
 */
require_once '/opt/fmc_repository/Process/Reference/Common/common.php';

/**
 * List all the parameters required by the task
 */ 
function list_args(){
  create_var_def('fw_name', 'String');
  create_var_def('device.0.id', 'Device');
  create_var_def('sleep', 'Integer');
}

$fw_name= $context['fw_name'];
if ($fw_name == ' '){
 task_error("firewall name contain blankspace --->>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> checkkkkk");
}
sleep(3);

/**
 * End of the task do not modify after this point
 */
$message = "";
$warn_set = "false";
if (strlen($fw_name) > 6 ){
 $warn_set = "true";
 $message = " Firwall name is tooooooooooooooooooooooooooooooooo lonnngggggggggggg" . strlen($fw_name);
}
else{
 $message = "name length is great " . strlen($fw_name);
}

sleep(4);

if ($warn_set == "true"){
  task_exit(WARNING, "$message");
}
else{
  task_exit(ENDED, "$message");
}
 
?>