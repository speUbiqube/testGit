<?php
/*
* Available global variables
*  Created: Dec 04, 2018
*  $sms_csp           pointer to csp context to respond to user
*  $sms_sd_ctx        pointer to sd_ctx context to retreive usefull field(s)
*  $SMS_RETURN_BUF    string buffer containing the result
*/

// Generate the Pre-Conf for the router
require_once 'smsd/sms_user_message.php';
require_once 'smsd/sms_common.php';
require_once load_once('nec_ix', 'nec_ix_configuration.php');
require_once "$db_objects";


try
{
  $conf = new nec_ix_configuration($sdid);
  $configuration = $conf->get_staging_conf();

  $result = sms_user_message_add("", SD_CONFIGURATION, $configuration);
  $user_message = sms_user_message_add("", sms_status, OK);
  $user_message = sms_user_message_add_json($user_message, sms_result, $result);

  sms_send_user_message($sms_csp, $sdid, $user_message);
}

catch(Exception $e)
{
  sms_send_user_error($sms_csp, $sdid, $e->getMessage(), $e->getCode());
}

return SMS_OK;·

?>
