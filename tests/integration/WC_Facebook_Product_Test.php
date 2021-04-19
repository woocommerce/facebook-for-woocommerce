<?php

use SkyVerge\WooCommerce\Facebook\Products;

/**
 * Tests the Facebook product class.
 */
class WC_Facebook_Product_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/



	/** @see \WC_Facebook_Product::prepare_product() */
	public function test_prepare_product_not_ready_for_commerce_inventory() {

		$product = $this->tester->get_product();

		Products::enable_sync_for_products( [ $product ] );

		$data = ( new \WC_Facebook_Product( $product ) )->prepare_product( null, \WC_Facebook_Product::PRODUCT_PREP_TYPE_ITEMS_BATCH );

		$this->assertArrayNotHasKey( 'inventory', $data );
	}


	/** @see \WC_Facebook_Product::prepare_product() */
	public function test_prepare_product_not_ready_for_commerce_google_product_category() {

		$product = $this->tester->get_product();

		Products::enable_sync_for_products( [ $product ] );

		$data = ( new \WC_Facebook_Product( $product ) )->prepare_product( null, \WC_Facebook_Product::PRODUCT_PREP_TYPE_ITEMS_BATCH );

		$this->assertArrayNotHasKey( 'google_product_category', $data );
	}


	/** @see \WC_Facebook_Product::prepare_product() */
	public function test_prepare_product_ready_for_commerce_google_product_category() {

		$product = $this->get_product_ready_for_commerce();

		Products::update_google_product_category_id( $product, '1234' );

		$data = ( new \WC_Facebook_Product( $product ) )->prepare_product( null, \WC_Facebook_Product::PRODUCT_PREP_TYPE_ITEMS_BATCH );

		$this->assertSame( '1234', $data['google_product_category'] );
	}

	/**
	 * This is a regression test, used to check that we correctly set string
	 * lists as arrays before we send them
	 *
	 * @see \WC_Facebook_Product::apply_enhanced_catalog_fields_from_attributes()
	 * */
	public function test_prepare_product_ready_for_commerce_google_product_category_with_additional_features() {
		$product = $this->get_product_ready_for_commerce();
		Products::update_google_product_category_id( $product, '1604' );
		Products::update_product_enhanced_catalog_attribute( $product, 'additional_features', 'Embroidered, Nice' );
		$data = ( new \WC_Facebook_Product( $product ) )->prepare_product( null, \WC_Facebook_Product::PRODUCT_PREP_TYPE_ITEMS_BATCH );

		$this->assertIsArray( $data['additional_features'] );
		$this->assertSame( array( 'Embroidered', 'Nice' ), $data['additional_features'] );
	}

	/**
	 * @see \WC_Facebook_Product::prepare_product()
	 *
	 * @dataProvider provider_prepare_product_ready_for_commerce_inventory
	 *
	 * @param int|string $woo_quantity WooCommerce stock quantity
	 * @param int $facebook_expected expected Facebook inventory value
	 */
	public function test_prepare_product_ready_for_commerce_inventory( $woo_quantity, $facebook_expected ) {

		$product = $this->tester->get_product( [
			'status'         => 'publish',
			'regular_price'  => '1.00',
			'manage_stock'   => true,
			'stock_quantity' => $woo_quantity,
		] );

		Products::enable_sync_for_products( [ $product ] );

		$data = ( new \WC_Facebook_Product( $product ) )->prepare_product();

		$this->assertSame( $facebook_expected, $data['inventory'] );
	}


	/** @see test_prepare_product_ready_for_commerce_inventory */
	public function provider_prepare_product_ready_for_commerce_inventory() {

		return [
			'valid stock quantity'    => [ 4, 4 ],
			'negative stock quantity' => [ -4, 0 ],
			'invalid stock quantity'  => [ 'asdf', 0 ],
		];
	}


	/**
	 * @see \WC_Facebook_Product::prepare_product()
	 *
	 * @dataProvider provider_prepare_product_uses_correct_number_of_additional_image_urls
	 */
	public function test_prepare_product_uses_correct_number_of_additional_image_urls( int $images_count ) {

		$product = $this->tester->get_product();

		$attachments = array_map( function() {

			return wp_insert_attachment( [] );
		}, range( 1, $images_count ) );

		$product->update_meta_data( '_thumbnail_id', $attachments[0] );
		$product->update_meta_data( '_product_image_gallery', implode( ',', array_slice( $attachments, 1 ) ) );
		$product->save_meta_data();

		$data = ( new \WC_Facebook_Product( $product->get_id() ) )->prepare_product();

		$this->assertLessThanOrEqual( 20, count( $data['additional_image_urls'] ) );
	}


	/** @see test_prepare_product_uses_correct_number_of_additional_image_urls() */
	public function provider_prepare_product_uses_correct_number_of_additional_image_urls() {

		return [
			[ 1 ],
			[ 2 ],
			[ 10 ],
			[ 15 ],
			[ 25 ],
		];
	}


	/**
	 * @see \WC_Facebook_Product::prepare_product()
	 *
	 * @param mixed $price the regular price for the product
	 * @param bool $is_visible whether the product should visible in the Facebook Shop
	 * @param string $visibility 'staging' or 'published'
	 * @dataProvider provider_prepare_product_sets_product_visibility
	 */
	public function test_prepare_product_sets_product_visibility( $price, $is_visible, $visibility ) {

		$product = $this->tester->get_product( [ 'regular_price' => $price ] );

		Products::set_product_visibility( $product, $is_visible );

		$data = ( new \WC_Facebook_Product( $product ) )->prepare_product();

		$this->assertSame( $visibility, $data['visibility'] );
	}


	/** @see test_prepare_product_sets_product_visibility() */
	public function provider_prepare_product_sets_product_visibility() {

		return [
			[ '',    true, \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_VISIBLE ],
			[ 0.00,  true, \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_VISIBLE ],
			[ 14.99, true, \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_VISIBLE ],

			[ '',    false, \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN ],
			[ 0.00,  false, \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN ],
			[ 14.99, false, \WC_Facebookcommerce_Integration::FB_SHOP_PRODUCT_HIDDEN ],
		];
	}


	/** @see \WC_Facebook_Product::prepare_variants_for_group() */
	public function test_prepare_variants_for_group_custom_attribute() {

		$attributes = [
			$this->tester->create_color_attribute( 'Custom attribute', [ 'red', 'blue' ], true ), // totally custom, but set as the Color attribute
			$this->tester->create_size_attribute( 'Size', [ 'small', 'large' ], true ), // not explicitly set, but interpreted by name
			$this->tester->create_size_attribute( 'Flavor', [ 'savory', 'sweet' ], true ), // completely custom
			$this->tester->create_pattern_attribute( 'Pattern', [ 'striped' ] ) // not used for variations
		];

		$product = $this->tester->get_variable_product( [
			'status'         => 'publish',
			'regular_price'  => '1.00',
			'manage_stock'   => true,
			'stock_quantity' => 100,
			'attributes'     => $attributes,
			'children'       => 0,
		] );

		$product->get_data_store()->create_all_product_variations( $product );

		Products::update_product_color_attribute( $product, 'custom-attribute' );

		$data = ( new \WC_Facebook_Product( $product ) )->prepare_variants_for_group();

		$this->assertEquals( [
			[
				'product_field' => 'color',
				'label'         => 'Custom attribute',
				'options'       => [
					'red',
					'blue',
				],
			],
			[
				'product_field' => 'size',
				'label'         => 'Size',
				'options'       => [
					'small',
					'large',
				],
			],
			[
				'product_field' => 'custom_data:flavor',
				'label'         => 'Flavor',
				'options'       => [
					'savory',
					'sweet',
				],
			],
		], $data );
	}


	/**
	 * @see \WC_Facebook_Product::get_fb_price()
	 *
	 * @param float $product_price product price
	 * @param string $tax_display incl or excl
	 * @param float $expected_price expected facebook price
	 *
	 * @dataProvider data_provider_get_fb_price
	 */
	public function test_get_fb_price( $product_price, $tax_display, $expected_price ) {

		$this->check_fb_price( $this->tester->get_product( [ 'regular_price' => $product_price ] ), $tax_display, $expected_price );
	}


	/**
	 * Tests that the returned Facebook price matches the expected value.
	 *
	 * @param \WC_Product $product product object
	 * @param string $tax_display incl or excl
	 * @param float $expected_price expected facebook price
	 */
	private function check_fb_price( $product, $tax_display, $expected_price ) {

		// create tax
		\WC_Tax::_insert_tax_rate( [
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => 10.000,
			'tax_rate_name'     => 'TEST',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 0,
		] );

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_display_shop', $tax_display );

		$this->assertSame( $expected_price, ( new WC_Facebook_Product( $product->get_id() ) )->get_fb_price() );
	}


	/** @see test_get_fb_price() */
	public function data_provider_get_fb_price() {

		return [
			'including taxes' => [ 19.99, 'incl', 2199 ],
			'excluding taxes' => [ 19.99, 'excl', 1999 ],
		];
	}


	/**
	 * @see \WC_Facebook_Product::get_fb_price()
	 *
	 * @param float $product_price product price
	 * @param string $tax_display incl or excl
	 *
	 * @dataProvider data_provider_get_fb_price
	 */
	public function test_get_fb_price_from_meta( $product_price, $tax_display ) {

		$product = $this->tester->get_product( [ 'regular_price' => wp_rand() ] );

		$product->update_meta_data( WC_Facebook_Product::FB_PRODUCT_PRICE, $product_price );
		$product->save_meta_data();

		// current behavior is to return the stored price without modifications regardless of tax settings
		$this->check_fb_price( $product, $tax_display, (int) round( $product_price * 100 ) );
	}


	/** @see \WC_Facebook_Product::get_fb_description() */
	public function test_get_fb_description_simple_standard_description() {

		$this->simple_product = $this->get_product();

		add_filter( 'wc_facebook_product_description_mode', function() {
			return \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_STANDARD;
		} );

		$fb_product = new WC_Facebook_Product( $this->simple_product->get_id() );

		$this->assertEquals( 'Standard Description.', $fb_product->get_fb_description() );
	}


	/** @see \WC_Facebook_Product::get_fb_description() */
	public function test_get_fb_description_simple_short_description() {

		$this->simple_product = $this->get_product();

		add_filter( 'wc_facebook_product_description_mode', function() {
			return \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT;
		} );

		$fb_product = new WC_Facebook_Product( $this->simple_product->get_id() );

		$this->assertEquals( 'Short Description.', $fb_product->get_fb_description() );
	}


	/** @see \WC_Facebook_Product::get_fb_description() */
	public function test_get_fb_description_simple_custom_description() {

		$this->simple_product = $this->get_product();

		$this->simple_product->update_meta_data( \WC_Facebook_Product::FB_PRODUCT_DESCRIPTION, 'Custom Description.' );
		$this->simple_product->save_meta_data();

		$fb_product = new WC_Facebook_Product( $this->simple_product->get_id() );

		$this->assertEquals( 'Custom Description.', $fb_product->get_fb_description() );
	}


	/** @see \WC_Facebook_Product::get_fb_description() */
	public function test_get_fb_description_variation_standard_parent_description() {

		$variable_product  = $this->get_variable_product();
		$product_variation = wc_get_product( current( $variable_product->get_children() ) );

		add_filter( 'wc_facebook_product_description_mode', function() {
			return \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_STANDARD;
		} );

		$parent_product = new WC_Facebook_Product( $variable_product->get_id() );
		$fb_product     = new WC_Facebook_Product( $product_variation->get_id(), $parent_product );

		$this->assertEquals( 'Standard Description.', $fb_product->get_fb_description() );
	}


	/** @see \WC_Facebook_Product::get_fb_description() */
	public function test_get_fb_description_variation_short_parent_description() {

		$variable_product  = $this->get_variable_product();
		$product_variation = wc_get_product( current( $variable_product->get_children() ) );

		add_filter( 'wc_facebook_product_description_mode', function() {
			return \WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT;
		} );

		$parent_product = new WC_Facebook_Product( $variable_product->get_id() );
		$fb_product     = new WC_Facebook_Product( $product_variation->get_id(), $parent_product );

		$this->assertEquals( 'Short Description.', $fb_product->get_fb_description() );
	}


	/** @see \WC_Facebook_Product::get_fb_description() */
	public function test_get_fb_description_variation_description() {

		$variable_product  = $this->get_variable_product();
		$product_variation = wc_get_product( current( $variable_product->get_children() ) );

		$product_variation->set_description( 'Variation Description.' );
		$product_variation->save();

		$parent_product = new WC_Facebook_Product( $variable_product->get_id() );
		$fb_product     = new WC_Facebook_Product( $product_variation->get_id(), $parent_product );

		$this->assertEquals( 'Variation Description.', $fb_product->get_fb_description() );
	}


	/** @see \WC_Facebook_Product::get_fb_description() */
	public function test_get_fb_description_variation_custom_description() {

		$variable_product  = $this->get_variable_product();
		$product_variation = wc_get_product( current( $variable_product->get_children() ) );

		$product_variation->update_meta_data( \WC_Facebook_Product::FB_PRODUCT_DESCRIPTION, 'Custom Description.' );
		$product_variation->save_meta_data();

		$parent_product = new WC_Facebook_Product( $variable_product->get_id() );
		$fb_product     = new WC_Facebook_Product( $product_variation->get_id(), $parent_product );

		$this->assertEquals( 'Custom Description.', $fb_product->get_fb_description() );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets a new product object with descriptions.
	 *
	 * @return \WC_Product
	 */
	private function get_product() {

		$product = $this->tester->get_product();

		$product->set_description( 'Standard Description.' );
		$product->set_short_description( 'Short Description.' );
		$product->save();

		return $product;
	}


	/**
	 * Gets a product that's ready for Commerce.
	 *
	 * @param \WC_Product_Attribute[] $attributes product attributes to set
	 * @return \WC_Product
	 */
	private function get_product_ready_for_commerce( $attributes = [] ) {

		$product = $this->tester->get_product( [
			'status'         => 'publish',
			'regular_price'  => '1.00',
			'manage_stock'   => true,
			'stock_quantity' => 100,
			'attributes'     => $attributes,
		] );

		Products::enable_sync_for_products( [ $product ] );

		return $product;
	}


	/**
	 * Gets a new variable product object with descriptions.
	 *
	 * @return \WC_Product_Variable
	 */
	private function get_variable_product() {

		$product = $this->tester->get_variable_product();

		$product->set_description( 'Standard Description.' );
		$product->set_short_description( 'Short Description.' );
		$product->save();

		return $product;
	}


}
