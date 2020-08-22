<?php

use SkyVerge\WooCommerce\Facebook;
use SkyVerge\WooCommerce\Facebook\Product_Categories;
use SkyVerge\WooCommerce\Facebook\Products;

/**
 * Tests the Products class.
 */
class Products_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var int excluded product category ID */
	private $excluded_category;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		$this->add_excluded_category();
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Facebook\Products::product_should_be_synced() */
	public function test_product_should_be_synced_simple() {

		// used the tester's method directly to set regular_price to 0
		$product = $this->tester->get_product( [
			'status'        => 'publish',
			'regular_price' => 0,
		] );

		$this->assertTrue( Facebook\Products::product_should_be_synced( $product ) );
	}

	/** @see Facebook\Products::product_should_be_synced() */
	public function test_product_should_be_synced_variation() {

		// used the tester's method directly to set regular_price to 0
		$product = $this->tester->get_variable_product( [
			'status'        => 'publish',
			'regular_price' => 0,
		] );

		foreach ( $product->get_children() as $child_id ) {
			$this->assertTrue( Facebook\Products::product_should_be_synced( wc_get_product( $child_id ) ) );
		}
	}


	/**
	 * Tests that product excluded from the store catalog or from search results should not be synced.
	 *
	 * @see Facebook\Products::product_should_be_synced()
	 *
	 * @param string $term product_visibility term
	 *
	 * @dataProvider provider_product_should_be_synced_with_excluded_products
	 */
	public function test_product_should_be_synced_with_excluded_products( $term ) {

		$product = $this->get_product();

		wp_set_object_terms( $product->get_id(), $term, 'product_visibility' );

		$this->assertFalse( Facebook\Products::product_should_be_synced( $product ) );
	}


	/** @see test_product_should_be_synced_with_excluded_products() */
	public function provider_product_should_be_synced_with_excluded_products() {

		return [
			[ 'exclude-from-catalog' ],
			[ 'exclude-from-search' ],
		];
	}


	/** @see Facebook\Products::product_should_be_synced() */
	public function test_product_should_be_synced_simple_in_excluded_category() {

		$product = $this->get_product();
		$product->set_category_ids( [ $this->excluded_category ] );

		$this->assertFalse( Facebook\Products::product_should_be_synced( $product ) );
	}


	/** @see Facebook\Products::product_should_be_synced() */
	public function test_product_should_be_synced_variation_in_excluded_category() {

		$product = $this->get_variable_product();
		$product->set_category_ids( [ $this->excluded_category ] );
		$product->save();

		foreach ( $product->get_children() as $child_id ) {
			$this->assertFalse( Facebook\Products::product_should_be_synced( wc_get_product( $child_id ) ) );
		}
	}


	/** @see Facebook\Products::enable_sync_for_products() */
	public function test_enable_sync_for_products() {

		$product = $this->get_product();

		Facebook\Products::disable_sync_for_products( [ $product ] );
		Facebook\Products::enable_sync_for_products( [ $product ] );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $product ) );
	}


	/** @see Facebook\Products::enable_sync_for_products() for variable products  */
	public function test_enable_sync_for_products_variable() {

		$variable_product = $this->get_variable_product();

		Facebook\Products::disable_sync_for_products( [ $variable_product ] );
		Facebook\Products::enable_sync_for_products( [ $variable_product ] );

		// get a fresh product object to ensure the status is stored
		$variable_product = wc_get_product( $variable_product->get_id() );

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $variable_product ) );
	}


	/** @see Facebook\Products::enable_sync_for_products() for variations  */
	public function test_enable_sync_for_products_variation() {

		$variable_product = $this->get_variable_product();

		Facebook\Products::disable_sync_for_products( [ $variable_product ] );
		Facebook\Products::enable_sync_for_products( [ $variable_product ] );

		// get a fresh product object to ensure the status is stored
		$variable_product = wc_get_product( $variable_product->get_id() );

		foreach ( $variable_product->get_children() as $child_product_id ) {
			$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( wc_get_product( $child_product_id ) ) );
		}
	}


	/** @see Facebook\Products::disable_sync_for_products() */
	public function test_disable_sync_for_products() {

		$product = $this->get_product();

		Facebook\Products::enable_sync_for_products( [ $product ] );
		Facebook\Products::disable_sync_for_products( [ $product ] );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertFalse( Facebook\Products::is_sync_enabled_for_product( $product ) );
	}


	/** @see Facebook\Products::disable_sync_for_products() for variable products */
	public function test_disable_sync_for_products_variable() {

		$variable_product = $this->get_variable_product();

		Facebook\Products::enable_sync_for_products( [ $variable_product ] );
		Facebook\Products::disable_sync_for_products( [ $variable_product ] );

		// get a fresh product object to ensure the status is stored
		$variable_product = wc_get_product( $variable_product->get_id() );

		$this->assertFalse( Facebook\Products::is_sync_enabled_for_product( $variable_product ) );
	}


	/** @see Facebook\Products::disable_sync_for_products() for variations */
	public function test_disable_sync_for_products_variation() {

		$variable_product = $this->get_variable_product();

		Facebook\Products::enable_sync_for_products( [ $variable_product ] );
		Facebook\Products::disable_sync_for_products( [ $variable_product ] );

		// get a fresh product object to ensure the status is stored
		$variable_product = wc_get_product( $variable_product->get_id() );

		foreach ( $variable_product->get_children() as $child_product_id ) {
			$this->assertFalse( Facebook\Products::is_sync_enabled_for_product( wc_get_product( $child_product_id ) ) );
		}
	}


	/**
	 * @see Facebook\Products::disable_sync_for_products_with_terms()
	 *
	 * @param int $term_id the ID of the term to look for
	 * @param string $taxonomy the name of the taxonomy to look for
	 * @param bool $set_term whether to add the term to the test product
	 * @param bool $is_synced_enabled whether sync should be enabled for the product or not
	 *
	 * @dataProvider provider_disable_sync_for_products_with_terms
	 */
	public function test_disable_sync_for_products_with_terms( $term_id, $taxonomy, $set_term, $is_sync_enabled ) {

		$product = $this->get_product();

		if ( $set_term ) {
			wp_set_object_terms( $product->get_id(), $term_id, $taxonomy );
		}

		Facebook\Products::enable_sync_for_products( [ $product ] );
		Facebook\Products::disable_sync_for_products_with_terms( [ 'taxonomy' => $taxonomy, 'include' => [ $term_id ] ] );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( $is_sync_enabled, Facebook\Products::is_sync_enabled_for_product( $product ) );
	}


	public function provider_disable_sync_for_products_with_terms() {

		$category = wp_insert_term( 'product_cat_test', 'product_cat' );
		$tag      = wp_insert_term( 'product_tag_test', 'product_tag' );

		return [
			// the product has the term
			[ $category['term_id'], 'product_cat', true, false ],
			[ $tag['term_id'],      'product_tag', true, false ],

			// the product does not have the term
			[ $category['term_id'], 'product_cat', false, true ],
			[ $tag['term_id'],      'product_tag', false, true ],
		];
	}


	/** @see Facebook\Products::is_sync_enabled_for_product() for products that don't have a preference set */
	public function test_is_sync_enabled_for_product_defaults() {

		$product = $this->get_product();

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $product ) );

		$variable_product = $this->get_variable_product();

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $this->get_variable_product() ) );

		foreach ( $variable_product->get_children() as $child_product_id ) {
			$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( wc_get_product( $child_product_id ) ) );
		}
	}


	/** @see \SkyVerge\WooCommerce\Facebook\Products::set_product_visibility() */
	public function test_set_product_visibility() {

		$product = $this->get_product();

		$visibility = $product->get_meta( Facebook\Products::VISIBILITY_META_KEY );

		$this->assertEmpty( $visibility );

		Facebook\Products::set_product_visibility( $product, true );

		$visibility = $product->get_meta( Facebook\Products::VISIBILITY_META_KEY );

		$this->assertEquals( 'yes', $visibility );

		Facebook\Products::set_product_visibility( $product, false );

		$visibility = $product->get_meta( Facebook\Products::VISIBILITY_META_KEY );

		$this->assertEquals( 'no', $visibility );
	}


	/** @see \SkyVerge\WooCommerce\Facebook\Products::is_product_visible() */
	public function test_is_product_visible() {

		$product = $this->get_product();

		Facebook\Products::set_product_visibility( $product, false );

		$this->assertFalse( Facebook\Products::is_product_visible( $product ) );

		Facebook\Products::set_product_visibility( $product, true );

		$this->assertTrue( Facebook\Products::is_product_visible( $product ) );
	}


	/** @see Facebook\Products::get_product_price() */
	public function test_get_product_price_filter() {

		$product = $this->get_product();

		add_filter( 'wc_facebook_product_price', static function() {
			return 1234;
		} );

		$this->assertSame( 1234, Facebook\Products::get_product_price( $product ) );
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_simple_product() {

		$product = $this->get_product();
		Products::update_google_product_category_id( $product, '1' );

		$this->assertEquals( '1', Products::get_google_product_category_id( $product ) );
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_product_variation() {

		$parent_product    = $this->get_variable_product();

		foreach ( $parent_product->get_children() as $child_product_id ) {

			$product_variation = wc_get_product( $child_product_id );
			Products::update_google_product_category_id( $parent_product, '2' );

			$this->assertEquals( '2', Products::get_google_product_category_id( $product_variation ) );
		}
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_product_single_category() {

		$product         = $this->get_product();
		$parent_category = wp_insert_term( 'Animals & Pet Supplies', 'product_cat' );
		Product_Categories::update_google_product_category_id( $parent_category['term_id'], '3' );
		wp_set_post_terms( $product->get_id(), [ $parent_category['term_id'] ], 'product_cat' );

		$this->assertEquals( '3', Products::get_google_product_category_id( $product ) );
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_product_multiple_categories() {

		$product         = $this->get_product();
		$parent_category = wp_insert_term( 'Animals & Pet Supplies', 'product_cat' );
		Product_Categories::update_google_product_category_id( $parent_category['term_id'], '4' );
		$child_category = wp_insert_term( 'Pet Supplies', 'product_cat', [ 'parent' => $parent_category['term_id'] ] );
		Product_Categories::update_google_product_category_id( $child_category['term_id'], '5' );
		wp_set_post_terms( $product->get_id(), [
			$parent_category['term_id'],
			$child_category['term_id'],
		], 'product_cat' );

		$this->assertEquals( '5', Products::get_google_product_category_id( $product ) );
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_product_conflicting_categories() {

		$product         = $this->get_product();
		$parent_category = wp_insert_term( 'Animals & Pet Supplies', 'product_cat' );
		Product_Categories::update_google_product_category_id( $parent_category['term_id'], '5' );
		$child_category_1 = wp_insert_term( 'Cat Supplies', 'product_cat', [ 'parent' => $parent_category['term_id'] ] );
		Product_Categories::update_google_product_category_id( $child_category_1['term_id'], '6' );
		$child_category_2 = wp_insert_term( 'Dog Supplies', 'product_cat', [ 'parent' => $parent_category['term_id'] ] );
		Product_Categories::update_google_product_category_id( $child_category_2['term_id'], '7' );
		wp_set_post_terms( $product->get_id(), [
			$parent_category['term_id'],
			$child_category_1['term_id'],
			$child_category_2['term_id'],
		], 'product_cat' );

		$this->assertEquals( '', Products::get_google_product_category_id( $product ) );
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_default() {

		$product = $this->get_product();
		facebook_for_woocommerce()->get_commerce_handler()->update_default_google_product_category_id( '7' );

		$this->assertEquals( '7', Products::get_google_product_category_id( $product ) );
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\Products::update_google_product_category_id()
	 *
	 * @param string $google_product_category_id Google product category ID
	 *
	 * @dataProvider provider_update_google_product_category_id
	 */
	public function test_update_google_product_category_id( $google_product_category_id ) {

		$product = $this->get_product();

		Products::update_google_product_category_id( $product, $google_product_category_id );

		$this->assertEquals( $google_product_category_id, $product->get_meta( Products::GOOGLE_PRODUCT_CATEGORY_META_KEY ) );
	}


	/** @see test_update_google_product_category_id */
	public function provider_update_google_product_category_id() {

		return [
			[ '3350' ],
			[ '' ],
		];
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets a new product object.
	 *
	 * @param array $args product configuration parameters
	 * @return \WC_Product
	 */
	private function get_product( $args = [] ) {

		return $this->tester->get_product( array_merge( $args,  [
			'status'        => 'publish',
			'regular_price' => 19.99,
		] ) );
	}


	/**
	 * Gets a new variable product object, with variations.
	 *
	 * @param array $args product configuration parameters
	 * @param int|int[] $children array of variation IDs, if unspecified will generate the amount passed (default 3)
	 * @return \WC_Product_Variable
	 */
	private function get_variable_product( $args = [] ) {

		return $this->tester->get_variable_product( array_merge( $args,  [
			'status'        => 'publish',
			'regular_price' => 19.99,
		] ) );
	}


	/**
	 * Adds and excluded category.
	 */
	private function add_excluded_category() {

		$category = wp_insert_term( 'Excluded category', 'product_cat' );

		$this->excluded_category = $category['term_id'];

		update_option( \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, [ $this->excluded_category ] );
	}


}

