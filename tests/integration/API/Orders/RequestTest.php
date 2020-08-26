<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders;

use SkyVerge\WooCommerce\Facebook\API\Orders\Request;

/**
 * Tests the API\Orders\Request class.
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
	 * @param array $args constructor args
	 * @param array $expected_params expected request params
	 *
	 * @dataProvider provider_constructor
	 */
	public function test_constructor( $args, $expected_params ) {

		$request = new Request( '1234', $args );

		$this->assertEquals( '/1234/commerce_orders', $request->get_path() );
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
			[[ 'updated_before' => '1565728779' ], [ 'updated_before' => '1565728779', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'updated_after' => '1565728779' ], [ 'updated_after' => '1565728779', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'state' => 'CREATED' ], [ 'state' => 'CREATED', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'state' => [ 'CREATED', 'IN_PROGRESS' ] ], [ 'state' => 'CREATED,IN_PROGRESS', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'state' => 'CREATED,IN_PROGRESS' ], [ 'state' => 'CREATED,IN_PROGRESS', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'filters' => 'no_shipments' ], [ 'filters' => 'no_shipments', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'filters' => [ 'no_shipments', 'has_cancellations' ] ], [ 'filters' => 'no_shipments,has_cancellations', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'filters' => 'no_shipments,has_cancellations' ], [ 'filters' => 'no_shipments,has_cancellations', 'fields' => implode( ',', $all_fields ) ]],
			[[ 'fields' => [ 'id' ] ], [ 'fields' => 'id' ]],
			[[ 'fields' => [ 'id', 'order_status' ] ], [ 'fields' => 'id,order_status' ]],
			[[ 'fields' => 'id,order_status' ], [ 'fields' => 'id,order_status' ]],
		];
	}


}
