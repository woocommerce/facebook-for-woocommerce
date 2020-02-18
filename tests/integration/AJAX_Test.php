<?php

use SkyVerge\WooCommerce\Facebook;
use SkyVerge\WooCommerce\Facebook\AJAX;

/**
 * Tests the AJAX class.
 */
class AJAX_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var AJAX */
	protected $ajax;

	/** @var ReflectionMethod */
	protected $get_products_to_be_excluded;

	/** @var \WC_Facebookcommerce_Integration */
	protected $integration;

	/** @var int[] excluded product category ID */
	private $excluded_categories = [];

	/** @var int[] excluded product tag ID */
	private $excluded_tags = [];

	/** @var int ID of the new category being added */
	private $new_category;

	/** @var int ID of the new tag being added */
	private $new_tag;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/AJAX.php';

		$this->integration = facebook_for_woocommerce()->get_integration();

		// simulate a complete plugin configuration so that actions and filters callbacks are setup
		$this->integration->api_key            = '1234';
		$this->integration->product_catalog_id = '1234';

		$this->get_products_to_be_excluded = self::getMethod( AJAX::class, 'get_products_to_be_excluded' );

		$this->ajax = new AJAX();

		$this->add_excluded_categories();
		$this->add_excluded_tags();

		$new_category       = wp_insert_term( 'New category', 'product_cat' );
		$this->new_category = $new_category['term_id'];

		$new_tag       = wp_insert_term( 'New tag', 'product_tag' );
		$this->new_tag = $new_tag['term_id'];
	}


	/** Test methods **************************************************************************************************/


	/** @see Facebook\AJAX::get_products_to_be_excluded() */
	public function test_get_products_to_be_excluded_no_new_terms() {

		// no added categories or tags
		$products_to_be_excluded = $this->get_products_to_be_excluded->invokeArgs( $this->ajax, [] );
		$this->assertEqualSets( [], $products_to_be_excluded );
	}


	/** @see Facebook\AJAX::get_products_to_be_excluded() */
	public function test_get_products_to_be_excluded_new_category() {

		$product_to_be_excluded = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_to_be_excluded ] );
		$product_to_be_excluded->set_category_ids( [ $this->new_category ] );
		$product_to_be_excluded->save();

		$product_sync_disabled = $this->get_product();
		Facebook\Products::disable_sync_for_products( [ $product_sync_disabled ] );
		$product_sync_disabled->set_category_ids( [ $this->new_category ] );
		$product_sync_disabled->save();

		$product_already_excluded = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_already_excluded ] );
		$product_already_excluded->set_category_ids( array_merge( $this->excluded_categories, [ $this->new_category ] ) );
		$product_already_excluded->save();

		$products_to_be_excluded = $this->get_products_to_be_excluded->invokeArgs( $this->ajax, [ [ $this->new_category ] ] );
		$this->assertEqualSets( [ $product_to_be_excluded->get_id() ], $products_to_be_excluded );
	}


	/** @see Facebook\AJAX::get_products_to_be_excluded() */
	public function test_get_products_to_be_excluded_new_category_without_products() {

		$products_to_be_excluded = $this->get_products_to_be_excluded->invokeArgs( $this->ajax, [ [ $this->new_category ] ] );
		$this->assertEqualSets( [], $products_to_be_excluded );
	}


	/** @see Facebook\AJAX::get_products_to_be_excluded() */
	public function test_get_products_to_be_excluded_new_tag() {

		$product_to_be_excluded = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_to_be_excluded ] );
		$product_to_be_excluded->set_tag_ids( [ $this->new_tag ] );
		$product_to_be_excluded->save();

		$product_sync_disabled = $this->get_product();
		Facebook\Products::disable_sync_for_products( [ $product_sync_disabled ] );
		$product_sync_disabled->set_tag_ids( [ $this->new_tag ] );
		$product_sync_disabled->save();

		$product_already_excluded = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_already_excluded ] );
		$product_already_excluded->set_tag_ids( array_merge( $this->excluded_tags, [ $this->new_tag ] ) );
		$product_already_excluded->save();

		$products_to_be_excluded = $this->get_products_to_be_excluded->invokeArgs( $this->ajax, [ [], [ $this->new_tag ] ] );
		$this->assertEqualSets( [ $product_to_be_excluded->get_id() ], $products_to_be_excluded );
	}


	/** @see Facebook\AJAX::get_products_to_be_excluded() */
	public function test_get_products_to_be_excluded_new_tag_without_products() {

		$products_to_be_excluded = $this->get_products_to_be_excluded->invokeArgs( $this->ajax, [ [], [ $this->new_tag ] ] );
		$this->assertEqualSets( [], $products_to_be_excluded );
	}


	/** @see Facebook\AJAX::get_products_to_be_excluded() */
	public function test_get_products_to_be_excluded_new_category_and_new_tag() {

		$product_to_be_excluded_by_category = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_to_be_excluded_by_category ] );
		$product_to_be_excluded_by_category->set_category_ids( [ $this->new_category ] );
		$product_to_be_excluded_by_category->save();

		$product_to_be_excluded_by_tag = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_to_be_excluded_by_tag ] );
		$product_to_be_excluded_by_tag->set_tag_ids( [ $this->new_tag ] );
		$product_to_be_excluded_by_tag->save();

		$product_to_be_excluded_by_both = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_to_be_excluded_by_both ] );
		$product_to_be_excluded_by_category->set_category_ids( [ $this->new_category ] );
		$product_to_be_excluded_by_both->set_tag_ids( [ $this->new_tag ] );
		$product_to_be_excluded_by_both->save();

		$product_sync_disabled = $this->get_product();
		Facebook\Products::disable_sync_for_products( [ $product_sync_disabled ] );
		$product_to_be_excluded_by_category->set_category_ids( [ $this->new_category ] );
		$product_to_be_excluded_by_both->set_tag_ids( [ $this->new_tag ] );
		$product_sync_disabled->save();

		$product_already_excluded_by_category = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_already_excluded_by_category ] );
		$product_already_excluded_by_category->set_category_ids( array_merge( $this->excluded_categories, [ $this->new_category ] ) );
		$product_already_excluded_by_category->save();

		$product_already_excluded_by_tag = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_already_excluded_by_tag ] );
		$product_already_excluded_by_tag->set_tag_ids( array_merge( $this->excluded_tags, [ $this->new_tag ] ) );
		$product_already_excluded_by_tag->save();

		$product_already_excluded_by_both = $this->get_product();
		Facebook\Products::enable_sync_for_products( [ $product_already_excluded_by_both ] );
		$product_already_excluded_by_both->set_category_ids( array_merge( $this->excluded_categories, [ $this->new_category ] ) );
		$product_already_excluded_by_both->set_tag_ids( array_merge( $this->excluded_tags, [ $this->new_tag ] ) );
		$product_already_excluded_by_both->save();

		$products_to_be_excluded = $this->get_products_to_be_excluded->invokeArgs( $this->ajax, [ [ $this->new_category ], [ $this->new_tag ] ] );
		$expected_result = [
			$product_to_be_excluded_by_category->get_id(),
			$product_to_be_excluded_by_tag->get_id(),
			$product_to_be_excluded_by_both->get_id(),
		];
		$this->assertEqualSets( $expected_result, $products_to_be_excluded );
	}


	/** Helper methods ************************************************************************************************/


	/**
	 * Gets a new product object.
	 *
	 * @return \WC_Product
	 */
	private function get_product() {

		$product = new \WC_Product();
		$product->save();

		return $product;
	}


	/**
	 * Adds excluded categories.
	 */
	private function add_excluded_categories() {

		$category                    = wp_insert_term( 'Excluded category', 'product_cat' );
		$this->excluded_categories[] = $category['term_id'];

		$category                    = wp_insert_term( 'Another excluded category', 'product_cat' );
		$this->excluded_categories[] = $category['term_id'];

		$settings = get_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', [] );

		$settings[ \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS ] = $this->excluded_categories;

		update_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', $settings );

		// ensure the settings are reloaded before tests
		$this->integration->init_settings();
	}


	/**
	 * Adds excluded tags.
	 */
	private function add_excluded_tags() {

		$tag                   = wp_insert_term( 'Excluded tag', 'product_tag' );
		$this->excluded_tags[] = $tag['term_id'];

		$tag                   = wp_insert_term( 'Another excluded tag', 'product_tag' );
		$this->excluded_tags[] = $tag['term_id'];

		$settings = get_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', [] );

		$settings[ \WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS ] = $this->excluded_tags;

		update_option( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', $settings );

		// ensure the settings are reloaded before tests
		$this->integration->init_settings();
	}


	/**
	 * Use reflection to make a method public so we can test it.
	 *
	 * @param string $class_name class name
	 * @param string $method_name method name
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	protected static function getMethod( $class_name, $method_name ) {

		$class  = new ReflectionClass( $class_name );
		$method = $class->getMethod( $method_name );
		$method->setAccessible( true );

		return $method;
	}


}
