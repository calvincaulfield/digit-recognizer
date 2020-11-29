<?php
require 'lib/aws_php_sdk/aws-autoloader.php';


$client = new Aws\MachineLearning\MachineLearningClient([
	'region'  => 'us-east-1',
	'version' => 'latest',
	'credentials' => [
		'key'    => getenv('AWS_ACCESS_KEY_ID'),
		'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
	],
]);


$record = array();
$pixels = json_decode($_GET['input']);

foreach (range(1, 28 * 28) as $number) {
	// Var001 is target column, so skip it (002~785)
	$num_string = str_pad(strval($number + 1), 3, '0', STR_PAD_LEFT);
	$column_name = 'Var' . $num_string;
	$record[$column_name] = strval($pixels[$number - 1]);
	//$record[$column_name] = strval(0);
}

$result = $client->predict([
	'MLModelId' => 'ml-jXWcB3voH6Y', // REQUIRED
	'PredictEndpoint' => 'https://realtime.machinelearning.us-east-1.amazonaws.com', // REQUIRED
	'Record' => $record, // REQUIRED
]);

//echo $result->$Prediction->$predictedValue;
$predict = $result->get('Prediction')['predictedLabel'];
//$predict = 5;
echo json_decode($predict);
