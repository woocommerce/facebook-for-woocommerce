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

	// TODO: add test for render_modal_templates()

	// TODO: add test for render_refund_reason_field()

	// TODO: add test for handle_refund()

	// TODO: add test for handle_bulk_update()


	/**
	 * @see Admin\Orders::maybe_stop_order_email()
	 *
	 * @throws WC_Data_Exception
	 */
	public function test_maybe_stop_order_email() {

		$orders_handler = $this->get_orders_handler();

		$this->assertFalse( $orders_handler->maybe_stop_order_email( false, null ) );
		$this->assertTrue( $orders_handler->maybe_stop_order_email( true, null ) );
		$this->assertTrue( $orders_handler->maybe_stop_order_email( true, 'a non \WC_Order object' ) );

		$order = new \WC_Order();

		$this->assertTrue( $orders_handler->maybe_stop_order_email( true, $order ) );

		$order->set_created_via( 'instagram' );

		$this->assertFalse( $orders_handler->maybe_stop_order_email( true, $order ) );
	}


	/** @see Admin\Orders::maybe_stop_order_email() */
	public function test_maybe_stop_order_email_filter() {

		add_filter( 'wc_facebook_commerce_send_woocommerce_emails', function( $is_enabled ) {

			return ! $is_enabled;
		} );

		$orders_handler = $this->get_orders_handler();

		$this->assertTrue( $orders_handler->maybe_stop_order_email( false, null ) );
		$this->assertFalse( $orders_handler->maybe_stop_order_email( true, null ) );
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
