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


	// TODO: add test for render()

	// TODO: add test for get_categories()


}
