<?php 

require_once '/opt/fmc_repository/Process/Reference/Common/common.php';

function list_args()
{
	//create_var_def('uris.0.uri', 'String'); // Configuration/MSA/...
	create_var_def('position', 'String'); // PRE_CONFIG, POST_CONFIG, AUTO
}

//check_mandatory_param('device_id');
//check_mandatory_param('uris');
//check_mandatory_param('position');

//$device_id=$context['device_id'];
//$device_id=getIdFromUbiId ($device_id);
//$uris = $context['uris'];
//$uris = 'Configuration/PA_initial_config';
$uri = 'Configuration/PA_initial_config';
$uris_array = array();
$index = 0;
//foreach ($uris as $uri) {
//	$uris_array[$index++] = $uri;
//}
$uris_array = array($uri);
//$position = $context['position'];
$position = 'AUTO';
$response = _device_configuration_attach_files_to_device('199531', $uris_array, $position);
$response = json_decode($response, true);
if ($response['wo_status'] !== ENDED || $response['wo_newparams'] !== "") {
	$response = json_encode($response);
	echo $response;
	exit;
}

$response = prepare_json_response(ENDED, "Files Attached successfully to the Device : $device_id", $context, true);
echo $response;

?>