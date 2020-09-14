<?php

use SkyVerge\WooCommerce\Facebook\Admin;

/**
 * Tests the Admin\Google_Product_Category_Field class.
 */
class GoogleProductCategoryFieldTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Admin/Google_Product_Category_Field.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Admin\Google_Product_Category_Field::render() */
	public function test_render() {
		global $wc_queued_js;

		$field = new Admin\Google_Product_Category_Field();
		$field->render( 'this-input' );

		$this->assertStringContainsString( 'new WC_Facebook_Google_Product_Category_Fields', $wc_queued_js );
	}

	// TODO: add test for get_categories()


}
