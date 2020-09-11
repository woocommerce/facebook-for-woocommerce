<?php

use SkyVerge\WooCommerce\Facebook\Admin;

/**
 * Tests the Admin\Products class.
 */
class ProductsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Admin/Products.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for render_google_product_category_fields()

	// TODO: add test for render_attribute_fields()

	// TODO: add test for render_commerce_fields()

	// TODO: add test for save_commerce_fields()


	/** Utility methods ***********************************************************************************************/


	/**
	 * Gets a products handler instance.
	 *
	 * @since 2.1.0-dev.1
	 *
	 * @return Admin\Products
	 */
	private function get_products_handler() {

		return new Admin\Products();
	}


}
