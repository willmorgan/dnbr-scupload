<?php

/**
 * UploadJob
 * Performs the actual downloading of the podcast item, and then
 * the subsequent uploading of it to Soundcloud.
 */
namespace SCUpload\Track;

use JSManagedJob;
use JQJob;

class UploadJob implements JQJob {

	protected $app;

	protected $track;

	public function __construct(SCUpload $app, Track $track) {
		$this->app = $app;
		$this->track = $track;
	}

	/**
	 * Keep the variables when serialized
	 * @return array
	 */
	public function __sleep() {
		return array(
			'app',
			'track'
		);
	}

    /**
     * No settings needed... so yeah.
     * {@inheritdoc}
     */
    public function getEnqueueOptions() {
    	return array();
    }

    /**
     * @return SCUpload\Track\Writer
     */
    protected function getWriter() {
    	return new Writer($this->app, $this->track);
    }

    /**
     * {@inheritdoc}
     */
    public function run(JQManagedJob $job) {
    	// copy code from trackwriter, lulz
    	$writer = $this->getWriter();
    	try {
    		$writer->write();
    		return JQManagedJob::STATUS_COMPLETED;
    	}
    	catch(Exception $e) {
    		return JQManagedJob::STATUS_FAILED;
    	}
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup() {
    	unlink($this->getWriter()->getCacheFilename());
    }

    /**
     * {@inheritdoc}
     */
    public function statusDidChange(JQManagedJob $mJob, $oldStatus, $message) {
    	$logger = $this->app->getLog();
    	if($message === JQManagedJob::STATUS_FAILED) {
    		$logger->error($mJob);
    	}
    	else {
    		$logger->info($mJob);
    	}
    }

    /**
     * {@inheritdoc}
     */
    public function description() {
    	return 'Uploading a track to Soundcloud';
    }

    /**
     * {@inheritdoc}
     */
    public function coalesceId() {
    	return __CLASS__ . '_' . $this->track->getClientID();
    }

}
