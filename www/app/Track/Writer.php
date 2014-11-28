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

	protected $track;

	/**
	 * @param array $tracks
	 */
	public function __construct(SCUpload $app, Track $track) {
		$this->app = $app;
		$this->track = $track;
	}

	/**
	 * Ideally this is where the work is parallelized.
	 * Upload the track to Soundcloud and do some cleanup.
	 * Should utilise the Slim log interface to report back on errors.
	 * @throws Exception
	 * @return void
	 */
	public function write() {
		$track = $this->track;
		$cacheFileName = $this->getCacheFilename();
		// Check if the file already exists in our temp directory. If not, download
		if(!is_readable($cacheFileName)) {
			$this->fetchTrackFile($cacheFileName, $track);
			if(!is_readable($cacheFileName)) {
				throw new Writer_PodcastNotFoundException(
					'Could not read the downloaded podcast'
				);
			}
		}
		$soundcloud = $this->app->getSoundcloud();
		try {
			$response = $soundcloud->upload(
				$cacheFileName,
				$track->getCreateData()
			);
			$this->app->getLog()->debug($response->bodyRaw());
			return $response;
		}
		catch(Exception $e) {
			throw new Writer_SoundcloudUploadFailedException(
				$e->getMessage(),
				$e->getCode(),
				$e
			);
		}
	}

	/**
	 * @return string
	 */
	public function getCacheFilename() {
		$tmpDir = $this->app->app_config['settings']['tmp_directory'];
		return implode('/', array($tmpDir, $this->track->getClientID()));
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
		$curlSuccess = curl_exec($ch);
		curl_close($ch);
		if(!$curlSuccess) {
			throw new Writer_PodcastDownloadException();
		}
	}


}

class Writer_PodcastDownloadException extends \Exception { }
class Writer_SoundcloudUploadFailedException extends \Exception { }
class Writer_PodcastNotFoundException extends Writer_PodcastDownloadException { }
