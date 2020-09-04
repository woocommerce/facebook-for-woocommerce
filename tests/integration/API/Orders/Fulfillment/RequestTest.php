<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders\Fulfillment;

use SkyVerge\WooCommerce\Facebook\API\Orders\Fulfillment\Request;

/**
 * Tests the API\Orders\Fulfillment\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$fulfillment_data = [
			'items'         => [
				[
					'retailer_id' => 'FB_product_1238',
					'quantity'    => 1,
				],
				[
					'retailer_id' => 'FB_product_5624',
					'quantity'    => 2,
				],
			],
			'tracking_info' => [
				'tracking_number'      => 'ship 1',
				'carrier'              => 'FEDEX',
				'shipping_method_name' => '2 Day Fedex',
			],
		];

		$request = new Request( '368508827392800', $fulfillment_data );

		$this->assertEquals( '/368508827392800/shipments', $request->get_path() );
		$this->assertEquals( 'POST', $request->get_method() );

		$expected_data                    = $fulfillment_data;
		$expected_data['idempotency_key'] = $request->get_idempotency_key();
		$this->assertEquals( $expected_data, $request->get_data() );
	}


	/** @see Request::get_rate_limit_id() */
	public function test_get_rate_limit_id() {

		$this->assertEquals( 'pages', Request::get_rate_limit_id() );
	}


}
