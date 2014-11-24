<?php

/**
 * QueueManager
 * Handles pushing and acking Track objects from a central queue
 */

namespace SCUpload\Track;

use SCUpload\SCUpload;

class QueueManager {

	protected $app;

	protected $store;

	public function __construct(SCUpload $app) {
		$this->app = $app;
		$this->store = new QueueStore($app);
	}

	/**
	 * Add many tracks to the stack
	 */
	public function addTracks(array $tracks) {
		foreach($tracks as $track) {
			$job = new UploadJob($this->app, $track);
			$this->store->enqueue($job);
		}
	}

}
