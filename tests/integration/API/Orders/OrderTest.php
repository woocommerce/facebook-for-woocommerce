<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders;

use SkyVerge\WooCommerce\Facebook\API\Orders\Order;

/**
 * Tests the API\Orders\Order class.
 */
class OrderTest extends \Codeception\TestCase\WPTestCase {


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


	/** @see Order::get_id() */
	public function test_get_id() {

		$test_response_data = $this->get_test_response_data();
		$order_handler      = new Order( $test_response_data );

		$this->assertEquals( $test_response_data['id'], $order_handler->get_id() );
	}


	/** @see Order::get_status() */
	public function test_get_status() {

		$test_response_data = $this->get_test_response_data();
		$order_handler      = new Order( $test_response_data );

		$this->assertEquals( $test_response_data['order_status']['state'], $order_handler->get_status() );
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the response test data.
	 *
	 * @see https://developers.facebook.com/docs/commerce-platform/order-management/order-api#get_orders
	 *
	 * @param string $order_status order status
	 * @return array
	 */
	private function get_test_response_data( $order_status = Order::STATUS_CREATED ) {

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
					'retailer_id'    => 'external_product_1234',
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
			'channel'                   => 'instagram',
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
