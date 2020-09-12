<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Pixel;

defined( 'ABSPATH' ) or exit;

/**
 * Base Automatic advanced matching  settings object
 *
 */
class AAMSettings {

	/** @var bool is enable automatic matching enabled for this pixel */
	private $enable_automatic_matching;

	/**,@var string[] personal identifiable information to extract when $enable_automatic_matching is true*/
	private $enabled_automatic_matching_fields;

	const SIGNALS_JSON_CONFIG_PATH = 'signals/config/json';

	const CONNECT_FACEBOOK_DOMAIN = 'https://connect.facebook.net/';

	/**
	 * AAMSettings constructor
	 *
	 * @param array $data
	 */
	public function __construct( $data = array() ) {
		$this->enable_automatic_matching = isset($data['enableAutomaticMatching']) ? $data['enableAutomaticMatching'] : null;
		$this->enabled_automatic_matching_fields =	isset($data['enabledAutomaticMatchingFields']) ? $data['enabledAutomaticMatchingFields'] : null;
	}

	public static function get_url($pixel_id){
		return self::CONNECT_FACEBOOK_DOMAIN.self::SIGNALS_JSON_CONFIG_PATH.'/'.$pixel_id;
	}

	/**
	 * Factory method that builds an AAMSettings object given a pixel id
	 * by sending a request to connect.facebook.net domain
	*/
	public static function build_from_pixel_id( $pixel_id ){
		$url = self::get_url($pixel_id);
		$response = wp_remote_get($url);
		if ( is_wp_error( $response ) ) {
			return null;
		}
		else{
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
			if (!array_key_exists('errorMessage', $response_body)){
        return new AAMSettings($response_body['matchingConfig']);
      }
		}
		return null;
	}

	/**
	 * @return bool
	 */
	public function get_enable_automatic_matching(){
		return $this->enable_automatic_matching;
	}

	/**
	 * @return string[]
	 */
	public function get_enabled_automatic_matching_fields(){
		return $this->enabled_automatic_matching_fields;
	}

	/**
	 * @return AAMSettings
	 */
	public function set_enable_automatic_matching($enable_automatic_matching){
		$this->enable_automatic_matching = $enable_automatic_matching;
		return $this;
	}

	/**
	 * @return AAMSettings
	 */
	public function set_enabled_automatic_matching_fields($enable_automatic_matching_fields){
		$this->enabled_automatic_matching_fields = $enabled_automatic_matching_fields;
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		return json_encode(
			array(
				'enableAutomaticMatching' => $this->enable_automatic_matching,
				'enabledAutomaticMatchingFields' => $this->enabled_automatic_matching_fields
			)
		);
	}
}
