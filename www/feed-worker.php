<?php

/**
 * feed_worker
 * This downloads the RSS feed and adds the tasks to the queue store
 */
require_once 'vendor/autoload.php';

use SCUpload\Track;
use SCUpload\SCUpload;
use SCUpload\FeedReader;
use SCUpload\Track\QueueManager;

$app = new SCUpload(array(
	'run_config' => './run_config.json',
	'app_config' => './config.json',
	'log.writer' => new \Slim\LogWriter(fopen('../feed-worker.log', 'a')),
));

$manager = new QueueManager($app);

$fetcher = new FeedReader($app);

$manager->addTracks($fetcher->getTracks());

$fetcher->setLastRun(time());
