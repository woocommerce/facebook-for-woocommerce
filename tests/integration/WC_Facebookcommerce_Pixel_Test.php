<?php

/**
 * Tests the WC_Facebookcommerce_Pixel class.
 */
class WC_Facebookcommerce_Pixel_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {


	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see \WC_Facebookcommerce_Pixel::build_event()
	 *
	 * @param string $event_name event name
	 * @param array $params event params
	 * @param array $contains set of strings the event is expected to contain
	 * @param array $does_not_contain set of strings the event is expected not to contain
	 *
	 * @dataProvider provider_build_event
	 */
	public function test_build_event( $event_name, $params, $contains, $does_not_contain ) {

		$event = WC_Facebookcommerce_Pixel::build_event( $event_name, $params );

		foreach ( $contains as $string ) {
			$this->assertStringContainsString( $string, $event );
		}

		foreach ( $does_not_contain as $string ) {
			$this->assertStringNotContainsString( $string, $event );
		}
	}


	/** @see test_build_event */
	public function provider_build_event() {

		$plugin_version = facebook_for_woocommerce()->get_version();

		return [
			'old_format'  => [
				'AddToCart',
				[
					'source'       => 'woocommerce',
					'version'      => '4.1.1',
					'content_ids'  => [ 'wc_post_id_5518' ],
					'content_type' => 'product',
					'contents'     => [ 'id' => 'wc_post_id_5518', 'quantity' => 5 ],
					'value'        => '70.00',
					'currency'     => 'CAD',
				],
				[
					'/* WooCommerce Facebook Integration Event Tracking */',
					'fbq(\'set\', \'agent\', ',
					'fbq(\'track\', \'AddToCart\', {',
					'"source": "woocommerce",',
					'"version": "4.1.1",',
					'"pluginVersion": "' . $plugin_version . '",',
					'"content_ids": {',
					'"content_type": "product",',
					'"contents": {',
					'"id": "wc_post_id_5518",',
					'"quantity": 5',
				],
				[],
			],
			'event_name'  => [
				'AddToCart',
				[
					'event_name'   => 'Name',
					'source'       => 'woocommerce',
					'version'      => '4.1.1',
					'content_ids'  => [ 'wc_post_id_5518' ],
					'content_type' => 'product',
					'contents'     => [ 'id' => 'wc_post_id_5518', 'quantity' => 5 ],
					'value'        => '70.00',
					'currency'     => 'CAD',
				],
				[
					'/* WooCommerce Facebook Integration Event Tracking */',
					'fbq(\'set\', \'agent\', ',
					'fbq(\'track\', \'AddToCart\', {',
					'"source": "woocommerce",',
					'"version": "4.1.1",',
					'"pluginVersion": "' . $plugin_version . '",',
					'"content_ids": {',
					'"content_type": "product",',
					'"contents": {',
					'"id": "wc_post_id_5518",',
					'"quantity": 5',
				],
				[
					'Name',
				],
			],
			'event_id'    => [
				'AddToCart',
				[
					'event_id'     => '123456',
					'source'       => 'woocommerce',
					'version'      => '4.1.1',
					'content_ids'  => [ 'wc_post_id_5518' ],
					'content_type' => 'product',
					'contents'     => [ 'id' => 'wc_post_id_5518', 'quantity' => 5 ],
					'value'        => '70.00',
					'currency'     => 'CAD',
				],
				[
					'/* WooCommerce Facebook Integration Event Tracking */',
					'fbq(\'set\', \'agent\', ',
					'fbq(\'track\', \'AddToCart\', {',
					'"source": "woocommerce",',
					'"version": "4.1.1",',
					'"pluginVersion": "' . $plugin_version . '",',
					'"content_ids": {',
					'"content_type": "product",',
					'"contents": {',
					'"id": "wc_post_id_5518",',
					'"quantity": 5',
					'"eventID": "123456"'
				],
				[],
			],
			'custom_data' => [
				'AddToCart',
				[
					'other'       => 'data',
					'custom_data' => [
						'source'       => 'woocommerce',
						'version'      => '4.1.1',
						'content_ids'  => [ 'wc_post_id_5518' ],
						'content_type' => 'product',
						'contents'     => [ 'id' => 'wc_post_id_5518', 'quantity' => 5 ],
						'value'        => '70.00',
						'currency'     => 'CAD',
					],
				],
				[
					'/* WooCommerce Facebook Integration Event Tracking */',
					'fbq(\'set\', \'agent\', ',
					'fbq(\'track\', \'AddToCart\', {',
					'"source": "woocommerce",',
					'"version": "4.1.1",',
					'"pluginVersion": "' . $plugin_version . '",',
					'"content_ids": {',
					'"content_type": "product",',
					'"contents": {',
					'"id": "wc_post_id_5518",',
					'"quantity": 5',
				],
				[
					'custom_data',
					'other',
				],
			],
		];
	}


}
