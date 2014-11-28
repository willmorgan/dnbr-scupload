<?php

/**
 * UploadWorker
 * In charge of uploading the UploadJobs. Overrides JQWorker to provide
 * some sugar functionality, and to hook in to SCUpload/Slim's application log.
 */
namespace SCUpload;

use JQWorker;
use SCUpload\Track\QueueStore;

class UploadWorker extends JQWorker {

	protected $app;

	public function __construct(QueueStore $store, $options = array()) {
		$this->setApp($store->getApp());
		parent::__construct($store, $options);
	}

	public function setApp(SCUpload $app) {
		$this->app = $app;
		return $this;
	}

	/**
	 * Hook in to the SCUpload log
	 */
	protected function log($msg, $verboseOnly = false) {
		$logger = $this->app->getLog();
		if($verboseOnly) {
			$logger->debug($msg);
		}
		else {
			$logger->info($msg);
		}
	}

	/**
	 * Wrap around SCUpload->sanityCheck
	 * @return mixed
	 */
	public function sanityCheck() {
		try {
			$this->app->sanityCheck();
			return true;
		}
		catch(\Exception $e) {
			return $e->getMessage();
		}
	}

}
