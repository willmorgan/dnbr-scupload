<?php

namespace SCUpload;

include 'app/Parser/PodcastFeedParser.php';

use SCUpload\Parser;
use FastFeed\Factory;
use FastFeed\Processor;

class FeedReader {

	/**
	 * @var SCUpload
	 */
	protected $app;

	public function __construct(SCUpload $app) {
		$this->app = $app;
	}

	/**
	 * Fetch the feed, get the entries, and narrow
	 * them down to those not seen since we last
	 * run the uploader task.
	 * @return array
	 */
	public function getEntries() {
		$fastFeed = Factory::create();
		$fastFeed->popParser();
		$fastFeed->pushParser(
			new Parser\PodcastFeedParser()
		);
		$fastFeed->pushProcessor(
			new \FastFeed\Processor\SortByDateProcessor()
		);
		$fastFeed->addFeed(
			'default',
			$this->app->app_config['settings']['rss_feed']
		);
		return $fastFeed->fetch('default');
	}

}
