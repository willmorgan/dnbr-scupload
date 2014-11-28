<?php

/**
 * upload_worker
 * Fetch a track off the queue then go and upload it. Simples.
 */

require_once 'vendor/autoload.php';

use SCUpload\Track;
use SCUpload\SCUpload;
use SCUpload\Track\QueueManager;

declare(ticks = 1);

$app = new SCUpload(array(
	'run_config' => './run_config.json',
	'app_config' => './config.json',
	'log.writer' => new \Slim\LogWriter(fopen('../debug.log', 'a')),

));

$manager = new QueueManager($app);

$job = $manager->fetch();

if(!$job) {
	$app->getLog()->info('Nothing to upload, all jobs done!');
	return;
}

$job->run($job);
