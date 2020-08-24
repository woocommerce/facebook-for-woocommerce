<?php

use SkyVerge\WooCommerce\Facebook;
use SkyVerge\WooCommerce\Facebook\Product_Categories;
use SkyVerge\WooCommerce\Facebook\Products;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Plugin_Exception;

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


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\Products::is_product_ready_for_commerce()
	 *
	 * @param bool $manage_stock_option WC general option to manage stock
	 * @param bool $manage_stock_prop product property to manage stock
	 * @param string $product_price product price
	 * @param bool $commerce_enabled commerce enabled for product
	 * @param bool $sync_enabled sync enabled for product
	 * @param bool $expected_result the expected result
	 *
	 * @dataProvider provider_is_product_ready_for_commerce
	 */
	public function test_is_product_ready_for_commerce( $manage_stock_option, $manage_stock_prop, $product_price, $commerce_enabled, $sync_enabled, $expected_result ) {

		$product = $this->get_product();

		update_option( 'woocommerce_manage_stock', $manage_stock_option ? 'yes' : 'no' );
		$product->set_manage_stock( $manage_stock_prop );
		$product->set_regular_price( $product_price );
		Products::update_commerce_enabled_for_product( $product, $commerce_enabled );
		if ($sync_enabled) {
			Products::enable_sync_for_products( [$product]);
		} else {
			Products::disable_sync_for_products( [$product]);
		}

		$this->assertEquals( $expected_result, Facebook\Products::is_product_ready_for_commerce( $product ) );
	}


	/** @see test_is_product_ready_for_commerce */
	public function provider_is_product_ready_for_commerce() {

		return [
			[ true, true, '10.00', true, true, true ],
			[ false, true, '10.00', true, true, false ],
			[ true, false, '10.00', true, true, false ],
			[ true, true, '0', true, true, false ],
			[ true, true, '10.00', false, true, false ],
			[ true, true, '10.00', true, false, false ],
		];
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\Products::is_commerce_enabled_for_product()
	 *
	 * @param string $meta_value meta value
	 * @param bool $expected_result the expected result
	 *
	 * @dataProvider provider_is_commerce_enabled_for_product
	 */
	public function test_is_commerce_enabled_for_product( $meta_value, $expected_result ) {

		$product = $this->get_product();

		if ( ! empty( $meta_value ) ) {
			$product->update_meta_data( Products::COMMERCE_ENABLED_META_KEY, $meta_value, true );
		} else {
			$product->delete_meta_data( Products::COMMERCE_ENABLED_META_KEY );
		}

		$this->assertEquals( $expected_result, Facebook\Products::is_commerce_enabled_for_product( $product ) );
	}


	/** @see test_is_commerce_enabled_for_product */
	public function provider_is_commerce_enabled_for_product() {

		return [
			[ 'yes',  true ],
			[ true,  true ],
			[ 'no', false ],
			[ false, false ],
			[ null, false ], // if a product does not have this meta set, Commerce is not enabled for it
		];
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\Products::update_commerce_enabled_for_product()
	 *
	 * @param bool $param_value param value
	 * @param string $expected_meta_value the expected meta value
	 *
	 * @dataProvider provider_update_commerce_enabled_for_product
	 */
	public function test_update_commerce_enabled_for_product( $param_value, $expected_meta_value ) {

		$product = $this->get_product();

		Products::update_commerce_enabled_for_product( $product, $param_value );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( $expected_meta_value, $product->get_meta( Products::COMMERCE_ENABLED_META_KEY ) );
	}


	/** @see test_update_commerce_enabled_for_product */
	public function provider_update_commerce_enabled_for_product() {

		return [
			[ true, 'yes' ],
			[ 'yes', 'yes' ],
			[ false,  'no' ],
			[ 'no',  'no' ],
			[ '', 'no' ],
		];
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_simple_product() {

		$product = $this->get_product();
		Products::update_google_product_category_id( $product, '1' );

		$this->assertEquals( '1', Products::get_google_product_category_id( $product ) );
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_product_variation() {

		$variable_product = $this->get_variable_product( [ 'children' => 2 ] );
		Products::update_google_product_category_id( $variable_product, '2' );
		$variable_product->save();
		$variable_product = wc_get_product( $variable_product->get_id() );

		foreach ( $variable_product->get_children() as $child_product_id ) {

			$product_variation = wc_get_product( $child_product_id );
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
	public function test_get_google_product_category_id_product_variation_multiple_categories() {

		$variable_product = $this->get_variable_product( [ 'children' => 2 ] );

		$parent_category = wp_insert_term( 'Animals & Pet Supplies', 'product_cat' );
		Product_Categories::update_google_product_category_id( $parent_category['term_id'], '8' );
		$child_category = wp_insert_term( 'Pet Supplies', 'product_cat', [ 'parent' => $parent_category['term_id'] ] );
		Product_Categories::update_google_product_category_id( $child_category['term_id'], '9' );

		wp_set_post_terms( $variable_product->get_id(), [
			$parent_category['term_id'],
			$child_category['term_id'],
		], 'product_cat' );

		foreach ( $variable_product->get_children() as $child_product_id ) {

			$product_variation = wc_get_product( $child_product_id );
			$this->assertEquals( '9', Products::get_google_product_category_id( $product_variation ) );
		}
	}


	/** @see Products::get_google_product_category_id() */
	public function test_get_google_product_category_id_default() {

		$product = $this->get_product();
		facebook_for_woocommerce()->get_commerce_handler()->update_default_google_product_category_id( '10' );

		$this->assertEquals( '10', Products::get_google_product_category_id( $product ) );
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

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( $google_product_category_id, $product->get_meta( Products::GOOGLE_PRODUCT_CATEGORY_META_KEY ) );
	}


	/** @see test_update_google_product_category_id */
	public function provider_update_google_product_category_id() {

		return [
			[ '3350' ],
			[ '' ],
		];
	}


	/** @see Facebook\Products::get_product_color_attribute() */
	public function test_get_product_color_attribute_configured_valid() {

		$color_attribute = self::create_color_attribute();

		$product = $this->get_product( [ 'attributes' => [ $color_attribute ] ] );
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->save_meta_data();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( $color_attribute->get_name(), Products::get_product_color_attribute( $product ) );
	}


	/** @see Facebook\Products::get_product_color_attribute() */
	public function test_get_product_color_attribute_configured_invalid() {

		$color_attribute = self::create_color_attribute();

		// create the product without attributes
		$product = $this->get_product();
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->save_meta_data();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( '', Products::get_product_color_attribute( $product ) );
	}


	/** @see Facebook\Products::get_product_color_attribute() */
	public function test_get_product_color_attribute_string_matching() {

		$color_attribute = self::create_color_attribute( 'product colour' );

		$product = $this->get_product( [ 'attributes' => [ $color_attribute ] ] );

		$this->assertSame( $color_attribute->get_name(), Products::get_product_color_attribute( $product ) );
	}


	/** @see Facebook\Products::get_product_color_attribute() */
	public function test_get_product_color_attribute_variation() {

		$color_attribute = self::create_color_attribute( 'color', [ 'pink', 'blue' ], true );

		$product = $this->get_variable_product();
		$product->set_attributes( [ $color_attribute ] );
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->save();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		foreach ( $product->get_children() as $child_id ) {

			$product_variation = wc_get_product( $child_id );
			$this->assertSame( $color_attribute->get_name(), Products::get_product_color_attribute( $product_variation ) );
		}
	}


	/** @see Facebook\Products::update_product_color_attribute() */
	public function test_update_product_color_attribute_valid() {

		$color_attribute = self::create_color_attribute();

		$product = $this->get_product( [ 'attributes' => [ $color_attribute ] ] );

		Products::update_product_color_attribute( $product, $color_attribute->get_name() );

		// get a fresh product object to ensure the meta is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( $color_attribute->get_name(), $product->get_meta( Products::COLOR_ATTRIBUTE_META_KEY ) );
	}


	/** @see Facebook\Products::update_product_color_attribute() */
	public function test_update_product_color_attribute_invalid() {

		$color_attribute = self::create_color_attribute();

		$product = $this->get_product( [ 'attributes' => [ $color_attribute ] ] );

		$this->expectException( SV_WC_Plugin_Exception::class );

		Products::update_product_color_attribute( $product, 'colour' );

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( '', $product->get_meta( Products::COLOR_ATTRIBUTE_META_KEY ) );
	}


	/** @see Facebook\Products::update_product_color_attribute() */
	public function test_update_product_color_attribute_already_used() {

		$color_attribute = self::create_color_attribute();
		$size_attribute  = self::create_size_attribute();

		$product = $this->get_product( [ 'attributes' => [ $size_attribute ] ] );
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save_meta_data();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->expectException( SV_WC_Plugin_Exception::class );

		Products::update_product_color_attribute( $product, $size_attribute->get_name() );

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( '', $product->get_meta( Products::COLOR_ATTRIBUTE_META_KEY ) );
	}


	/** @see Facebook\Products::get_product_color() */
	public function test_get_product_color_simple_product_single_value() {

		$color_attribute = self::create_color_attribute( 'color', [ 'pink' ] );

		$product = $this->get_product( [ 'attributes' => [ $color_attribute ] ] );
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->save();

		// get a fresh product object
        $product = wc_get_product( $product->get_id() );

		$this->assertSame( 'pink', Products::get_product_color( $product ) );
	}


	/** @see Facebook\Products::get_product_color() */
	public function test_get_product_color_variation_with_attribute_set() {

		$color_attribute = self::create_color_attribute( 'color', [ 'pink', 'blue' ], true );

		$product = $this->get_variable_product();
		$product->set_attributes( [ $color_attribute ] );
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->save();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		foreach ( $product->get_children() as $child_id ) {

			$product_variation = wc_get_product( $child_id );

			/**
			 * Unlike the parent product which uses terms, variations are assigned specific attributes using name value pairs.
			 * @see WC_Product_Variation::set_attributes()
			 */
			$product_variation->set_attributes( [ 'color' => 'pink' ] );
			$product_variation->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
			$product_variation->save();

			// get a fresh product object
			$product_variation = wc_get_product( $child_id );

			$this->assertSame( 'pink', Products::get_product_color( $product_variation ) );
		}
	}


	/** @see Facebook\Products::get_product_color() */
	public function test_get_product_color_variation_without_attribute_set() {

		$color_attribute = self::create_color_attribute( 'color', [ 'pink', 'blue' ], true );

		$product = $this->get_variable_product();
		$product->set_attributes( [ $color_attribute ] );
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->save();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		foreach ( $product->get_children() as $child_id ) {

			$product_variation = wc_get_product( $child_id );
			$this->assertSame( 'pink | blue', Products::get_product_color( $product_variation ) );
		}
	}


	/** @see Facebook\Products::get_product_size_attribute() */
	public function test_get_product_size_attribute_configured_valid() {

		$size_attribute = self::create_size_attribute();

		$product = $this->get_product( [ 'attributes' => [ $size_attribute ] ] );
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save_meta_data();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( $size_attribute->get_name(), Products::get_product_size_attribute( $product ) );
	}


	/** @see Facebook\Products::get_product_size_attribute() */
	public function test_get_product_size_attribute_configured_invalid() {

		$size_attribute = self::create_size_attribute();

		// create the product without attributes
		$product = $this->get_product();
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save_meta_data();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( '', Products::get_product_size_attribute( $product ) );
	}


	/** @see Facebook\Products::get_product_size_attribute() */
	public function test_get_product_size_attribute_string_matching() {

		$size_attribute = self::create_size_attribute( 'product size' );

		$product = $this->get_product( [ 'attributes' => [ $size_attribute ] ] );

		$this->assertSame( $size_attribute->get_name(), Products::get_product_size_attribute( $product ) );
	}


	/** @see Facebook\Products::get_product_size_attribute() */
	public function test_get_product_size_attribute_variation() {

		$size_attribute = self::create_size_attribute( 'size', [ 'small', 'medium', 'large' ], true );

		$product = $this->get_variable_product();
		$product->set_attributes( [ $size_attribute ] );
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		foreach ( $product->get_children() as $child_id ) {

			$product_variation = wc_get_product( $child_id );
			$this->assertSame( $size_attribute->get_name(), Products::get_product_size_attribute( $product_variation ) );
		}
	}


	/** @see Facebook\Products::update_product_size_attribute() */
	public function test_update_product_size_attribute_valid() {

		$size_attribute = self::create_size_attribute();

		$product = $this->get_product( [ 'attributes' => [ $size_attribute ] ] );

		Products::update_product_size_attribute( $product, $size_attribute->get_name() );

		// get a fresh product object to ensure the meta is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( $size_attribute->get_name(), $product->get_meta( Products::SIZE_ATTRIBUTE_META_KEY ) );
	}


	/** @see Facebook\Products::update_product_size_attribute() */
	public function test_update_product_size_attribute_invalid() {

		$size_attribute = self::create_size_attribute();

		$product = $this->get_product( [ 'attributes' => [ $size_attribute ] ] );

		$this->expectException( \Exception::class );

		Products::update_product_size_attribute( $product, 'height' );

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( '', $product->get_meta( Products::SIZE_ATTRIBUTE_META_KEY ) );
	}


	/** @see Facebook\Products::update_product_size_attribute() */
	public function test_update_product_size_attribute_already_used() {

		$color_attribute = self::create_color_attribute();
		$size_attribute  = self::create_size_attribute();

		$product = $this->get_product( [ 'attributes' => [ $size_attribute ] ] );
		$product->update_meta_data( Products::COLOR_ATTRIBUTE_META_KEY, $color_attribute->get_name() );
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save_meta_data();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->expectException( \Exception::class );

		Products::update_product_size_attribute( $product, $color_attribute->get_name() );

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		$this->assertSame( '', $product->get_meta( Products::SIZE_ATTRIBUTE_META_KEY ) );
	}


	/** @see Facebook\Products::get_product_size() */
	public function test_get_product_size_simple_product_single_value() {

		$size_attribute = self::create_size_attribute( 'size', [ 'small' ] );

		$product = $this->get_product( [ 'attributes' => [ $size_attribute ] ] );
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save();

		// get a fresh product object
        $product = wc_get_product( $product->get_id() );

		$this->assertSame( 'small', Products::get_product_size( $product ) );
	}


	/** @see Facebook\Products::get_product_size() */
	public function test_get_product_size_variation_with_attribute_set() {

		$size_attribute = self::create_size_attribute( 'size', [ 'small', 'medium', 'large' ], true );

		$product = $this->get_variable_product();
		$product->set_attributes( [ $size_attribute ] );
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		foreach ( $product->get_children() as $child_id ) {

			$product_variation = wc_get_product( $child_id );

			/**
			 * Unlike the parent product which uses terms, variations are assigned specific attributes using name value pairs.
			 * @see WC_Product_Variation::set_attributes()
			 */
			$product_variation->set_attributes( [ 'size' => 'small' ] );
			$product_variation->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
			$product_variation->save();

			// get a fresh product object
			$product_variation = wc_get_product( $child_id );

			$this->assertSame( 'small', Products::get_product_size( $product_variation ) );
		}
	}


	/** @see Facebook\Products::get_product_size() */
	public function test_get_product_size_variation_without_attribute_set() {

		$size_attribute = self::create_size_attribute( 'size', [ 'small', 'medium', 'large' ], true );

		$product = $this->get_variable_product();
		$product->set_attributes( [ $size_attribute ] );
		$product->update_meta_data( Products::SIZE_ATTRIBUTE_META_KEY, $size_attribute->get_name() );
		$product->save();

		// get a fresh product object
		$product = wc_get_product( $product->get_id() );

		foreach ( $product->get_children() as $child_id ) {

			$product_variation = wc_get_product( $child_id );
			$this->assertSame( 'small | medium | large', Products::get_product_size( $product_variation ) );
		}
	}


	/** @see Facebook\Products::get_available_product_attributes() */
	public function test_get_available_product_attributes() {

		$product = $this->get_product( [ 'attributes' => self::create_product_attributes() ] );

		$this->assertSame( $product->get_attributes(), Products::get_available_product_attributes( $product ) );
	}


	/** @see Facebook\Products::get_distinct_product_attributes() */
	public function test_get_distinct_product_attributes() {

		$attributes = self::create_product_attributes();
		$product    = $this->get_product( [ 'attributes' => $attributes ] );

		list( $color_attribute, $size_attribute, $pattern_attribute ) = $attributes;

		Products::update_product_color_attribute( $product, $color_attribute->get_name() );
		Products::update_product_size_attribute( $product, $size_attribute->get_name() );
		Products::update_product_pattern_attribute( $product, $pattern_attribute->get_name() );

		$this->assertSame( array_filter( [
			Products::get_product_color_attribute( $product ),
			Products::get_product_size_attribute( $product ),
			Products::get_product_pattern_attribute( $product ),
		] ), Products::get_distinct_product_attributes( $product ) );
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


	/**
	 * Creates color attribute.
	 *
	 * @param string $name attribute name
	 * @param string[] $options possible values for the attribute
	 * @param bool $variation used for variations or not
	 * @return \WC_Product_Attribute
	 */
	private function create_color_attribute( $name = 'color', $options = [ 'pink', 'blue' ], $variation = false ) {

		$color_attribute = new WC_Product_Attribute();
		$color_attribute->set_name( $name );
		$color_attribute->set_options( $options );
		$color_attribute->set_variation( $variation );

		return $color_attribute;
	}


	/**
	 * Creates size attribute.
	 *
	 * @param string $name attribute name
	 * @param string[] $options possible values for the attribute
	 * @param bool $variation used for variations or not
	 * @return \WC_Product_Attribute
	 */
	private function create_size_attribute( $name = 'size', $options = [ 'small', 'medium', 'large' ], $variation = false ) {

		$size_attribute = new WC_Product_Attribute();
		$size_attribute->set_name( $name );
		$size_attribute->set_options( $options );
		$size_attribute->set_variation( $variation );

		return $size_attribute;
	}


	/**
	 * Creates pattern attribute.
	 *
	 * @param string $name attribute name
	 * @param string[] $options possible values for the attribute
	 * @param bool $variation used for variations or not
	 * @return \WC_Product_Attribute
	 */
	private function create_pattern_attribute( $name = 'pattern', $options = [ 'checked', 'floral', 'leopard' ], $variation = false ) {

		$pattern_attribute = new WC_Product_Attribute();
		$pattern_attribute->set_name( $name );
		$pattern_attribute->set_options( $options );
		$pattern_attribute->set_variation( $variation );

		return $pattern_attribute;
	}


	/**
	 * Creates product attributes.
	 */
	private function create_product_attributes() {

		return [
			self::create_color_attribute(),
			self::create_size_attribute(),
			self::create_pattern_attribute(),
		];
	}


}

