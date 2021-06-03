<?php
/*
 * Version: $Id: veex_rtu_configuration.php 58927 2012-06-11 15:15:18Z abr $
 * Created: Feb 12, 2009
 */
require_once 'smsd/sms_common.php';
require_once 'smsd/pattern.php';
require_once 'smsd/expect.php';

require_once load_once('veex_rtu', 'adaptor.php');
require_once load_once('veex_rtu', 'veex_rtu_apply_conf.php');
require_once "$db_objects";
class veex_rtu_configuration
{
  var $conf_path; // Path for previous stored configuration files
  var $sdid; // ID of the SD to update
  var $running_conf; // Current configuration of the router
  var $profile_list; // List of managed profiles
  var $previous_conf_list; // Previous generated configuration loaded from files
  var $conf_list; // Current generated configuration waiting to be saved
  var $addon_list; // List of managed addon cards
  var $fmc_repo; // repository path without trailing /
  var $sd;

  // ------------------------------------------------------------------------------------------------
  /**
   * Constructor
   */
  function __construct($sdid, $is_provisionning = false)
  {
    $this->conf_path = $_SERVER['GENERATED_CONF_BASE'];
    $this->sdid = $sdid;
    $this->conf_pflid = 0;
    $this->fmc_repo = $_SERVER['FMC_REPOSITORY'];
    $net = get_network_profile();
    $this->sd = &$net->SD;
  }

  // ------------------------------------------------------------------------------------------------
  /**
   * Get running configuration from the router
   */
  function get_running_conf()
  {
    global $sms_sd_ctx;
    global $sendexpect_result;

    // Run the CLI Cmd
    exec_local(__FILE__ . ':' . __LINE__, "/opt/sms/bin/sms -e JSCALLCOMMAND -i '".$this->sdid." IMPORT 0' -c '{}'", $output_array);
    $result = "";
    foreach ($output_array as $line)
    {
      if (strpos($line, "SMS_OK") === false)
      {
        $result .= $line;
      }
    }
    $js = json_decode($result, true);
    if (!empty($js['sms_result']))
    {
      // PHP 5.4.0
      //$SMS_OUTPUT_BUF = json_encode($js->sms_result, JSON_PRETTY_PRINT);
      ob_start();
      print_r($js['sms_result']);
      $SMS_OUTPUT_BUF = ob_get_contents();
      ob_end_clean();
      $this->running_conf = trim($SMS_OUTPUT_BUF);
    }
    else
    {
      $this->running_conf = '';
    }
    return $this->running_conf;
  }



  // ------------------------------------------------------------------------------------------------
  /**
   * Generate the general pre-configuration
   * @param $configuration   configuration buffer to fill
   */
  function generate_pre_conf(&$configuration)
  {
    //$configuration .= "!PRE CONFIG\n";
    get_conf_from_config_file($this->sdid, $this->conf_pflid, $configuration, 'PRE_CONFIG', 'Configuration');
    return SMS_OK;
  }

  // ------------------------------------------------------------------------------------------------
  /**
   * Generate a full configuration
   * Uses the previous conf if present to perform deltas
   */
  function generate(&$configuration, $use_running = false)
  {
    //$configuration .= "! CONFIGURATION GOES HERE\n";
    $configuration .= '';
    return SMS_OK;
  }

  // ------------------------------------------------------------------------------------------------
  /**
   * Generate the general post-configuration
   * @param $configuration   configuration buffer to fill
   */
  function generate_post_conf(&$configuration)
  {
    //$configuration .= "!POST CONFIG\n";
    get_conf_from_config_file($this->sdid, $this->conf_pflid, $configuration, 'POST_CONFIG', 'Configuration');
    return SMS_OK;
  }
  // ------------------------------------------------------------------------------------------------
  /**
   *
   */
  function build_conf(&$generated_configuration)
  {
    //$this->monitoring_conf($generated_configuration);
    $ret = $this->generate_pre_conf($generated_configuration);
    if ($ret !== SMS_OK)
    {
      return $ret;
    }
    $ret = $this->generate($generated_configuration);
    if ($ret !== SMS_OK)
    {
      return $ret;
    }

    $ret = $this->generate_post_conf($generated_configuration);
    if ($ret !== SMS_OK)
    {
      return $ret;
    }

    return SMS_OK;
  }

  // ------------------------------------------------------------------------------------------------
  /**
   *
   */
  function update_conf()
  {
    $ret = $this->build_conf($generated_configuration);

    if (!empty($generated_configuration))
    {
      $ret = veex_rtu_apply_conf($generated_configuration);
    }
    return $ret;
  }
  function provisioning()
  {
    return $this->update_conf();
  }
}

?>
