<?php

use SkyVerge\WooCommerce\Facebook\Admin;

/**
 * Tests the Admin\Orders class.
 */
class OrdersTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Admin/Orders.php';
		require_once 'includes/Commerce/Orders.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for enqueue_assets()

	// TODO: add test for add_notices()

	// TODO: add test for maybe_remove_order_metaboxes()

	// TODO: add test for render_refund_reason_field()

	// TODO: add test for handle_refund()

	// TODO: add test for handle_bulk_update()


	/**
	 * @see Admin\Orders::maybe_stop_order_email()
	 *
	 * @param bool $is_enabled
	 * @param \WC_Order|string|null $order
	 * @param bool $expected
	 *
	 * @dataProvider provider_maybe_stop_order_email
	 */
	public function test_maybe_stop_order_email( $is_enabled, $order, $expected ) {

		$orders_handler = $this->get_orders_handler();

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
	 * @see Admin\Orders::maybe_stop_order_email()
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

		$orders_handler = $this->get_orders_handler();

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


	// TODO: add test for is_order_editable()


	/** Utility methods ***********************************************************************************************/


	/**
	 * Gets an orders handler instance.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return Admin\Orders
	 */
	private function get_orders_handler() {

		return new Admin\Orders();
	}


}
