<?php

namespace SCUpload;

require_once 'app/Parser/PodcastFeedParser.php';
require_once 'app/Track/Track.php';

use SCUpload\Parser;
use SCUpload\Track;
use FastFeed\Factory;
use FastFeed\Processor;

class FeedReader {

	/**
	 * @var SCUpload
	 */
	protected $app;

	protected static $track_item_map = array(
		'title' => 'getName',
		'description' => 'getIntro',
	);

	public function __construct(SCUpload $app) {
		$this->app = $app;
	}

	public function getLastRun() {
		return $this->app->run_config['feedreader']['last_run'] ?: 0;
	}

	public function setLastRun($lastRun) {
		$this->app->run_config['feedreader']['last_run'] = (int) $lastRun;
		$this->app->persistRunConfig();
		return $this;
	}

	/**
	 * Fetch the feed, get the entries, and narrow them down to those
	 * not seen since we last run the uploader task.
	 * @return array<SCUpload\Track>
	 */
	public function getTracks() {
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
		$tracks = array();
		foreach($fastFeed->fetch('default') as $item) {
			$tracks[] = $this->exportToTrack($item);
		}
		return $tracks;
	}

	/**
	 * @param Fastfeed\Item $item
	 * @return SCUpload\Track
	 */
	public function exportToTrack($item) {
		$track = new Track\Track($this->app);
		$fields = array();
		foreach(static::$track_item_map as $trackField => $itemMethod) {
			$fields[$trackField] = $item->$itemMethod();
		}
		$track->loadSoundcloud($fields);
		$track->setAudioSource($item->getExtra('media'));
		$track->setClientID(sha1($item->getId()));
		return $track;
	}

}
