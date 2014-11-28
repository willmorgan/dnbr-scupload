<?php

/**
 * upload_worker
 * Fetch a track off the queue then go and upload it. Simples.
 */

require_once 'vendor/autoload.php';

use SCUpload\Track;
use SCUpload\SCUpload;
use SCUpload\UploadWorker;
use SCUpload\Track\QueueStore;

if(php_sapi_name() !== 'cli') {
	exit(
		'Must be run via CLI otherwise you can\'t easily terminate the worker'
	);
}

$app = new SCUpload(array(
	'run_config' => './run_config.json',
	'app_config' => './config.json',
	'log.writer' => new \Slim\LogWriter(fopen('../upload-worker.log', 'a')),
));

$store = new QueueStore($app);

$worker = new UploadWorker($store);

$check = $worker->sanityCheck();
if($check !== true) {
	echo $check . "\n\n(cwd = " . getcwd() . ")";
	die;
}

$worker->start();
