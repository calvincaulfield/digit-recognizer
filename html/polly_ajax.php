<?php
	require '../../aws_php_sdk/aws-autoloader.php';
	$client = new Aws\Polly\PollyClient([
			'region'  => 'us-east-1',
			'version' => 'latest',
			'credentials' => [
				'key'    => 'AKIAJGEYPOZAM36MTHHA',
				'secret' => '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',
			],
		]);
	$result = $client->synthesizeSpeech([
		'OutputFormat' => 'mp3', // REQUIRED
		'Text' => $_GET['input'], // REQUIRED
		'VoiceId' => 'Emma', // REQUIRED
	]);
	$audio = $result->get('AudioStream')->getContents();
	
	$current_time = time();
	$filename = $current_time . ".mp3";
	//$filename = "polly.mp3";

	file_put_contents($filename, $audio);
	echo json_encode($filename);
	//var_dump($audio);
