<?php
/*
 * Version: $Id: do_checkprovisioning.php 23483 2009-11-03 09:11:46Z tmt $
 * Created: Jun 27, 2008
 * Available global variables
 *  $sms_sd_ctx    pointer to sd_ctx context to retreive usefull field(s)
 *  $sms_sd_info   sd_info structure
 *  $sms_csp       pointer to csp context to send response to user
 *  $sdid
 *  $sms_module    module name (for patterns)
 */

// Verb CHECKPROVISIONING

$script_file = "$sdid:".__FILE__;
require_once 'smsd/sms_common.php';

require_once load_once('oneaccess_lbb', 'provisioning_stages.php');

return require_once 'smsd/do_checkprovisioning.php';

?>
