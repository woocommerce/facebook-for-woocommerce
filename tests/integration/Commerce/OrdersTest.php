<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\Commerce;

use ReflectionProperty;
use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Orders\Order;
use SkyVerge\WooCommerce\Facebook\Commerce;
use SkyVerge\WooCommerce\Facebook\Commerce\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_API_Exception;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception;

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

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/** @see Orders::find_local_order() */
	public function test_find_local_order_found() {

		$order = new \WC_Order();
		$order->save();

		$remote_id = '335211597203390';

		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, $remote_id );
		$order->save_meta_data();

		$this->assertInstanceOf( \WC_Order::class, $this->get_commerce_orders_handler()->find_local_order( $remote_id ) );
		$this->assertEquals( $order->get_id(), $this->get_commerce_orders_handler()->find_local_order( $remote_id )->get_id() );
	}


	/** @see Orders::find_local_order() */
	public function test_find_local_order_not_found() {

		$order = new \WC_Order();
		$order->save();

		$remote_id           = '435211597203390';
		$different_remote_id = '335211597203390';

		$order->update_meta_data( Orders::REMOTE_ID_META_KEY, $different_remote_id );
		$order->save_meta_data();

		$this->assertNull( $this->get_commerce_orders_handler()->find_local_order( $remote_id ) );
	}


	/** @see Orders::create_local_order() */
	public function test_create_local_order() {

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_PROCESSING, (string) $product->get_id() );
		$remote_order  = new Order( $response_data );

		$local_order = $this->get_commerce_orders_handler()->create_local_order( $remote_order );
		$this->assertInstanceOf( \WC_Order::class, $local_order );
		$this->assertEquals( $response_data['channel'], $local_order->get_created_via() );
		$this->assertEquals( 'pending', $local_order->get_status() );
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

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, (string) $product->get_id() );

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

		$product = $this->tester->get_product();

		$order = new \WC_Order();
		$order->save();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, (string) $product->get_id(), (string) $order->get_id() );

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

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, (string) $product->get_id() );

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

		$this->get_commerce_orders_handler()->add_order_refund( $refund, 'REFUND_REASON_OTHER' );

		$order = wc_get_order( $order->get_id() );

		$this->assertTrue( $this->order_has_note( $order, 'Could not refund Instagram order: Remote ID for parent order not found.' ) );
	}


	/** @see Orders::add_order_refund() */
	public function test_add_order_refund_no_valid_items() {

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
		$refunded_item->set_quantity( 1 );
		$refunded_item->set_total( 0.50 );

		$refund->add_item( $refunded_item );
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
		if ( ! empty( $reason_text ) ) {
			$refund->set_reason( $reason_text );
		}
		$refund->save();

		// mock the API to ensure the correct reason is passed to the API
		$api = $this->make( API::class, [
			'add_order_refund' => function( $remote_id, $refund_data ) use ( $expected ) { $this->assertSame( $expected, $refund_data['reason_text'] ); },
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
		$refund->set_shipping_total( 4.00 );
		$refund->update_meta_data( Orders::REMOTE_ID_META_KEY, '1234' );
		$refund->save();

		$expected_refunded_shipping_total = $refund->get_shipping_total();

		// mock the API to ensure the correct reason is passed to the API
		$api = $this->make( API::class, [
			'add_order_refund' => function( $remote_id, $refund_data ) use ( $expected_refunded_shipping_total ) { $this->assertSame( $expected_refunded_shipping_total, $refund_data['shipping']['amount'] ); },
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
	 * @param string $product_retailer_id WC product ID
	 * @param string $merchant_order_id WC order ID
	 * @return array
	 */
	private function get_test_response_data( $order_status = Order::STATUS_CREATED, $product_retailer_id = '', $merchant_order_id = '' ) {

		return [
			'id'                        => '335211597203390',
			'order_status'              => [
				'state' => $order_status,
			],
			'created'                   => '2019-01-14T19:17:31+00:00',
			'last_updated'              => '2019-01-14T19:47:35+00:00',
			'items'                     => [
				0 => [
					'id'             => '2082596341811586',
					'product_id'     => '1213131231',
					'retailer_id'    => ! empty( $product_retailer_id ) ? $product_retailer_id : 'external_product_1234',
					'quantity'       => 2,
					'price_per_unit' => [
						'amount'   => '20.00',
						'currency' => 'USD',
					],
					'tax_details'    => [
						'estimated_tax' => [
							'amount'   => '0.30',
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
				'name'        => 'ABC company',
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
