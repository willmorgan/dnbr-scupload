<?php

/**
 * UploadJob
 * Performs the actual downloading of the podcast item, and then
 * the subsequent uploading of it to Soundcloud.
 */
namespace SCUpload\Track;

use SCUpload\SCUpload;
use JQManagedJob;
use JQJob;

class UploadJob implements JQJob {

	protected $app;

	protected $track;

	public function __construct(SCUpload $app, Track $track) {
		$this->app = $app;
		$this->track = $track;
	}

    public function setApp(SCUpload $app) {
        $this->app = $app;
        return $this;
    }

    public function getApp() {
        return $this->app;
    }

	/**
	 * Keep the variables when serialized
	 * @return array
	 */
	public function __sleep() {
		return array(
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

    public function getTrack() {
        return $this->track;
    }

    /**
     * {@inheritdoc}
     */
    public function run(JQManagedJob $job) {
    	// copy code from trackwriter, lulz
    	$writer = $this->getWriter();
        $logger = $this->getApp()->getLog();
        $track = $this->getTrack();

        try {
            $writer->write();
            $logger->info(
                'Podcast ' . $track->getField('title') . ' uploaded'
            );
            return JQManagedJob::STATUS_COMPLETED;
        }
        // Allow job to hang for later on known errors
        catch(Track\Writer_PodcastDownloadException $e) {
            $logger->warning(
                'Podcast 404 or download error',
                array(
                    'exception' => $e,
                )
            );
        }
        catch(Track\Writer_SoundcloudUploadFailedException $e) {
            $logger->warning(
                'Soundcloud upload failed',
                array(
                    'exception' => $e,
                )
            );
        }
        catch(\Exception $e) {
            $logger->critical(
                'Unknown error',
                array(
                    'exception' => $e,
                )
            );
        }
        return JQManagedJob::STATUS_FAILED;
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
    		$logger->error($message, $mJob->toArray());
    	}
    	else {
    		$logger->info($message, $mJob->toArray());
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
    	return 'UploadJob_'.$this->track->getClientID();
    }

}
