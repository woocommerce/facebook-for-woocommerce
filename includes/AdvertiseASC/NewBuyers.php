<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace WooCommerce\Facebook\AdvertiseASC;

use WooCommerce\Facebook\Framework\Plugin\Exception as PluginException;
use WooCommerce\Facebook\AdvertiseASC\AscNotSupportedException;
use WooCommerce\Facebook\AdvertiseASC\NonDiscriminationNotAcceptedException;
use WooCommerce\Facebook\Framework\Api\Exception as ApiException;

defined( 'ABSPATH' ) || exit;

/**
 * This class creates a wrapper for interacting with ASC NewBuyers campaign type.
 *
 * @since x.x.x
 */
class NewBuyers extends CampaignHandler {

	/** @var string holding the ID for this campangin type */
	const ID = 'new-buyers';

	/** @var string holding the FB Pixel ID */
	private $facebook_pixel_id;

	/** @var string the default message shown as Ad Message */
	private $default_creative_message;

	public function __construct() {
		$this->default_creative_message = __( 'Check out these great products!', 'facebook-for-woocommerce' );
		parent::__construct( 'New Buyers' );
	}

	protected function first_time_setup() {

		$this->facebook_pixel_id = $this->integration->get_facebook_pixel_id();

		try {

			$this->campaign = $this->setup_campaign();

		} catch ( ApiException $e ) {

			if ( $e->getCode() === 2 ) {

				throw new AscNotSupportedException();

			} else {

				$message = sprintf( 'An exception happened trying to setup the New Buyers campaign for the first time. ' . $e->getMessage() );
				\WC_Facebookcommerce_Utils::log( $message );
				throw new PluginException( $message );

			}
		}

		try {

			$this->adset      = $this->setup_adset( $this->facebook_pixel_id, $this->campaign['id'], $this->get_ad_daily_budget() * 100, $this->get_ad_targeting() );
			$this->ad         = $this->setup_ad( $this->adset['id'], $this->ad_name, $this->facebook_page_id, $this->default_creative_message, $this->default_product_set, $this->store_url );
			$this->adcreative = $this->fetch_adcreative( $this->ad['adcreatives']['data'][0]['id'] );

		} catch ( ApiException $e ) {

			$message = sprintf( 'An exception happened trying to setup the New Buyers campaign objects for the first time. ' . $e->getMessage() );
			\WC_Facebookcommerce_Utils::log( $message );
			if ( str_contains( $e->getMessage(), 'non-discrimination' ) ) {
				throw new NonDiscriminationNotAcceptedException();
			} else {
				throw new PluginException( $message );
			}
		}
	}

	public function get_tooltips(): array {

		return array(

			'p1' => __( 'Activating or pausing your campaign', 'facebook-for-woocommerce' ),
			'p2' => __( 'Text message shown in your ad', 'facebook-for-woocommerce' ),
			'p3' => __( "Products shown in your ad's carousel", 'facebook-for-woocommerce' ),
			'p4' => __( 'Countries where your campaign will be shown', 'facebook-for-woocommerce' ),
			'p5' => __( 'Your daily budget for this campaign', 'facebook-for-woocommerce' ),

		);

	}

	protected function get_id() {

		return self::ID;

	}

	private function setup_campaign() {

		$properties = array(
			'name'                  => $this->campaign_name,
			'special_ad_categories' => [],
			'objective'             => 'OUTCOME_SALES',
			'smart_promotion_type'  => 'AUTOMATED_SHOPPING_ADS',
			'status'                => 'PAUSED',
		);

		return $this->create_campaign( $properties );
	}

	private function setup_adset( $installed_pixel, $campaign_id, $daily_budget, $selected_countries ) {

		$properties = $this->get_adset_creation_params( $this->adset_name, $campaign_id, $installed_pixel, $daily_budget, $selected_countries );
		return $this->create_adset( $properties );

	}

	private function get_adset_creation_params( $adset_name, $campaign_id, $pixel_id, $daily_budget, $selected_countries ) {
		$promoted_object = array(
			'pixel_id'          => $pixel_id,
			'custom_event_type' => 'PURCHASE',
		);

		$targeting = array(
			'geo_locations' => array(
				'countries' => $selected_countries,
			),
		);

		return array(
			'name'                                => $adset_name,
			'campaign_id'                         => $campaign_id,
			'promoted_object'                     => $promoted_object,
			'daily_budget'                        => $daily_budget,
			'existing_customer_budget_percentage' => '0',
			'billing_event'                       => 'IMPRESSIONS',
			'bid_strategy'                        => 'LOWEST_COST_WITHOUT_CAP',
			'targeting'                           => $targeting,
		);
	}

	private function get_creative_creation_params( $ad_creative_name, $page_id, $store_url, $ad_message, $product_set_id ) {

		return array(
			'name'              => $ad_creative_name,
			'object_story_spec' => array(
				'page_id'       => $page_id,
				'template_data' => array(
					'description' => '{{product.price}}',
					'link'        => $store_url,
					'message'     => $ad_message,
					'name'        => '{{product.name | titleize}}',
				),
			),
			'product_set_id'    => $product_set_id,
		);

	}

	private function get_ad_creation_params( $ad_name, $adset_id, $creative ) {

		return array(
			'name'     => $ad_name,
			'adset_id' => $adset_id,
			'creative' => $creative,
		);

	}

	private function setup_ad( $adset_id, $ad_name, $page_id, $ad_description_message, $product_set_id, $store_url ) {

		$creative = $this->get_creative_creation_params( $this->ad_creative_name, $page_id, $store_url, $ad_description_message, $product_set_id );
		return $this->create_ad( $this->get_ad_creation_params( $ad_name, $adset_id, $creative ) );

	}

	protected function get_ad_message() {

		if ( $this->load_default ) {
			return $this->default_creative_message;
		}
		return $this->adcreative['body'];

	}

	public function update_asc_campaign( $data ) {

		if ( ! $data ) {
			return true;
		}

		$msg = array_key_exists( 'p2', $data ) ? $data['p2'] : '';

		if ( $msg ) {
			$this->apply_creative_changes( $msg );
		}

		$countries    = array_key_exists( 'p4', $data ) ? explode( ',', $data['p4'] ) : [];
		$daily_budget = array_key_exists( 'p5', $data ) ? $data['p5'] : '';

		if ( $countries || $daily_budget ) {
			$this->apply_adset_changes( $countries, $daily_budget );
		}

		if ( array_key_exists( 'p1', $data ) ) {
			$this->set_ad_status( $data['p1'] );
		}

		return true;
	}

	private function apply_creative_changes( $message ) {

		$ad_message      = $message ? $message : $this->adcreative['body'];
		$old_creative_id = $this->adcreative['id'];
		$old_ad_id       = $this->ad['id'];

		if ( $message ) {

			$creative_props = $this->get_creative_creation_params( $this->ad_creative_name, $this->facebook_page_id, $this->store_url, $ad_message, $this->default_product_set );

			$new_ad = '';
			try {

				$new_ad = $this->create_ad( $this->get_ad_creation_params( $this->ad_name, $this->adset['id'], $creative_props ) );

			} catch ( ApiException $e ) {

				$message = sprintf( 'Error applying creative changes .' . $e->getMessage() );
				\WC_Facebookcommerce_Utils::log( $message );
				throw new PluginException( $message );

			}

			$this->ad         = $new_ad;
			$this->adcreative = $this->fetch_adcreative( $this->ad['adcreatives']['data'][0]['id'] );

			$this->update_stored_data();

			$this->delete_item( $old_ad_id );
			$this->delete_item( $old_creative_id );
		}
	}

	private function apply_adset_changes( $countries, $daily_budget ) {

		$props            = [];
		$new_daily_budget = $daily_budget ? $daily_budget : $this->adset['daily_budget'];

		if ( $daily_budget ) {
			$props['daily_budget'] = $new_daily_budget * 100;
		}

		if ( $countries ) {
			$props['targeting'] = array(
				'geo_locations' => array(
					'countries' => $countries,
				),
			);
		}

		try {

			$this->update_adset( $props );

		} catch ( ApiException $e ) {

			$message = sprintf( 'Error applying adset changes .' . $e->getMessage() );
			\WC_Facebookcommerce_Utils::log( $message );
			throw new PluginException( $message );

		}

	}

	public function get_properties(): array {
		return array(
			'p1' => __( 'Off/On', 'facebook-for-woocommerce' ),
			'p2' => __( 'Ad Message', 'facebook-for-woocommerce' ),
			'p3' => '',
			'p4' => __( 'Country', 'facebook-for-woocommerce' ),
			'p5' => __( 'Daily Budget', 'facebook-for-woocommerce' ),
		);
	}

	public function get_info(): array {
		return array(
			'p1' => $this->get_ad_status(),
			'p2' => $this->get_ad_message(),
			'p3' => '',
			'p4' => $this->get_ad_targeting(),
			'p5' => $this->get_ad_daily_budget(),
		);
	}

	private function get_ad_targeting() {
		if ( $this->load_default ) {
			return [ 'US' ];
		}
		return $this->adset['targeting']['geo_locations']['countries'];
	}

	public function get_campaign_type(): string {
		return self::ID;
	}

	public function get_choices_for( string $property_name ): array {

		switch ( $property_name ) {
			case 'p4':
				return $this->get_countries();
			default:
				throw new PluginException( 'Invalid property name: ' . $property_name );
		}
	}
}
