<?php

/**
 * FeedJob
 * Reads the podcast (on a particular schedule) and then creates more jobs
 * for the subsequent uploads
 */
namespace SCUpload;

use JSManagedJob;
use JQJob;

class FeedJob implements JQJob {

	protected $app;

	public function __construct(SCUpload $app) {
		$this->app = $app;
	}

	/**
	 * Keep the variables when serialized
	 * @return array
	 */
	public function __sleep() {
		return array(
			'app',
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
     * {@inheritdoc}
     */
    public function run(JQManagedJob $job) {
        $reader = $app->getFeedReader();
        $tracks = $reader->getTracks();
        $reader->setLastRun(time());
        $queue = new Track\QueueManager($app);
        $queue->addTracks($tracks);
   		return JQManagedJob::STATUS_COMPLETED;
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup() {
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
    	return 'Reading the podcast feed';
    }

    /**
     * {@inheritdoc}
     */
    public function coalesceId() {
    	return __CLASS__;
    }

}
