<?php
	require 'lib/aws_php_sdk/aws-autoloader.php';
	$client = new Aws\Polly\PollyClient([
			'region'  => 'us-east-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => file_get_contents('../../aws_web_id'),
				'secret' => file_get_contents('../../aws_web_password'),
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
