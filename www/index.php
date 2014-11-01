<?php

/**
 * SoundCloud oauth step
 * There's no nice way of getting an oauth token via a dashboard,
 * So we have to implement it in code (-1 hour of my life!!)
 * @author @willmorgan
 */
include 'vendor/autoload.php';

use Slim\Slim;
use Njasm\Soundcloud;


/**
 * Go and make me a SoundCloud facade with a hardcoded port name in the callback URL
 * @return Njasm\Soundcloud\Soundcloud\SoundcloudFacade
 */
function ugly_make_sc_facade($app) {
	$scConfig = $app->app_config['credentials']['soundcloud'];
	$serverAndPort = $_SERVER['SERVER_NAME'] . ':8080';
	$redirectURI = 'http://' . $serverAndPort . '/oauth2callback';
	return new Soundcloud\SoundcloudFacade(
		$scConfig['client_id'],
		$scConfig['client_secret'],
		$redirectURI
	);
};

/**
 * The token must be valid and must belong to the right user
 * specified on the config file
 * @param Slim\Slim $app
 * @param string $token The token to use if we're testing a new one, instead of one held in the app
 * @return boolean
 */
function ugly_is_the_token_valid($app, $token = null) {
	if(!isset($token)) {
		$token = $app->oauth_token;
	}
	if(empty($token)) {
		return false;
	}
	$sc = ugly_make_sc_facade($app);
	$sc->setAccessToken($token);
	$meResponse = $sc->get('/me')->asJson()->request()->bodyObject();
	$tokenUser = $meResponse->username;
	$allowedUser = $app->app_config['settings']['soundcloud_user'];
	return $tokenUser == $allowedUser;
};

echo '<pre>';

$app = new Slim();

// Load the config files: config is semi-portable, but run_config should not be copied across instances
$app->run_config = json_decode(file_get_contents('run_config.json'), true);
$app->app_config = json_decode(file_get_contents('config.json'), true);

// Check if we have an oauth token.
$app->oauth_token = $app->run_config['soundcloud']['oauth_token'];

/**
 *
 *
 * ROUTES (and lots of functional code hahahahahahah)
 *
 *
 */
/**
 * Index page - shows some diagnostic info if stuff is wrong
 */
$app->get('/', function() use($app) {
	$checks = array(
		'run_config_is_writable' => is_writable('run_config.json'),
		'has_valid_oauth_token' => ugly_is_the_token_valid($app),
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
	$sc = ugly_make_sc_facade($app);
	$link = $sc->getAuthURL();
	echo '<a href="'.$link.'">Authenticate</a>';
});

/**
 * The callback that SoundCloud sends us back to
 * If all is good then we update run_config.json with a proper token
 * Otherwise we just dump the error to the lovely person/hacker
 * I LOVE OAUTH IT'S SO GREAT
 */
$app->get('/oauth2callback', function() use($app) {
	$sc = ugly_make_sc_facade($app);
	$scConfig = $app->app_config['credentials']['soundcloud'];
	$request = $app->request;
	$code = $app->request->get('code');
	$response = $sc->codeForToken($code)->bodyObject();
	try {
		if(empty($response->access_token) || strpos($response->scope, 'non-expiring') === false) {
			throw new LogicException('Scope must be non-expiring, or no access token response');
		}
		$token = $response->access_token;
		$sc->setAccessToken($token);
		if(!ugly_is_the_token_valid($app, $token)) {
			throw new LogicException('Logged in as the wrong user');
		}
		$app->run_config['soundcloud']['oauth_token'] = $token;
		file_put_contents('run_config.json', json_encode($app->run_config));
		echo 'Runtime config updated, you can now proceed to run the upload task!';
	}
	catch(LogicException $e) {
		echo 'Error occurred ('.$e->getMessage().'). <a href="/get-token">Start again</a>?<br>';
		var_dump($response);
	}
});

$app->run();
