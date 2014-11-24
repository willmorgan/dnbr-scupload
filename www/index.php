<?php

/**
 * @author @willmorgan
 */

require_once 'vendor/autoload.php';

use SCUpload\SCUpload;
use SCUpload\Track;

echo '<pre>';

$app = new SCUpload(array(
	'run_config' => './run_config.json',
	'app_config' => './config.json',
));

/**
 *
 *
 * ROUTES (and lots of functional code...)
 *
 *
 */
/**
 * Index page - shows some diagnostic info if stuff is wrong
 */
$app->get('/', function() use($app) {
	$checks = array(
		'app_config_is_readable' => file_exists('config.json'),
		'run_config_is_readable' => file_exists('run_config.json'),
		'run_config_is_writable' => is_writable('run_config.json'),
		'tmp_dir_is_writable' => is_writable($app->app_config['settings']['tmp_directory']),
		'has_valid_oauth_token' => $app->isTokenValid(),
	);
	echo '<h3>Instance status</h3>';
	echo '<ul>';
	foreach($checks as $checkID => $checkValue) {
		echo sprintf('<li>%s = %s</li>', $checkID, ($checkValue ? 'OK' : 'No'));
	}
	echo '</ul>';
});

/**
 * Get token page - just provides a link off to the auth page.
 * Kinda a waste of a route but oh well
 */
$app->get('/get-token', function() use ($app) {
	$link = $app->getSoundcloud()->getAuthURL();
	echo '<a href="'.$link.'">Authenticate</a>';
});

/**
 * The callback that SoundCloud sends us back to
 * If all is good then we update run_config.json with a proper token
 * Otherwise we just dump the error to the lovely person/hacker
 * I LOVE OAUTH IT'S SO GREAT
 */
$app->get('/oauth2callback', function() use($app) {
	try {
		$app->setTokenFromOAuthCode($app->request->get('code'));
		echo 'Token successfully obtained and saved, you may now continue to run the task';
	}
	catch(Exception $e) {
		echo 'Error occurred ('.$e->getMessage().'). <a href="/get-token">Start again</a>?<br>';
	}
});

/**
 * Web CLI
 * Just a placeholder until I find a nice CLI environment runner
 */
$app->get('/webcli', function() use($app) {
	$reader = $app->getFeedReader();
	$tracks = $reader->getTracks();
	$reader->setLastRun(time());
	$queue = new Track\QueueManager($app);
	$queue->addTracks($tracks);
});

$app->run();
