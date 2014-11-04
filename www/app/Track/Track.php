<?php

/**
 * Track
 * A prepared set of data ready to go to Soundcloud
 * @author @willmorgan
 */

namespace SCUpload\Track;

use SCUpload\SCUpload;

class Track {

	/**
	 * See https://developers.soundcloud.com/docs/api/reference#tracks
	 * Delivered to SCUpload in JSON format
	 * @var array
	 */
	protected $sc_metadata = array(
		'id'					=> null,
		'created_at'			=> null,
		'user_id'				=> null,
		'user'					=> null,
		'title'					=> null,
		'permalink'				=> null,
		'permalink_url'			=> null,
		'uri'					=> null,
		'sharing'				=> 'private',
		'embeddable_by'			=> null,
		'purchase_url'			=> null,
		'artwork_url'			=> null,
		'description'			=> null,
		'label'					=> null,
		'duration'				=> null,
		'genre'					=> null,
		'shared_to_count'		=> null,
		'tag_list'				=> null,
		'label_id'				=> null,
		'label_name'			=> null,
		'release'				=> null,
		'release_day'			=> null,
		'release_month'			=> null,
		'release_year'			=> null,
		'streamable'			=> null,
		'downloadable'			=> null,
		'license'				=> 'no-rights-reserved',
		'track_type'			=> 'podcast',
		'waveform_url'			=> null,
		'download_url'			=> null,
		'stream_url'			=> null,
		'video_url'				=> null,
		'bpm'					=> null,
		'commentable'			=> null,
		'isrc'					=> null,
		'key_signature'			=> null,
		'comment_count'			=> null,
		'download_count'		=> null,
		'playback_count'		=> null,
		'favoritings_count'		=> null,
		'original_format'		=> null,
		'original_content_size'	=> null,
		'created_with'			=> null,
		/*
		'asset_data'			=> null,
		'artwork_data'			=> null,
		'user_favorite'			=> null,
		*/
	);

	/**
	 * sha1 ID for use when mediating between the archive and soundcloud
	 * @var string
	 */
	protected $client_id;

	/**
	 * @var string URL or path to filename - contents used with PUT asset_data
	 */
	protected $audio_source;

	/**
	 * @var string URL or path to image - contents used with PUT artwork_data
	 */
	protected $image_source;

	public function loadSoundcloud(array $data) {
		$this->sc_metadata = array_merge($this->sc_metadata, $data);
		return $this;
	}

	public function setAudioSource($source) {
		$this->audio_source = $source;
		return $this;
	}

	public function getAudioSource() {
		return $this->audio_source;
	}

	public function setImageSource($source) {
		$this->image_source = $source;
		return $this;
	}

	public function setClientID($hash) {
		$this->client_id = $hash;
		return $this;
	}

	public function getClientID() {
		return $this->client_id;
	}

	public function getSoundcloudData() {
		return $this->sc_metadata;
	}

	/**
	 * Wrap the values in track[] for the Soundcloud API
	 * @return array
	 */
	public function getCreateData() {
		$data = array();
		foreach($this->sc_metadata as $key => $value) {
			if(!is_null($value)) {
				$data['track['.$key.']'] = $value;
			}
		}
		return $data;
	}

}
