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

	// TODO: add test for maybe_stop_order_email()

	// TODO: add test for is_order_editable()

	// TODO: add test for is_orders_screen()

	// TODO: add test for is_edit_order_screen()


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
