<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\Pixel\Events;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\Events\Event;

/**
 * Base S2S API request object.
 *
 * @since 2.0.0-dev.1
 */
class Request extends API\Request {


	/** @var Event[] events to send */
	private $events;


	/**
	 * Request constructor.
	 *
	 * @param string $pixel_id
	 * @param Event[] $events events to send
	 */
	public function __construct( $pixel_id, array $events ) {

		$this->events = $events;

		parent::__construct( "/{$pixel_id}/events", 'POST' );
	}


	/**
	 * Gets the request data.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return array
	 */
	public function get_data() {

		$data = [
			'data' => [],
		];

		foreach ( $this->events as $event ) {

			if ( ! $event instanceof Event ) {
				continue;
			}

			$data['data'][] = $event->get_data();
		}

		return $data;
	}


}
