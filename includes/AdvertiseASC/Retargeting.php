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
use WooCommerce\Facebook\Framework\Api\Exception as ApiException;

defined( 'ABSPATH' ) || exit;

/**
 * This class creates a wrapper for interacting with ASC Retargeting campaign type.
 *
 * @since x.x.x
 */
class Retargeting extends CampaignHandler {

	/** @var string holding the ID for this campangin type */
	const ID = 'retargeting';

	/** @var string the default message shown as Ad Message */
	private $default_creative_message = 'These great products are still waiting for you!';

	public function __construct() {
		$this->default_creative_message = __( 'These great products are still waiting for you!', 'facebook-for-woocommerce' );
		parent::__construct( 'Retargeting' );
	}

	public function get_tooltips(): array {
		return array(
			'p1' => __( 'Activating or pausing your campaign', 'facebook-for-woocommerce' ),
			'p2' => __( 'Text message shown in your ad', 'facebook-for-woocommerce' ),
			'p3' => __( "Products shown in your ad's carousel", 'facebook-for-woocommerce' ),
			'p4' => __( 'Time where users visited your website', 'facebook-for-woocommerce' ),
			'p5' => __( 'Your daily budget for this campaign', 'facebook-for-woocommerce' ),
		);
	}

	protected function get_id() {

		return self::ID;

	}

	protected function first_time_setup() {

		try {

			$this->campaign   = $this->setup_campaign( $this->product_catalog_id );
			$this->adset      = $this->setup_adset( $this->campaign['id'], $this->bid_amount, $this->get_ad_daily_budget() * 100, $this->default_product_set, 1814400 );
			$this->adcreative = $this->setup_adcreative( $this->ad_creative_name, $this->facebook_page_id, $this->instagram_actor_id, $this->store_url, $this->default_creative_message, $this->default_product_set );
			$this->ad         = $this->setup_ad();

		} catch ( ApiException $e ) {

			$message = sprintf( 'An exception happened trying to setup the Retargeting campaign for the first time. ' . $e->getMessage() );
			\WC_Facebookcommerce_Utils::log( $message );
			throw new PluginException( $message );

		}
	}

	public function update_asc_campaign( $data ) {

		$new_message      = array_key_exists( 'p2', $data ) ? $data['p2'] : '';
		$new_visit_period = array_key_exists( 'p4', $data ) ? $data['p4'] : '';
		$new_daily_budget = array_key_exists( 'p5', $data ) ? $data['p5'] : '';

		if ( $new_daily_budget || $new_visit_period ) {
			$this->apply_adset_changes( $new_daily_budget * 100, $new_visit_period );
		}

		if ( $new_message ) {
			$this->apply_adcreative_changes( $new_message );
		}

		if ( array_key_exists( 'p1', $data ) ) {
			$this->set_ad_status( $data['p1'] );
		}

		return true;
	}

	private function apply_adcreative_changes( $new_message ) {

		$old_creative_id = $this->adcreative['id'];
		$message         = $new_message ? $new_message : $this->adcreative['body'];

		if ( $new_message ) {

			$new_ad_creative = '';
			try {

				$new_ad_creative = $this->setup_adcreative( $this->ad_creative_name, $this->facebook_page_id, $this->instagram_actor_id, $this->store_url, $message, $this->default_product_set );
				$this->update_ad( array( 'creative' => array( 'creative_id' => $this->adcreative['id'] ) ) );

			} catch ( ApiException $e ) {

				$message = sprintf( 'There was an error trying to apply the changes to adcreative and ad. ' . $e->getMessage() );
				\WC_Facebookcommerce_Utils::log( $message );
				throw new PluginException( $message );

			}

			$this->adcreative = $new_ad_creative;

			$this->update_stored_data();

			try {
				$this->delete_item( $old_creative_id );
			} catch ( ApiException $e ) {

				$message = sprintf( 'Unable to delete item: ' . $old_creative_id . '. error: ' . $e->getMessage() );
				\WC_Facebookcommerce_Utils::log( $message );
			}
		}
	}

	private function apply_adset_changes( $daily_budget, $visit_period ) {

		$new_daily_budget     = $daily_budget ? $daily_budget : $this->adset['daily_budget'];
		$visit_period_seconds = ( $visit_period ? $visit_period : $this->get_ad_last_visit_period() ) * 24 * 3600;

		if ( $daily_budget || $visit_period ) {
			$props                 = [];
			$props['daily_budget'] = $new_daily_budget;
			$props['targeting']    = $this->get_adset_targeting_creation_params( $this->default_product_set, $visit_period_seconds );
			try {

				$this->update_adset( $props );
				$this->update_stored_data();

			} catch ( ApiException $e ) {

				$message = sprintf( 'There was an error trying to apply the changes to the adset.' . $e->getMessage() );
				\WC_Facebookcommerce_Utils::log( $message );
				throw new PluginException( $message );

			}
		}
	}

	protected function setup_campaign( string $catalog_id ) {

		$properties = array(
			'name'                  => $this->campaign_name,
			'objective'             => 'PRODUCT_CATALOG_SALES',
			'special_ad_categories' => array(),
			'promoted_object'       => array(
				'product_catalog_id' => $catalog_id,
			),
			'status'                => 'PAUSED',
		);

		return $this->create_campaign( $properties );
	}

	private function get_adset_targeting_creation_params( $product_set_id, $seconds ) {

		return array(
			'age_max'                => 65,
			'age_min'                => 18,
			'targeting_optimization' => 'none',
			'product_audience_specs' => array(
				array(
					'product_set_id' => $product_set_id,
					'inclusions'     => array(
						array(
							'retention_seconds' => $seconds,
							'rule'              => array(
								'event' => array(
									'eq' => 'ViewContent',
								),
							),
						),
						array(
							'retention_seconds' => $seconds,
							'rule'              => array(
								'event' => array(
									'eq' => 'AddToCart',
								),
							),
						),
					),
					'exclusions'     => array(
						array(
							'retention_seconds' => $seconds,
							'rule'              => array(
								'event' => array(
									'eq' => 'Purchase',
								),
							),
						),
					),
				),
			),
		);
	}

	private function setup_adset( $campaign_id, $bid_amount, $daily_budget, $product_set_id, $visit_period_seconds ) {

		$params = array(
			'name'              => $this->adset_name,
			'bid_amount'        => $bid_amount,
			'billing_event'     => 'IMPRESSIONS',
			'daily_budget'      => $daily_budget,
			'optimization_goal' => 'OFFSITE_CONVERSIONS',
			'campaign_id'       => $campaign_id,
			'promoted_object'   => array(
				'product_set_id' => $product_set_id,
			),
			'status'            => 'PAUSED',
			'targeting'         => $this->get_adset_targeting_creation_params( $product_set_id, $visit_period_seconds ),
		);

		return $this->create_adset( $params );
	}

	private function get_adcreative_creation_params( $name, $page_id, $instagram_actor_id, $link, $message, $product_set_id ) {

		$properties = array(
			'name'              => $name,
			'product_set_id'    => $product_set_id,
			'object_story_spec' => array(
				'page_id'              => $page_id,
				'instagram_actor_id'   => $instagram_actor_id,
				'multi_share_end_card' => true,
				'show_multiple_images' => false,
				'template_data'        => array(
					'description'    => '{{product.current_price strip_zeros}}',
					'link'           => $link,
					'message'        => $message,
					'name'           => '{{product.name | titleize}}',
					'call_to_action' => array( 'type' => 'SHOP_NOW' ),
				),

			),
		);

		return $properties;
	}

	private function setup_adcreative( string $ad_creative_name, string $page_id, string $instagram_actor_id, string $link, string $message, string $product_set_id ) {

		$properties = $this->get_adcreative_creation_params( $ad_creative_name, $page_id, $instagram_actor_id, $link, $message, $product_set_id );

		return $this->create_adcreative( $properties );

	}

	protected function get_ad_message() {

		if ( $this->load_default ) {

			return $this->default_creative_message;

		}

		return $this->adcreative['body'];

	}

	private function setup_ad() {

		$properties = array(
			'name'     => $this->ad_name,
			'adset_id' => $this->adset['id'],
			'creative' => array( 'creative_id' => $this->adcreative['id'] ),
			'status'   => parent::STATUS_PAUSED,
		);

		return $this->create_ad( $properties );
	}

	public function get_properties(): array {

		return array(
			'p1' => __( 'Off/On', 'facebook-for-woocommerce' ),
			'p2' => __( 'Ad Message', 'facebook-for-woocommerce' ),
			'p3' => '',
			'p4' => __( 'Visit Period', 'facebook-for-woocommerce' ),
			'p5' => __( 'Daily Budget', 'facebook-for-woocommerce' ),
		);

	}

	public function get_info(): array {

		return array(
			'p1' => $this->get_ad_status(),
			'p2' => $this->get_ad_message(),
			'p3' => '',
			'p4' => $this->get_ad_last_visit_period(),
			'p5' => $this->get_ad_daily_budget(),
		);

	}

	public function get_campaign_type(): string {

		return self::ID;

	}

	private function get_ad_last_visit_period() {

		if ( $this->load_default ) {

			return reset( $this->get_visit_periods() );
		}

		$val = ( $this->adset['targeting']['product_audience_specs'][0]['inclusions'][0]['retention_seconds'] / 3600 ) / 24;

		return $val;
	}

	public function get_choices_for( string $property_name ): array {

		switch ( $property_name ) {
			case 'p4':
				return $this->get_visit_periods();
			default:
				throw new PluginException( 'Invalid property name: ' . $property_name );
		}

	}

	protected function get_visit_periods(): array {

		return array(
			1  => 'Yesterday',
			7  => 'Last 7 days',
			14 => 'Last 14 days',
			21 => 'Last 21 days',
			28 => 'Last 28 days',
		);
	}
}
