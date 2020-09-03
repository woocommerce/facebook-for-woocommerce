<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders\Read;

use SkyVerge\WooCommerce\Facebook\API\Orders\Read\Request;

/**
 * Tests the API\Orders\Read\Request class.
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


	/**
	 * @see Request::__construct()
	 *
	 * @param array $fields constructor fields arg
	 * @param array $expected_params expected request params
	 *
	 * @dataProvider provider_constructor
	 */
	public function test_constructor( $fields, $expected_params ) {

		$request = new Request( '368508827392800', $fields );

		$this->assertEquals( '/368508827392800', $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
		$this->assertEquals( $expected_params, $request->get_params() );
	}


	/** @see test_constructor */
	public function provider_constructor() {

		$all_fields = [
			'id',
			'order_status',
			'created',
			'last_updated',
			'items',
			'ship_by_date',
			'merchant_order_id',
			'channel',
			'selected_shipping_option',
			'shipping_address',
			'estimated_payment_details',
			'buyer_details',
		];

		return [

			[[], [ 'fields' => implode( ',', $all_fields ) ]],
			[[ 'id', 'order_status' ], [ 'fields' => 'id,order_status' ]],
			[[ 'id,order_status' ], [ 'fields' => 'id,order_status' ]],
		];
	}


	/** @see Request::get_rate_limit_id() */
	public function test_get_rate_limit_id() {

		$this->assertEquals( 'pages', Request::get_rate_limit_id() );
	}


}
