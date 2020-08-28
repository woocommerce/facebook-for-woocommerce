<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\Commerce;

use SkyVerge\WooCommerce\Facebook\API\Orders\Order;
use SkyVerge\WooCommerce\Facebook\Commerce;
use SkyVerge\WooCommerce\Facebook\Commerce\Orders;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception;

/**
 * Tests the general Commerce orders handler class.
 */
class OrdersTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		if ( ! class_exists( SkyVerge\WooCommerce\Facebook\API\Orders\Order::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Orders/Order.php';
		}
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


	/** @see Orders::update_local_order() */
	public function test_update_local_order_created() {

		$order = new \WC_Order();
		$order->save();

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, (string) $product->get_id() );
		$remote_order  = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		// get a fresh order
		$updated_local_order = wc_get_order( $updated_local_order->get_id() );

		$order_items = $updated_local_order->get_items();
		$this->assertCount( 1, $order_items );

		/** @var \WC_Order_Item_Product $first_order_item */
		$first_order_item = current( $order_items );
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $first_order_item );
		$this->assertEquals( $response_data['items'][0]['retailer_id'], $first_order_item->get_product_id() );
		$this->assertEquals( $response_data['items'][0]['quantity'], $first_order_item->get_quantity() );
		$this->assertEquals( $response_data['items'][0]['quantity'] * $response_data['items'][0]['price_per_unit']['amount'], $first_order_item->get_subtotal() );
		$this->assertEquals( $response_data['items'][0]['tax_details']['estimated_tax']['amount'], $first_order_item->get_subtotal_tax() );

		$this->assertEquals( $response_data['selected_shipping_option']['price']['amount'], $updated_local_order->get_shipping_total() );
		$this->assertEquals( $response_data['selected_shipping_option']['calculated_tax']['amount'], $updated_local_order->get_shipping_tax() );

		$this->assertEquals( $response_data['shipping_address']['name'], $updated_local_order->get_shipping_last_name() );
		$this->assertEquals( $response_data['shipping_address']['street1'], $updated_local_order->get_shipping_address_1() );
		$this->assertEquals( $response_data['shipping_address']['street2'], $updated_local_order->get_shipping_address_2() );
		$this->assertEquals( $response_data['shipping_address']['city'], $updated_local_order->get_shipping_city() );
		$this->assertEquals( $response_data['shipping_address']['state'], $updated_local_order->get_shipping_state() );
		$this->assertEquals( $response_data['shipping_address']['postal_code'], $updated_local_order->get_shipping_postcode() );
		$this->assertEquals( $response_data['shipping_address']['country'], $updated_local_order->get_shipping_country() );

		$this->assertEquals( $response_data['estimated_payment_details']['total_amount']['amount'], $updated_local_order->get_total() );

		$this->assertEquals( $response_data['buyer_details']['name'], $updated_local_order->get_billing_last_name() );
		$this->assertEquals( $response_data['buyer_details']['email'], $updated_local_order->get_billing_email() );
		$this->assertEquals( $response_data['buyer_details']['email_remarketing_option'], wc_string_to_bool( $updated_local_order->get_meta( Orders::EMAIL_REMARKETING_META_KEY ) ) );

		$this->assertEquals( $response_data['id'], $updated_local_order->get_meta( Orders::REMOTE_ID_META_KEY ) );

		$this->assertEquals( 'processing', $updated_local_order->get_status() );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_fb_processing() {

		$order = new \WC_Order();
		$order->save();

		$product = $this->tester->get_product();

		$response_data = $this->get_test_response_data( Order::STATUS_PROCESSING, (string) $product->get_id() );
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

		$response_data = $this->get_test_response_data( Order::STATUS_CREATED, (string) $product->get_id() );
		$remote_order  = new Order( $response_data );

		$updated_local_order = $this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );

		$order_items = $updated_local_order->get_items();
		$this->assertCount( 1, $order_items );

		/** @var \WC_Order_Item_Product $first_order_item */
		$first_order_item = current( $order_items );
		$this->assertInstanceOf( \WC_Order_Item_Product::class, $first_order_item );
		$this->assertEquals( $response_data['items'][0]['retailer_id'], $first_order_item->get_product_id() );
		$this->assertEquals( $response_data['items'][0]['quantity'], $first_order_item->get_quantity() );
		$this->assertEquals( $response_data['items'][0]['quantity'] * $response_data['items'][0]['price_per_unit']['amount'], $first_order_item->get_subtotal() );
		$this->assertEquals( $response_data['items'][0]['tax_details']['estimated_tax']['amount'], $first_order_item->get_subtotal_tax() );
	}


	/** @see Orders::update_local_order() */
	public function test_update_local_order_product_not_found() {

		$order = new \WC_Order();
		$order->save();

		$response_data = $this->get_test_response_data( Order::STATUS_PROCESSING );
		$remote_order  = new Order( $response_data );

		$this->expectException( SV_WC_Plugin_Exception::class );
		$this->expectExceptionMessage( 'Product with WC ID external_product_1234 not found' );

		$this->get_commerce_orders_handler()->update_local_order( $remote_order, $order );
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the Commerce orders handler instance.
	 *
	 * @return Orders
	 */
	private function get_commerce_orders_handler() {

		$commerce_handler = new Commerce();

		return $commerce_handler->get_orders_handler();
	}


	/**
	 * Gets the response test data.
	 *
	 * @see https://developers.facebook.com/docs/commerce-platform/order-management/order-api#get_orders
	 *
	 * @param string $order_status order status
	 * @param string $product_retailer_id WC product ID
	 * @return array
	 */
	private function get_test_response_data( $order_status = Order::STATUS_CREATED, $product_retailer_id = '' ) {

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
			'merchant_order_id'         => '46192',
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
