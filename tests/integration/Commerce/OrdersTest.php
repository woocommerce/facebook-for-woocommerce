<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\Commerce;

use ReflectionProperty;
use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Orders\Order;
use SkyVerge\WooCommerce\Facebook\Commerce;
use SkyVerge\WooCommerce\Facebook\Commerce\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_9_0\SV_WC_API_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_9_0\SV_WC_Plugin_Exception;

/**
 * Tests the general Commerce orders handler class.
 */
class OrdersTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );
		facebook_for_woocommerce()->get_connection_handler()->update_page_access_token( 'fake_page_access_token' );
		facebook_for_woocommerce()->get_connection_handler()->update_commerce_manager_id( 'fake_commerce_manager_id' );
		// This would usually covered as a hook on the init action but if the acceptance
		// test setup doesn't already have a page acess token or commerece manager id
		// then it'll have been skipped.
		$this->get_commerce_orders_handler()->schedule_local_orders_update();

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Orders::is_order_pending()
	 *
	 * @param string $created_via created via value
	 * @param string $status WC order status value
	 * @param bool $expected expected result
	 *
	 * @dataProvider provider_is_order_pending
	 *
	 * @throws \WC_Data_Exception
	 */
	public function test_is_order_pending( $created_via, $status, $expected ) {

		$order = new \WC_Order();
		$order->set_created_via( $created_via );
		$order->set_status( $status );
		$order->save();

		$this->assertEquals( $expected, Orders::is_order_pending( $order ) );
	}


	/** @see test_is_order_pending */
	public function provider_is_order_pending() {

		return [
			[ 'checkout', 'pending', false ],
			[ 'checkout', 'processing', false ],
			[ 'instagram', 'pending', true ],
			[ 'instagram', 'processing', false ],
			[ 'facebook', 'pending', true ],
			[ 'facebook', 'processing', false ],
		];
	}


	/**
	 * @see Orders::is_commerce_order()
	 *
	 * @param string $created_via created via value
	 * @param bool $expected expected result
	 *
	 * @dataProvider provider_is_commerce_order
	 *
	 * @throws \WC_Data_Exception
	 */
	public function test_is_commerce_order( $created_via, $expected ) {

		$order = new \WC_Order();
		$order->set_created_via( $created_via );
		$order->save();

		$this->assertEquals( $expected, Orders::is_commerce_order( $order ) );
	}


	/** @see test_is_commerce_order */
	public function provider_is_commerce_order() {

		return [
			[ 'checkout', false ],
			[ 'instagram', true ],
			[ 'facebook', true ],
		];
	}


	/** @see Orders::find_local_order() */
	public function test_find_local_order_found() {

		$order = new \WC_Order();
		$order->save();

		$remote_id = 'FAKE_REMOTE_ID_1';

		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, $remote_id );
		$order->save_meta_data();

		$this->assertInstanceOf( \WC_Order::class, $this->get_commerce_orders_handler()->find_local_order( $remote_id ) );
		$this->assertEquals( $order->get_id(), $this->get_commerce_orders_handler()->find_local_order( $remote_id )->get_id() );
	}


	/** @see Orders::find_local_order() */
	public function test_find_local_order_not_found() {

		$order = new \WC_Order();
		$order->save();

		$remote_id           = 'FAKE_REMOTE_ID_1';
		$different_remote_id = 'FAKE_REMOTE_ID_2';

		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, $different_remote_id );
		$order->save_meta_data();

		$this->assertNull( $this->get_commerce_orders_handler()->find_local_order( $remote_id ) );
	}


	/** @see Orders::create_local_order() */
	public function test_create_local_order() {

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_PROCESSING, $product );
		$remote_order  = new Order( $response_data );

		$local_order = $this->get_commerce_orders_handler()->create_local_order( $remote_order );
		$this->assertInstanceOf( \WC_Order::class, $local_order );
		$this->assertEquals( $response_data['channel'], $local_order->get_created_via() );
		$this->assertEquals( 'pending', $local_order->get_status() );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_created() {

		$order = new \WC_Order();
		$order->save();

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, $product );
		$remote_order  = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		// get a fresh order
		$updated_local_order = wc_get_order( $updated_local_order->get_id() );

		$order_items = $updated_local_order->get_items();
		$this->assertCount( 1, $order_items );

		/** @var \WC_Order_Item_Product $first_order_item */
		$first_order_item = current( $order_items );
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $first_order_item );
		$this->assertEquals( $response_data['items']['data'][0]['retailer_id'], \WC_Facebookcommerce_Utils::get_fb_retailer_id( $first_order_item->get_product() ) );
		$this->assertEquals( $response_data['items']['data'][0]['quantity'], $first_order_item->get_quantity() );
		$this->assertEquals( $response_data['items']['data'][0]['quantity'] * $response_data['items']['data'][0]['price_per_unit']['amount'], $first_order_item->get_subtotal() );
		$this->assertEquals( $response_data['items']['data'][0]['tax_details']['estimated_tax']['amount'], $first_order_item->get_total_tax() );

		$shipping_items = $updated_local_order->get_items('shipping');
		$this->assertCount( 1, $shipping_items );
		/** @var \WC_Order_Item_Shipping $shipping_item */
		$shipping_item = current( $shipping_items );
		$this->assertInstanceOf( \WC_Order_Item_Shipping::class, $shipping_item );
		$this->assertEquals( $response_data['selected_shipping_option']['name'], $shipping_item->get_method_title() );
		$this->assertEquals( $response_data['selected_shipping_option']['price']['amount'], $shipping_item->get_total() );
		$this->assertEquals( $response_data['selected_shipping_option']['calculated_tax']['amount'], $shipping_item->get_total_tax() );

		$tax_order_items = $updated_local_order->get_items( 'tax' );
		$this->assertCount( 1, $tax_order_items );

		/** @var \WC_Order_Item_Tax $first_tax_order_item */
		$first_tax_order_item = current( $tax_order_items );
		$this->assertInstanceOf( \WC_Order_Item_Tax::class, $first_tax_order_item );
		$this->assertEquals( $response_data['items']['data'][0]['tax_details']['estimated_tax']['amount'], $first_tax_order_item->get_tax_total() );
		$this->assertEquals( $response_data['selected_shipping_option']['calculated_tax']['amount'], $first_tax_order_item->get_shipping_tax_total() );

		$this->assertEquals( $response_data['selected_shipping_option']['price']['amount'], $updated_local_order->get_shipping_total() );
		$this->assertEquals( $response_data['selected_shipping_option']['calculated_tax']['amount'], $updated_local_order->get_shipping_tax() );

		$this->assertEquals( 'John', $updated_local_order->get_shipping_first_name() );
		$this->assertEquals( 'Doe', $updated_local_order->get_shipping_last_name() );
		$this->assertEquals( $response_data['shipping_address']['street1'], $updated_local_order->get_shipping_address_1() );
		$this->assertEquals( $response_data['shipping_address']['street2'], $updated_local_order->get_shipping_address_2() );
		$this->assertEquals( $response_data['shipping_address']['city'], $updated_local_order->get_shipping_city() );
		$this->assertEquals( $response_data['shipping_address']['state'], $updated_local_order->get_shipping_state() );
		$this->assertEquals( $response_data['shipping_address']['postal_code'], $updated_local_order->get_shipping_postcode() );
		$this->assertEquals( $response_data['shipping_address']['country'], $updated_local_order->get_shipping_country() );

		$this->assertEquals( $response_data['estimated_payment_details']['total_amount']['amount'], $updated_local_order->get_total() );
		$this->assertEquals( $response_data['estimated_payment_details']['total_amount']['currency'], $updated_local_order->get_currency() );

		$this->assertEquals( 'John', $updated_local_order->get_billing_first_name() );
		$this->assertEquals( 'Doe', $updated_local_order->get_billing_last_name() );
		$this->assertEquals( $response_data['buyer_details']['email'], $updated_local_order->get_billing_email() );
		$this->assertEquals( $response_data['buyer_details']['email_remarketing_option'], wc_string_to_bool( $updated_local_order->get_meta( Orders::EMAIL_REMARKETING_META_KEY ) ) );

		$this->assertEquals( $response_data['id'], $updated_local_order->get_meta( Orders::REMOTE_ID_META_KEY ) );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_created_username() {

		$order = new \WC_Order();
		$order->save();

		$product = $this->tester->get_product();

		$response_data                             = $this->get_test_response_data( Order::STATUS_CREATED, $product );
		$response_data['shipping_address']['name'] = 'johndoe';
		$response_data['buyer_details']['name']    = 'johndoe';
		$remote_order                              = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		// get a fresh order
		$updated_local_order = wc_get_order( $updated_local_order->get_id() );

		$this->assertEquals( '', $updated_local_order->get_shipping_first_name() );
		$this->assertEquals( 'johndoe', $updated_local_order->get_shipping_last_name() );

		$this->assertEquals( '', $updated_local_order->get_billing_first_name() );
		$this->assertEquals( 'johndoe', $updated_local_order->get_billing_last_name() );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_created_middle_name() {

		$order = new \WC_Order();
		$order->save();

		$product = $this->tester->get_product();

		$response_data                             = $this->get_test_response_data( Order::STATUS_CREATED, $product );
		$response_data['shipping_address']['name'] = 'John James Doe';
		$response_data['buyer_details']['name']    = 'John James Doe';
		$remote_order                              = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		// get a fresh order
		$updated_local_order = wc_get_order( $updated_local_order->get_id() );

		$this->assertEquals( 'John', $updated_local_order->get_shipping_first_name() );
		$this->assertEquals( 'James Doe', $updated_local_order->get_shipping_last_name() );

		$this->assertEquals( 'John', $updated_local_order->get_billing_first_name() );
		$this->assertEquals( 'James Doe', $updated_local_order->get_billing_last_name() );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_fb_processing() {

		$order = new \WC_Order();
		$order->save();

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_PROCESSING, $product );
		$remote_order  = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		$this->assertEquals( 'pending', $updated_local_order->get_status() );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_existing_item() {

		$order = new \WC_Order();
		$order->save();

		$product = $this->tester->get_product();

		$order_item = new \WC_Order_Item_Product();
		$order_item->set_product_id( $product->get_id() );
		$order_item->set_quantity( 1 );
		$order_item->save();
		$order->add_item( $order_item );
		$order->save();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, $product );
		$remote_order  = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		$order_items = $updated_local_order->get_items();
		$this->assertCount( 1, $order_items );

		/** @var \WC_Order_Item_Product $first_order_item */
		$first_order_item = current( $order_items );
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $first_order_item );
		$this->assertEquals( $response_data['items']['data'][0]['retailer_id'], \WC_Facebookcommerce_Utils::get_fb_retailer_id( $first_order_item->get_product() ) );
		$this->assertEquals( $response_data['items']['data'][0]['quantity'], $first_order_item->get_quantity() );
		$this->assertEquals( $response_data['items']['data'][0]['quantity'] * $response_data['items']['data'][0]['price_per_unit']['amount'], $first_order_item->get_subtotal() );
		$this->assertEquals( $response_data['items']['data'][0]['tax_details']['estimated_tax']['amount'], $first_order_item->get_total_tax() );
		$this->assertEquals( $response_data['items']['data'][0]['tax_details']['estimated_tax']['amount'], $first_order_item->get_total_tax() );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_product_not_found() {

		$order = new \WC_Order();
		$order->save();

		$response_data = $this->get_test_response_data( Order::STATUS_PROCESSING );
		$remote_order  = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		// item was skipped
		$this->assertCount( 0, $updated_local_order->get_items() );
	}


	/** @see Orders::update_local_orders() */
	public function test_update_local_orders_error_fetching() {

		// mock the API to throw an exception
		$api = $this->make( API::class, [
			'get_new_orders' => function( $page_id ) {

				throw new SV_WC_API_Exception( 'Error' );
			},
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		// test will fail if find_local_order() is called
		$orders_handler = $this->make( Orders::class, [
			'find_local_order' => \Codeception\Stub\Expected::never(),
		] );

		$orders_handler->update_local_orders();
	}


	/** @see Orders::update_local_orders() */
	public function test_update_local_orders_create() {

		// ensure Commerce is connected
		facebook_for_woocommerce()->get_connection_handler()->update_page_access_token( '1234' );
		facebook_for_woocommerce()->get_connection_handler()->update_commerce_manager_id( '1234' );

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, $product );

		// mock the API to return a test response
		$api = $this->make( API::class, [
			'get_new_orders' => new API\Orders\Response( json_encode( [ 'data' => [ $response_data ] ] ) ),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		// test will fail if create_local_order() is not called once
		$orders_handler = $this->make( Orders::class, [
			'create_local_order' => \Codeception\Stub\Expected::once(),
		] );

		$orders_handler->update_local_orders();
	}


	/** @see Orders::update_local_orders() */
	public function test_update_local_orders_update() {

		// ensure Commerce is connected
		facebook_for_woocommerce()->get_connection_handler()->update_page_access_token( '1234' );
		facebook_for_woocommerce()->get_connection_handler()->update_commerce_manager_id( '1234' );

		$product = $this->tester->get_product();

		$order = new \WC_Order();
		$order->save();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, $product, (string) $order->get_id() );

		$remote_id = $response_data['id'];
		$order->add_meta_data( Orders::REMOTE_ID_META_KEY, $remote_id );
		$order->save_meta_data();

		// mock the API to return a test response
		$api = $this->make( API::class, [
			'get_new_orders' => new API\Orders\Response( json_encode( [ 'data' => [ $response_data ] ] ) ),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		// test will fail if create_local_order() is called or if update_local_order() is not called once
		$orders_handler = $this->make( Orders::class, [
			'create_local_order' => \Codeception\Stub\Expected::never(),
			'update_local_order' => \Codeception\Stub\Expected::once(),
		] );

		$orders_handler->update_local_orders();
	}


	/** @see Orders::update_local_orders() */
	public function test_update_local_orders_acknowledge() {

		// ensure Commerce is connected
		facebook_for_woocommerce()->get_connection_handler()->update_page_access_token( '1234' );
		facebook_for_woocommerce()->get_connection_handler()->update_commerce_manager_id( '1234' );

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, $product );

		// mock the API to return a test response and so that the test fails if acknowledge_order() is not called once
		$api = $this->make( API::class, [
			'get_new_orders'    => new API\Orders\Response( json_encode( [ 'data' => [ $response_data ] ] ) ),
			'acknowledge_order' => \Codeception\Stub\Expected::once(),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->update_local_orders();
	}


	/** @see Orders::update_cancelled_orders() */
	public function test_update_cancelled_orders() {

		// ensure Commerce is connected
		facebook_for_woocommerce()->get_connection_handler()->update_page_access_token( '1234' );
		facebook_for_woocommerce()->get_connection_handler()->update_commerce_manager_id( '1234' );

		$product = $this->tester->get_product( [
			'manage_stock'   => true,
			'stock_quantity' => 10,
			'price'          => 1.00,
		] );

		$order_item = new \WC_Order_Item_Product();
		$order_item->set_product( $product );
		$order_item->set_quantity( 1 );

		$order = new \WC_Order();
		$order->set_created_via( 'facebook' );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, 'FAKE_REMOTE_ID_1' );
		$order->add_item( $order_item );
		$order->save();

		wc_reduce_stock_levels( $order );

		$updated_product = wc_get_product( $product->get_id() );

		// ensure the stock got reduced so we don't get a false positive
		$this->assertEquals( 9, $updated_product->get_stock_quantity() );

		$response_data = $this->get_test_response_data( Order::STATUS_COMPLETED, $product );

		// mock the API to return a test response and so that the test fails if acknowledge_order() is not called once
		$api = $this->make( API::class, [
			'get_cancelled_orders'    => new API\Orders\Response( json_encode( [ 'data' => [ $response_data ] ] ) ),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->update_cancelled_orders();

		$updated_order = wc_get_order( $order->get_id() );

		$this->assertEquals( 'cancelled', $updated_order->get_status() );

		$updated_product = wc_get_product( $product->get_id() );

		$this->assertEquals( 10, $updated_product->get_stock_quantity() );
	}


	/** @see Orders::get_order_update_interval() */
	public function test_get_order_update_interval() {

		$this->assertSame( 300, $this->get_commerce_orders_handler()->get_order_update_interval() );
	}


	/**
	 * @see Orders::get_order_update_interval()
	 *
	 * @param int $filter_value filtered interval value
	 * @param int $expected expected return value
	 *
	 * @dataProvider provider_get_order_update_interval_filtered
	 */
	public function test_get_order_update_interval_filtered( $filter_value, $expected ) {

		add_filter( 'wc_facebook_commerce_order_update_interval', function() use ( $filter_value )  {
			return $filter_value;
		} );

		$this->assertSame( $expected, $this->get_commerce_orders_handler()->get_order_update_interval() );
	}


	/** @see test_get_order_update_interval_filtered */
	public function provider_get_order_update_interval_filtered() {

		return [
			'filter value longer'    => [ 600, 600 ],
			'filter value too short' => [ 5, 120 ],
			'filter value invalid'   => [ '1 billion seconds', 300 ],
		];
	}


	/** @see Orders::schedule_local_orders_update() */
	public function test_schedule_local_orders_update() {

		// ensure Commerce is connected
		facebook_for_woocommerce()->get_connection_handler()->update_page_access_token( '1234' );
		facebook_for_woocommerce()->get_connection_handler()->update_commerce_manager_id( '1234' );

		facebook_for_woocommerce()->get_commerce_handler()->get_orders_handler()->schedule_local_orders_update();

		$this->assertNotFalse( as_next_scheduled_action( Orders::ACTION_FETCH_ORDERS, [], \WC_Facebookcommerce::PLUGIN_ID ) );
	}


	/** @see Orders::fulfill_order() */
	public function test_fulfill_order_no_remote_id() {

		$order = new \WC_Order();
		$order->save();

		$this->expectException( SV_WC_Plugin_Exception::class );
		$this->expectExceptionMessage( 'Remote ID not found.' );

		$this->get_commerce_orders_handler()->fulfill_order( $order, '1234', 'FEDEX' );
	}


	/** @see Orders::fulfill_order() */
	public function test_fulfill_order_invalid_carrier() {

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Test' );
		$item->set_quantity( 2 );
		$item->set_total( 1.00 );

		$order = new \WC_Order();
		$order->add_item( $item );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		$this->expectException( SV_WC_Plugin_Exception::class );
		$this->expectExceptionMessage( 'NOT_A_CARRIER is not a valid shipping carrier code.' );

		$this->get_commerce_orders_handler()->fulfill_order( $order, '1234', 'NOT_A_CARRIER' );
	}


	/** @see Orders::fulfill_order() */
	public function test_fulfill_order_no_valid_items() {

		$order = new \WC_Order();

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Test' );
		$item->set_quantity( 2 );
		$item->set_total( 1.00 );

		$order->add_item( $item );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		$this->expectException( SV_WC_Plugin_Exception::class );
		$this->expectExceptionMessage( 'No valid Facebook products were found.' );

		$this->get_commerce_orders_handler()->fulfill_order( $order, '1234', 'FEDEX' );
	}


	/** @see Orders::fulfill_order() */
	public function test_fulfill_order() {

		$product = $this->tester->get_product();

		$order = new \WC_Order();

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Test' );
		$item->set_quantity( 2 );
		$item->set_total( 1.00 );
		$item->set_product( $product );

		$order->add_item( $item );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		// mock the API to return a test response
		$api = $this->make( API::class, [
			'fulfill_order' => new API\Orders\Response( json_encode( [ 'success' => true ] ) ),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->fulfill_order( $order, '1234', 'FEDEX' );
	}


	/** @see Orders::add_order_refund() */
	public function test_add_order_refund_no_remote_id() {

		$order = new \WC_Order();
		$order->save();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );
		$refund->save();

		// test will fail if add_order_refund() is called
		$api = $this->make( API::class, [
			'add_order_refund' => \Codeception\Stub\Expected::never(),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->expectException( SV_WC_Plugin_Exception::class );

		$this->get_commerce_orders_handler()->add_order_refund( $refund, 'REFUND_REASON_OTHER' );

		$order = wc_get_order( $order->get_id() );

		$this->assertTrue( $this->order_has_note( $order, 'Could not refund Instagram order: Remote ID for parent order not found.' ) );
	}


	/** @see Orders::add_order_refund() */
	public function test_add_order_refund_full_refund() {

		$order = new \WC_Order();

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Test' );
		$item->set_quantity( 2 );
		$item->set_total( 1.00 );

		$order->add_item( $item );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );

		$refunded_item = new \WC_Order_Item_Product();
		$refunded_item->set_name( 'Test' );
		$refunded_item->set_quantity( 2 );
		$refunded_item->set_total( 1.00 );

		$refund->add_item( $refunded_item );
		// full refund
		$refund->set_amount( '1.00' );
		$refund->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$refund->save();

		// mock the API to ensure the items are not sent to the API
		$api = $this->make( API::class, [
			'add_order_refund' => function( $remote_id, $refund_data ) { $this->assertArrayNotHasKey( 'items', $refund_data ); },
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->add_order_refund( $refund, 'REFUND_REASON_OTHER' );
	}


	/** @see Orders::get_refund_items() */
	public function test_get_refund_items() {

		$product = $this->tester->get_product();

		$refunded_item = new \WC_Order_Item_Product();
		$refunded_item->set_name( 'Test' );
		$refunded_item->set_quantity( 1 );
		$refunded_item->set_total( 0.50 );
		$refunded_item->set_product( $product );

		$refund  = new \WC_Order_Refund();
		$refund->add_item( $refunded_item );

		$method = \IntegrationTester::getMethod( Commerce\Orders::class, 'get_refund_items' );

		$this->assertIsArray( $method->invoke( new Commerce\Orders(), $refund ) );
	}


	/** @see Orders::get_refund_items() */
	public function test_get_refund_items_exception() {

		$refund = new \WC_Order_Refund();

		$method = \IntegrationTester::getMethod( Commerce\Orders::class, 'get_refund_items' );

		$this->expectException( SV_WC_Plugin_Exception::class );

		$method->invoke( new Commerce\Orders(), $refund );
	}


	/** @see Orders::add_order_refund() */
	public function test_add_order_refund_partial_refund() {

		$product         = $this->tester->get_product();
		$another_product = $this->tester->get_product();

		$order = new \WC_Order();

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Test item 1' );
		$item->set_quantity( 2 );
		$item->set_total( 1.00 );
		$item->set_product( $product );
		$item->save();

		$another_item = new \WC_Order_Item_Product();
		$another_item->set_name( 'Test item 2' );
		$another_item->set_quantity( 2 );
		$another_item->set_total( 4.00 );
		$another_item->set_product( $another_product );
		$another_item->save();

		$order->add_item( $item );
		$order->add_item( $another_item );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->set_total( '5.00' );
		$order->save();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );

		$refunded_item = new \WC_Order_Item_Product();
		$refunded_item->set_name( 'Test' );
		$refunded_item->set_quantity( 1 );
		$refunded_item->set_total( 0.50 );
		$refunded_item->set_product( $product );

		$refunded_item_without_quantity = new \WC_Order_Item_Product();
		$refunded_item_without_quantity->set_name( 'Test without quantity' );
		$refunded_item_without_quantity->set_total( '3.05' );
		$refunded_item_without_quantity->set_product( $another_product );
		$refunded_item_without_quantity->set_quantity( 0 );

		$refund->add_item( $refunded_item );
		$refund->add_item( $refunded_item_without_quantity );
		// partial refund
		$refund->set_amount( '3.50' );
		$refund->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$refund->save();

		// mock the API to ensure the items are sent to the API
		$api = $this->make( API::class, [
			'add_order_refund' => function( $remote_id, $refund_data ) use ( $product, $another_product ) {
				$this->assertArrayHasKey( 'items', $refund_data );
				$this->assertIsArray( $refund_data['items'] );
				$this->assertNotEmpty( $refund_data['items'] );
				$this->assertCount( 2, $refund_data['items'] );
				$this->assertSame( \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ), $refund_data['items'][0]['retailer_id'] );
				$this->assertSame( 1, $refund_data['items'][0]['item_refund_quantity'] );
				$this->assertArrayNotHasKey( 'item_refund_amount', $refund_data['items'][0] );
				$this->assertSame( \WC_Facebookcommerce_Utils::get_fb_retailer_id( $another_product ), $refund_data['items'][1]['retailer_id'] );
				$this->assertArrayNotHasKey( 'item_refund_quantity', $refund_data['items'][1] );
				$this->assertSame( 3.05, $refund_data['items'][1]['item_refund_amount']['amount'] );
			},
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->add_order_refund( $refund, 'REFUND_REASON_OTHER' );
	}


	/** @see Orders::add_order_refund() */
	public function test_add_order_refund_partial_refund_no_valid_items() {

		$order = new \WC_Order();

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Test' );
		$item->set_quantity( 2 );
		$item->set_total( 1.00 );

		$order->add_item( $item );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->set_total( '1.00' );
		$order->save();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );

		$refunded_item = new \WC_Order_Item_Product();
		$refunded_item->set_name( 'Test' );
		$refunded_item->set_quantity( 1 );
		$refunded_item->set_total( 0.50 );

		$refund->add_item( $refunded_item );
		// partial refund
		$refund->set_amount( '0.50' );
		$refund->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$refund->save();

		// test will fail if add_order_refund() is called
		$api = $this->make( API::class, [
			'add_order_refund' => \Codeception\Stub\Expected::never(),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->expectException( SV_WC_Plugin_Exception::class );

		$this->get_commerce_orders_handler()->add_order_refund( $refund, 'REFUND_REASON_OTHER' );

		$order = wc_get_order( $order->get_id() );

		$this->assertTrue( $this->order_has_note( $order, 'Could not refund Instagram order: No valid Facebook products were found.' ) );
	}


	/**
	 * @see Orders::add_order_refund()
	 *
	 * @param string $reason_code reason code to use
	 * @param string $expected expected request reason code
	 * @dataProvider provider_add_order_refund_reason_code
	 */
	public function test_add_order_refund_reason_code( $reason_code, $expected ) {

		$order = new \WC_Order();
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );
		$refund->set_amount( '0.50' );
		$refund->save();

		// mock the API to ensure the correct reason is passed to the API
		$api = $this->make( API::class, [
			'add_order_refund' => function( $remote_id, $refund_data ) use ( $expected ) { $this->assertSame( $expected, $refund_data['reason_code'] ); },
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->add_order_refund( $refund, $reason_code );
	}


	/** @see test_add_order_refund_reason_code */
	public function provider_add_order_refund_reason_code() {

		return [
			'valid reason code'   => [ 'BUYERS_REMORSE', 'BUYERS_REMORSE', ],
			'unknown reason code' => [ 'I_MADE_A_HUGE_MISTAKE', 'REFUND_REASON_OTHER' ],
		];
	}


	/**
	 * @see Orders::add_order_refund()
	 *
	 * @param string $reason_text reason text to use
	 * @param string $expected expected request reason text
	 * @dataProvider provider_add_order_refund_reason_text
	 */
	public function test_add_order_refund_reason_text( $reason_text, $expected ) {

		$order = new \WC_Order();
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );
		$refund->set_amount( '0.50' );
		if ( ! empty( $reason_text ) ) {
			$refund->set_reason( $reason_text );
		}
		$refund->save();

		// mock the API to ensure the correct reason is passed to the API
		$api = $this->make( API::class, [
			'add_order_refund' => function( $remote_id, $refund_data ) use ( $expected ) {
				if ( empty( $expected ) ) {
					$this->assertArrayNotHasKey( 'reason_text', $refund_data );
				} else {
					$this->assertSame( $expected, $refund_data['reason_text'] );
				}
			},
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->add_order_refund( $refund, 'REFUND_REASON_OTHER' );
	}


	/** @see test_add_order_refund_valid_reasons */
	public function provider_add_order_refund_reason_text() {

		return [
			'non empty reason text'   => [ 'Did not fit as expected', 'Did not fit as expected', ],
			'empty reason text' => [ '', '' ],
		];
	}


	/** @see Orders::add_order_refund() */
	public function test_add_order_refund_shipping() {

		$product = $this->tester->get_product();

		$order = new \WC_Order();

		$item = new \WC_Order_Item_Product();
		$item->set_name( 'Test' );
		$item->set_quantity( 2 );
		$item->set_total( 10.00 );
		$item->set_product( $product );
		$item->save();

		$shipping_item = new \WC_Order_Item_Shipping();
		$shipping_item->set_name( 'Standard' );
		$shipping_item->set_total( 5.00 );
		$shipping_item->save();

		$order->add_item( $item );
		$order->add_item( $shipping_item );
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		$refund = new \WC_Order_Refund();
		$refund->set_parent_id( $order->get_id() );

		$refunded_item = new \WC_Order_Item_Product();
		$refunded_item->set_name( 'Test' );
		$refunded_item->set_quantity( 1 );
		$refunded_item->set_total( 5.00 );
		$refunded_item->set_product( $product );

		$refunded_shipping_item = new \WC_Order_Item_Shipping();
		$refunded_shipping_item->set_name( 'Standard' );
		$refunded_shipping_item->set_total( 4.00 );
		$refunded_shipping_item->save();

		$refund->add_item( $refunded_item );
		$refund->add_item( $refunded_shipping_item );
		$refund->set_shipping_total( 4.05 );
		$refund->set_amount( '9.00' );
		$refund->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$refund->save();

		// mock the API to ensure the correct reason is passed to the API
		$api = $this->make( API::class, [
			'add_order_refund' => function( $remote_id, $refund_data ) { $this->assertSame( 4.05, $refund_data['shipping']['shipping_refund']['amount'] ); },
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->add_order_refund( $refund, 'REFUND_REASON_OTHER' );
	}


	/** @see Orders::cancel_order() */
	public function test_cancel_order_no_remote_id() {

		$order = new \WC_Order();
		$order->save();

		$this->expectException( SV_WC_Plugin_Exception::class );
		$this->expectExceptionMessage( 'Remote ID not found.' );

		$this->get_commerce_orders_handler()->cancel_order( $order, 'asdf' );
	}


	/** @see Orders::cancel_order() */
	public function test_cancel_order() {

		$order = new \WC_Order();
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		// mock the API to return a test response
		$api = $this->make( API::class, [
			'cancel_order' => new API\Orders\Response( json_encode( [ 'success' => true ] ) ),
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->cancel_order( $order, 'OTHER' );
	}


	/**
	 * @see Orders::cancel_order()
	 *
	 * @param string $reason_code reason code to use
	 * @param string $expected expected request reason code
	 * @dataProvider provider_cancel_order_valid_reasons
	 */
	public function test_cancel_order_valid_reasons( $reason_code, $expected ) {

		$order = new \WC_Order();
		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$order->save();

		// mock the API to ensure the correct reason is passed to the API
		$api = $this->make( API::class, [
			'cancel_order' => function( $remote_id, $reason ) use ( $expected ) { $this->assertSame( $expected, $reason ); },
		] );

		// replace the API property
		$property = new ReflectionProperty( \WC_Facebookcommerce::class, 'api' );
		$property->setAccessible( true );
		$property->setValue( facebook_for_woocommerce(), $api );

		$this->get_commerce_orders_handler()->cancel_order( $order, $reason_code );
	}


	/** @see test_cancel_order_valid_reasons */
	public function provider_cancel_order_valid_reasons() {

		return [
			'valid reason code'   => [ 'CUSTOMER_REQUESTED', 'CUSTOMER_REQUESTED', ],
			'unknown reason code' => [ 'I_MADE_A_HUGE_MISTAKE', 'CANCEL_REASON_OTHER' ],
		];
	}


	/**
	 * @see Orders::maybe_stop_order_email()
	 *
	 * @param bool $is_enabled
	 * @param \WC_Order|string|null $order
	 * @param bool $expected
	 *
	 * @dataProvider provider_maybe_stop_order_email
	 */
	public function test_maybe_stop_order_email( $is_enabled, $order, $expected ) {

		$orders_handler = $this->get_commerce_orders_handler();

		$this->assertEquals( $expected, $orders_handler->maybe_stop_order_email( $is_enabled, $order ) );
	}


	/**
	 * @see test_maybe_stop_order_email
	 *
	 * @throws WC_Data_Exception
	 */
	public function provider_maybe_stop_order_email() {

		$commerce_order = new \WC_Order();
		$commerce_order->set_created_via( 'instagram' );

		return [
			[ false, null,                     false ],
			[ true,  null,                     true ],
			[ true,  'a non \WC_Order object', true ],
			[ true,  new \WC_Order(),          true ],
			[ true,  $commerce_order,          false ],
		];
	}


	/**
	 * @see Orders::maybe_stop_order_email()
	 *
	 * @param bool $is_enabled
	 * @param bool $expected
	 * @param \WC_Order|string|null $order
	 *
	 * @dataProvider provider_maybe_stop_order_email_filter
	 */
	public function test_maybe_stop_order_email_filter( $is_enabled, $order, $expected ) {

		add_filter( 'wc_facebook_commerce_send_woocommerce_emails', function( $is_enabled ) {

			return ! $is_enabled;
		} );

		$orders_handler = $this->get_commerce_orders_handler();

		$this->assertEquals( $expected, $orders_handler->maybe_stop_order_email( $is_enabled, $order ) );
	}


	/** @see test_maybe_stop_order_email_filter */
	public function provider_maybe_stop_order_email_filter() {

		$commerce_order = new \WC_Order();
		$commerce_order->set_created_via( 'instagram' );

		return [
			[ false, null,                     false ],
			[ true,  null,                     true ],
			[ true,  'a non \WC_Order object', true ],
			[ false, 'a non \WC_Order object', false ],
			[ true,  new \WC_Order(),          true ],
			[ false, new \WC_Order(),          false ],
			[ true,  $commerce_order,          true ],
			[ false, $commerce_order,          false ],
		];
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the Commerce orders handler instance.
	 *
	 * @return Orders
	 */
	private function get_commerce_orders_handler() {

		return facebook_for_woocommerce()->get_commerce_handler()->get_orders_handler();
	}


	/**
	 * Checks if the order has a note with the given content.
	 *
	 * @param \WC_Order $order order object
	 * @param string $note_content note content
	 * @return bool
	 */
	private function order_has_note( $order, $note_content ) {

		$note_found = false;
		$notes      = wc_get_order_notes( [ 'order_id' => $order->get_id() ] );

		foreach ( $notes as $note ) {

			if ( $note_content === $note->content ) {

				$note_found = true;
				break;
			}
		}

		return $note_found;
	}


	/**
	 * Gets the response test data.
	 *
	 * @see https://developers.facebook.com/docs/commerce-platform/order-management/order-api#get_orders
	 *
	 * @param string $order_status order status
	 * @param \WC_Product|null $product WC product object
	 * @param string $merchant_order_id WC order ID
	 * @return array
	 */
	private function get_test_response_data( $order_status = Order::STATUS_CREATED, $product = null, $merchant_order_id = '' ) {

		return [
			'id'                        => 'FAKE_REMOTE_ID_1',
			'order_status'              => [
				'state' => $order_status,
			],
			'created'                   => '2019-01-14T19:17:31+00:00',
			'last_updated'              => '2019-01-14T19:47:35+00:00',
			'items'                     => [
				'data' => [
					0 => [
						'id'             => '2082596341811586',
						'product_id'     => '1213131231',
						'retailer_id'    => ! empty( $product ) ? \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ) : 'external_product_1234',
						'quantity'       => 2,
						'price_per_unit' => [
							'amount'   => '20.00',
							'currency' => 'USD',
						],
						'tax_details'    => [
							'estimated_tax' => [
								'amount'   => '0.36',
								'currency' => 'USD',
							],
							'captured_tax'  => [
								'total_tax' => [
									'amount'   => '0.30',
									'currency' => 'USD',
								],
							],
						],
					],
				],
			],
			'ship_by_date'              => '2019-01-16',
			'merchant_order_id'         => ! empty( $merchant_order_id ) ? $merchant_order_id : '46192',
			'channel'                   => 'Instagram',
			'selected_shipping_option'  => [
				'name'                    => 'Standard',
				'price'                   => [
					'amount'   => '10.00',
					'currency' => 'USD',
				],
				'calculated_tax'          => [
					'amount'   => '0.15',
					'currency' => 'USD',
				],
				'estimated_shipping_time' => [
					'min_days' => 3,
					'max_days' => 15,
				],
			],
			'shipping_address'          => [
				'name'        => 'John Doe',
				'street1'     => '123 Main St',
				'street2'     => 'Unit 200',
				'city'        => 'Boston',
				'state'       => 'MA',
				'postal_code' => '02110',
				'country'     => 'US',
			],
			'estimated_payment_details' => [
				'subtotal'     => [
					'items'    => [
						'amount'   => '20.00',
						'currency' => 'USD',
					],
					'shipping' => [
						'amount'   => '10.00',
						'currency' => 'USD',
					],
				],
				'tax'          => [
					'amount'   => '0.45',
					'currency' => 'USD',
				],
				'total_amount' => [
					'amount'   => '20.45',
					'currency' => 'USD',
				],
				'tax_remitted' => true,
			],
			'buyer_details'             => [
				'name'                     => 'John Doe',
				'email'                    => 'johndoe@example.com',
				'email_remarketing_option' => false,
			],
		];
	}


}
