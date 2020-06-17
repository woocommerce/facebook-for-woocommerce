<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook\API\FBE\Configuration;

defined( 'ABSPATH' ) or exit;

/**
 * The messenger configuration object.
 *
 * @since 2.0.0-dev.1
 */
class Messenger {


	/** @var bool whether messenger is enabled */
	private $enabled;

	/** @var string default locale */
	private $default_locale;

	/** @var string[] approved domains */
	private $domains = [];


	/**
	 * Messenger constructor.
	 *
	 * @param array $data configuration data
	 */
	public function __construct( array $data = [] ) {

		$data = wp_parse_args( $data, [
			'enabled'        => false,
			'default_locale' => '',
			'domains'        => [],
		] );

		$this->set_enabled( $data['enabled'] );
		$this->set_default_locale( $data['default_locale'] );
		$this->set_domains( $data['domains'] );
	}


	/**
	 * Determines if messenger is enabled.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return $this->enabled;
	}


	/**
	 * Gets the default locale.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string
	 */
	public function get_default_locale() {

		return $this->default_locale;
	}


	/**
	 * Gets the domains that messenger is configured for.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @return string[]
	 */
	public function get_domains() {

		if ( is_array( $this->domains ) ) {
			$domains = array_map( 'trailingslashit', $this->domains );
		} else {
			$domains = [];
		}

		return $domains;
	}


	/**
	 * Sets whether messenger is enabled.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param bool $is_enabled whether messenger is enabled
	 */
	public function set_enabled( $is_enabled ) {

		$this->enabled = (bool) $is_enabled;
	}


	/**
	 * Sets the default locale.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $locale default locale
	 */
	public function set_default_locale( $locale ) {

		$this->default_locale = (string) $locale;
	}


	/**
	 * Adds a domain to the list of approved domains.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string $domain domain to add
	 */
	public function add_domain( $domain ) {

		$domains = is_array( $this->domains ) ? $this->domains : [];

		$domains[] = $domain;

		$this->set_domains( $domains );
	}


	/**
	 * Sets the list of approved domains.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param string[] $domains list of domains
	 */
	public function set_domains( array $domains ) {

		$this->domains = array_unique( array_filter( $domains, 'wc_is_valid_url' ) );
	}


}
