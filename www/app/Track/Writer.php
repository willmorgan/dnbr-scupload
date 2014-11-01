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
	 * @param array $tracks
	 */
	public function __construct(array $tracks) {
		$this->tracks = $tracks;
	}

	/**
	 * Write this object to Soundcloud
	 */
	public function write() {
		// implement me!
	}

}
