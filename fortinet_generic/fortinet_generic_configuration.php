<?php

require_once 'smsd/sms_common.php';
//require_once 'smsd/pattern.php';

require_once load_once('fortinet_generic', 'common.php');
require_once load_once('fortinet_generic', 'adaptor.php');
require_once load_once('fortinet_generic', 'fortinet_generic_apply_conf.php');

require_once "$db_objects";


class fortinet_generic_configuration
{
	var $conf_path;           // Path for previous stored configuration files
	var $sdid;                // ID of the SD to update
	var $running_conf;        // Current configuration of the router
	var $conf_to_restore;     // configuration to restore
	var $profile_list;        // List of managed profiles
	var $fmc_repo;            // repository path without trailing /
	var $sd;

	// ------------------------------------------------------------------------------------------------
	/**
	* Constructor
	*/
	function __construct($sdid, $is_provisionning = false)
	{
		$this->conf_path = $_SERVER['GENERATED_CONF_BASE'];
		$this->sdid = $sdid;
		$this->fmc_repo = $_SERVER['FMC_REPOSITORY'];
		$net = get_network_profile();
		$this->sd = &$net->SD;
		$this->conf_pflid = $this->sd->SD_CONFIGURATION_PFLID;
	}

	// ------------------------------------------------------------------------------------------------
	/**
	* Get running configuration from the router
	*/
	function get_running_conf()
	{
		global $sms_sd_ctx;

        $temp_buffer=sendexpectone(__FILE__.':'.__LINE__, $sms_sd_ctx, 'get system status');
        if(strpos($temp_buffer, 'Virtual domain configuration: enable') !== false){
          //If VDOM is enabled get out of vdom and go into config global to take config backup
          $temp_buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, 'end', '#');
          $temp_buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, 'config global', '(global) #', 40000);
          $msa_ip = $_SERVER['SMS_ADDRESS_IP'];
          $dev_id=$this->sd->SDID;
          $tmp_conf_file="$dev_id"."_running.conf";
          $cmd="execute backup config tftp $tmp_conf_file $msa_ip";
          $temp_buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, $cmd, '(global) #',4000);
        	if(strpos($temp_buffer, 'Send config file to tftp server OK') !== false){
        	    $backup_file_path="/opt/sms/spool/tftp/"."$tmp_conf_file";
        		$running_conf = file_get_contents ($backup_file_path);
        		//remove all text between "config vpn certificate local" and "end" including these two lines
        		$list= explode("config vpn certificate local",$running_conf);
        		$remaining_conf= strstr($list[1],"end");
        		$remaining_conf = substr($remaining_conf,3);
        		$running_conf="$list[0]$remaining_conf";
        		//config backedup, go back to root vdom
        		$buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, 'end', '#',4000);
        		$buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, 'config vdom', '(vdom) #', 40000);
        		$cmd = "edit root";
        		$buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, $cmd, '#', 40000);
        		$tmp_file_cleanup_cmd = "rm -f $backup_file_path";
        		shell_exec($tmp_file_cleanup_cmd);
        		}else{
        		   throw new SmsException("", ERR_SD_CMDTMOUT);
        		}

        		$IS_VDOM_ENABLED=true;
        }else{

		$running_conf = sendexpectone(__FILE__.':'.__LINE__, $sms_sd_ctx, 'show');

		}
		if (!empty($running_conf))
		{
		  $running_conf = remove_line_starting_with($running_conf, '#conf_file_ver=');
		  $running_conf = trim($running_conf);
		}

		$this->running_conf = $running_conf;
		return $this->running_conf;
	}

	// ------------------------------------------------------------------------------------------------
	function generate_from_old_revision($revision_id)
	{

		echo("generate_from_old_revision revision_id: $revision_id\n");
		$this->revision_id = $revision_id;

		$get_saved_conf_cmd = "/opt/sms/script/get_saved_conf --get $this->sdid r$this->revision_id";
		echo($get_saved_conf_cmd . "\n");

		$ret = exec_local(__FILE__ . ':' . __LINE__, $get_saved_conf_cmd, $output);
		if ($ret !== SMS_OK)
		{
			echo("no running conf found\n");
			return $ret;
		}

		$res = array_to_string($output);

		// remove useless lines
		$patterns = array ();
		$patterns [0] = "/OK\s*/";
		$patterns [1] = "/SMS_\s*/";
		$replacements = array ();
		$replacements [0] = "";
		$replacements [1] = "";

		$this->conf_to_restore = preg_replace($patterns, $replacements, $res);

		return SMS_OK;
	}

	//------------------------------------------------------------------------------------------------
	function restore_conf()
	{
		global $sms_sd_ctx;

		//$this->conf_to_restore
		$filename = "{$_SERVER['TFTP_BASE']}/{$this->sdid}.cfg";
		file_put_contents($filename, $this->conf_to_restore);

		$IS_VDOM_ENABLED = false;
		$temp_buffer=sendexpectone(__FILE__.':'.__LINE__, $sms_sd_ctx, 'get system status');
		if(strpos($temp_buffer, 'Virtual domain configuration: enable') !== false){

			//If VDOM is enabled get out of vdom and go into config global to take config backup
			$temp_buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, 'end', '#');
			$temp_buffer = sendexpectone(__FILE__ . ':' . __LINE__, $sms_sd_ctx, 'config global', '(global) #', 40000);
			$IS_VDOM_ENABLED=true;
		}


		if(empty($this->sd->SD_CONFIGVAR_list['MANAGEMENT_VLAN_IP']))
		{
			$tftp_ip = $_SERVER['SMS_ADDRESS_IP'];
		}
		else
		{
			$tftp_ip = $this->sd->SD_CONFIGVAR_list['MANAGEMENT_VLAN_IP']->VAR_VALUE;
		}

		sendexpectone(__FILE__.':'.__LINE__, $sms_sd_ctx, "execute restore config tftp {$this->sdid}.cfg {$tftp_ip}", "(y/n)");
		unset($tab);
		$tab[0] = "File check OK.";
		$tab[1] = "Invalid config file";
		$index = sendexpect(__FILE__.':'.__LINE__, $sms_sd_ctx, "y", $tab);
		if ($index !== 0)
		{
			$SMS_OUTPUT_BUF = $sendexpect_result;
			return ERR_RESTORE_FAILED;
		}
		unlink($filename);

		return SMS_OK;
	}

	// ------------------------------------------------------------------------------------------------
	/**
	* Generate the general pre-configuration
	* @param $configuration   configuration buffer to fill
	*/
	function generate_pre_conf(&$configuration)
	{
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
		get_conf_from_config_file($this->sdid, $this->conf_pflid, $configuration, 'POST_CONFIG', 'Configuration');
		return SMS_OK;
	}

	// ------------------------------------------------------------------------------------------------
	/**
	*
	*/
	function build_conf(&$generated_configuration)
	{

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

		if(!empty($generated_configuration))
		{
			$ret = fortinet_generic_apply_conf($generated_configuration);
		}

		return $ret;
	}

	// ------------------------------------------------------------------------------------------------
	/**
	*
	*/
	function provisioning()
	{
		return $this->update_conf();
	}

	function wait_until_device_is_up()
	{
	  return wait_for_device_up ($this->sd->SD_IP_CONFIG, 60, 300);
	}

}

?>