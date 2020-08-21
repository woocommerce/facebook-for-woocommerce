<?php

use SkyVerge\WooCommerce\Facebook\Product_Categories;
use SkyVerge\WooCommerce\Facebook\Products;

/**
 * Tests the Product_Categories class.
 */
class Product_Categories_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var int category ID */
	protected $category_id;


	public function _before() {

		parent::_before();

		if ( ! class_exists( Product_Categories::class ) ) {
			require_once 'includes/Product_Categories.php';
		}

		$category          = wp_insert_term( 'New category', 'product_cat' );
		$this->category_id = $category['term_id'];
	}


	/** Test methods **************************************************************************************************/


	/** @see Product_Categories::get_google_product_category_id() */
	public function test_get_google_product_category_id() {

		add_term_meta( $this->category_id, Products::GOOGLE_PRODUCT_CATEGORY_META_KEY, '3367', true );

		$this->assertEquals( '3367', Product_Categories::get_google_product_category_id( $this->category_id ) );
	}


}
