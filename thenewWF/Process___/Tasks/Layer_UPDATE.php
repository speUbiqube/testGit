<?php

require_once '/opt/fmc_repository/Process/Reference/Common/common.php';

function list_args()
{
  create_var_def('device_id', 'Device');
  create_var_def('Layer.0.add_default_rule', 'Boolean');
create_var_def('Layer.0.name', 'String');
create_var_def('Layer.0.applications_and_url_filtering', 'Boolean');
create_var_def('Layer.0.shared', 'Boolean');
create_var_def('Layer.0.type', 'String');
create_var_def('Layer.0.firewall', 'Boolean');
create_var_def('Layer.0.object_id', 'String');

}

check_mandatory_param('device_id');

$device_id = $context['device_id'];
$prefix = substr($device_id, 0, 3);
$device_id = str_replace($prefix, '', $device_id);

foreach ($context['Layer'] as $key => $value) {
   $object_parameters['Layer'][$value['object_id']] = $value;
}


$response = execute_command_and_verify_response($device_id, "UPDATE", $object_parameters, "UPDATE Layer");
  $response = json_decode($response, true);
if ($response['wo_status'] !== ENDED) {
	$response = prepare_json_response($response['wo_status'], $response['wo_comment'], $context, true);
	echo $response;
	exit;
}

$response = prepare_json_response(ENDED, "order command execute successfull", $response['wo_newparams'], true);
echo $response;
?>
