<?php

/**
 * QueueManager
 * Handles pushing and acking Track objects from a central queue
 */

namespace SCUpload\Track;

use SCUpload\SCUpload;
use \PDO;

class QueueManager {

	protected $app;

	public function __construct(SCUpload $app) {
		$this->app = $app;
	}

	/**
	 * Add many tracks to the stack
	 */
	public function addTracks(array $tracks) {

	}

	/**
	 * Get all tracks in the queue that are yet to be processed
	 */
	protected function getTracks() {

	}

	/**
	 * Get all tracks in the queue, regardless of status
	 */
	protected function getAllTracks() {

	}

	/**
	 * Fetch a currently unhandled track from the queue;
	 * set its status to in process.
	 */
	public function fetchTrack() {

	}

	/**
	 * Call when the track has been uploaded and we're finished.
	 */
	public function ackTrack(Track $track) {

	}

}
