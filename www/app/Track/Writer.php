<?php

/**
 * Writer
 * Delegates the uploading of tracks to Soundcloud.
 * @author @willmorgan
 */

namespace SCUpload\Track;

use SCUpload\SCUpload;

class Writer {

	/**
	 * @var SCUpload
	 */
	protected $app;

	/**
	 * @param array $tracks
	 */
	public function __construct(SCUpload $app, array $tracks) {
		$this->app = $app;
		$this->tracks = $tracks;
	}

	/**
	 * Write this object to Soundcloud
	 */
	public function write() {
		foreach($this->tracks as $track) {
			$this->writeTrack($track);
		}
	}

	/**
	 * Ideally this is where the work is parallelized.
	 * Upload the track to Soundcloud and do some cleanup.
	 * Should utilise the Slim log interface to report back on errors.
	 * @param Track $track to upload
	 * @return void
	 */
	protected function writeTrack(Track $track) {
		$tmpDir = $this->app->app_config['settings']['tmp_directory'];
		$cacheFileName = implode('/', array($tmpDir, $track->getClientID()));
		// Check if the file already exists in our temp directory. If not, download
		if(!is_readable($cacheFileName)) {
			$this->fetchTrackFile($cacheFileName, $track);
		}
		$soundcloud = $this->app->getSoundcloud();
		$response = $soundcloud->upload(
			$cacheFileName,
			$track->getCreateData()
		);
// ;)
var_dump($response->bodyRaw());
	}

	/**
	 * Go and save the track file to our local staging directory
	 * @param string $target the file path
	 * @param Track $track
	 * @return void
	 */
	protected function fetchTrackFile($target, Track $track) {
		$fh = fopen($target, 'w+');
		$ch = curl_init($track->getAudioSource());
		curl_setopt_array($ch, array(
			CURLOPT_FAILONERROR => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLINFO_HEADER_OUT => false,
			CURLOPT_FILE => $fh,
		));
		curl_exec($ch);
	}

}
