<?php

use SkyVerge\WooCommerce\Facebook\Admin;
use SkyVerge\WooCommerce\Facebook\Admin\Products;

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

		require_once WC()->plugin_path() . '/includes/admin/wc-meta-box-functions.php';

		require_once 'includes/Admin/Products.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for render_google_product_category_fields()


	/**
	 * @see Products::render_attribute_fields()
	 *
	 * TODO: add an acceptance test that checks the behavior of the attribute fields {WV 2020-09-18}
	 *
	 * @param string $constant_name the name of the input field
	 * @dataProvider provider_render_attribute_fields
	 */
	public function test_render_attribute_fields( $constant_name ) {
		global $post;

		$product = $this->tester->get_product();
		$post    = get_post( $product->get_id() );

		ob_start();

		Products::render_attribute_fields( $product );

		$output = ob_get_clean();

		$this->tester->assertStringContainsString( constant( Products::class . '::' . $constant_name ), $output );
	}


	/**
	 * This method cannot use Product's constants because the class is not loaded by the time the method is executed.
	 *
	 * @see test_render_attribute_fields()
	 */
	public function provider_render_attribute_fields() {

		return [
			[ 'FIELD_GENDER' ],
			[ 'FIELD_COLOR' ],
			[ 'FIELD_SIZE' ],
			[ 'FIELD_PATTERN' ],
		];
	}


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
