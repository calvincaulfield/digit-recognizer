<?php
require 'lib/aws_php_sdk/aws-autoloader.php';
$client = new Aws\Polly\PollyClient([
	'region'  => 'us-east-1',
	'version' => 'latest',
	'credentials' => [
		'key'    => getenv('AWS_ACCESS_KEY_ID'),
		'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
	],
]);
$result = $client->synthesizeSpeech([
	'OutputFormat' => 'mp3', // REQUIRED
	'Text' => $_GET['input'], // REQUIRED
	'VoiceId' => 'Mizuki', // REQUIRED
]);
$audio = $result->get('AudioStream')->getContents();

$current_time = time();
$filename = $current_time . ".mp3";
//$filename = "polly.mp3";

//file_put_contents('audio/temp.mp3', '');
file_put_contents('audio/' . $filename, $audio);
//echo json_encode($filename);
echo json_encode($filename);

	//var_dump($audio);
