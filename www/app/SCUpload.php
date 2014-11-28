<?php

/**
 * SCUpload
 * @author @willmorgan
 */

namespace SCUpload;

use Slim\Slim;
use Njasm\Soundcloud;

class SCUpload extends Slim {

	/**
	 * Constructor settings
	 * @var array
	 */
	protected $sc_settings = array();

	/**
	 * @var array
	 */
	public $run_config;

	/**
	 * @var array
	 */
	public $app_config;

	/**
	 * @var string
	 */
	public $oauth_token;

	/**
	 * @param array $settings Must contain run_config and app_config params
	 */
	public function __construct(array $settings) {
		parent::__construct($settings);
		// Back up the settings (may be used later)
		$this->sc_settings = $settings;
		// Load the config files: config is semi-portable;
		// run_config should not be copied across instances
		$this->loadConfig('run_config', $settings);
		$this->loadConfig('app_config', $settings);

		// Check if we have an oauth token.
		$this->oauth_token = $this->run_config['soundcloud']['oauth_token'];
	}

	/**
	 * Load a config file's JSON into the app
	 * @param string $kind
	 * @param string $file The JSON file
	 * @return $this
	 */
	public function loadConfig($kind, $settings) {
		if(empty($settings[$kind])) {
			throw new \InvalidArgumentException('Please define a ' . $kind . ' property in $settings');
		}
		$file = $settings[$kind];
		$decoded = json_decode(file_get_contents($file), true);
		if(is_null($decoded)) {
			throw new \InvalidArgumentException(
				'Could not load config file, maybe ' . $file . ' is malformed'
			);
		}
		$this->$kind = $decoded;
		return $this;
	}

	/**
	 * The token must be valid and must belong to the right user
	 * specified on the config file
	 * @param string $token The token to use if we're testing a new one, instead of one held in the app
	 * @return boolean
	 */
	public function isTokenValid($token = null) {
		if(!isset($token)) {
			$token = $this->oauth_token;
		}
		if(empty($token)) {
			return false;
		}
		$sc = $this->getSoundcloud();
		$sc->setAccessToken($token);
		$meResponse = $sc->get('/me')->asJson()->request()->bodyObject();
		$tokenUser = $meResponse->username;
		$allowedUser = $this->app_config['settings']['soundcloud_user'];
		return $tokenUser == $allowedUser;
	}

	/**
	 * Validate the token to make sure that it's valid and belongs to
	 * the right user in the settings. Don't want to upload to someone
	 * else's account!
	 * @param string $code Should be from the redirect URI's GET params
	 * @throws LogicException
	 * @return boolean
	 */
	public function setTokenFromOAuthCode($code) {
		$sc = $this->getSoundcloud();
		$scConfig = $this->app_config['credentials']['soundcloud'];
		$response = $sc->codeForToken($code)->bodyObject();
		if(empty($response->access_token) || strpos($response->scope, 'non-expiring') === false) {
			throw new \LogicException('Scope must be non-expiring, or no access token response');
		}
		$token = $response->access_token;
		if(!$this->isTokenValid($token)) {
			throw new \LogicException('Logged in as the wrong user');
		}
		$this->saveOAuthToken($token);
		return true;
	}

	/**
	 * Persist the OAuth token to our runtime configuration
	 * Check its validity with $this->isTokenValid
	 * @param string $token
	 * @return $this
	 */
	public function saveOAuthToken($token) {
		$this->run_config['soundcloud']['oauth_token'] = $token;
		$this->persistRunConfig();
		return $this;
	}

	/**
	 * Save the run config to the JSON file
	 * @return $this
	 */
	public function persistRunConfig() {
		file_put_contents(
			$this->sc_settings['run_config'],
			json_encode($this->run_config)
		);
		return $this;
	}

	/**
	 * Make me a Soundcloud facade with a hardcoded port name in the callback URL
	 * @return Njasm\Soundcloud\Soundcloud\SoundcloudFacade
	 */
	public function getSoundcloud() {
		$scConfig = $this->app_config['credentials']['soundcloud'];
		$redirectURI = 'http://' . $this->app_config['settings']['server'] . '/oauth2callback';
		$facade = new Soundcloud\SoundcloudFacade(
			$scConfig['client_id'],
			$scConfig['client_secret'],
			$redirectURI
		);
		$facade->setAccessToken($this->oauth_token);
		return $facade;
	}

	/**
	 * @return FeedReader
	 */
	public function getFeedReader() {
		return new FeedReader($this);
	}

}
