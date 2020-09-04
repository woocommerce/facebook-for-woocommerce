<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Orders\Read;

use SkyVerge\WooCommerce\Facebook\API\Orders\Order;
use SkyVerge\WooCommerce\Facebook\API\Orders\Read\Response;

/**
 * Tests the API\Orders\Read\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


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


	/** @see Response::get_order() */
	public function test_get_order() {

		$response_data = [
			'id'                        => '335211597203390',
			'order_status'              => [
				'state' => 'CREATED',
			],
			'created'                   => '2019-01-14T19:17:31+00:00',
			'last_updated'              => '2019-01-14T19:47:35+00:00',
			'items'                     => [
				'data' => [
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

		$response = new Response( json_encode( $response_data ) );
		$order    = $response->get_order();

		$this->assertInstanceOf( Order::class, $order );

		foreach ( $response_data as $key => $value ) {

			if ( 'order_status' === $key ) {

				$this->assertEquals( $value['state'], $order->get_status() );

			} elseif ( 'items' === $key ) {

				$this->assertEquals( $value['data'], $order->get_items() );

			} elseif ( ! in_array( $key, [ 'created', 'last_updated', 'ship_by_date', 'merchant_order_id' ] ) ) {

				$method = "get_$key";
				$this->assertEquals( $value, $order->$method() );
			}
		}
	}


}
