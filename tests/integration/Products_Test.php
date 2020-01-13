<?php

use SkyVerge\WooCommerce\Facebook;

/**
 * Tests the Products class.
 */
class Products_Test extends \Codeception\TestCase\WPTestCase {


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


	/** @see Facebook\Products::enable_sync_for_products() */
	public function test_enable_sync_for_products() {

		$product = $this->get_product();

		Facebook\Products::enable_sync_for_products( [ $product ] );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $product ) );
	}


	/** @see Facebook\Products::disable_sync_for_products() */
	public function test_disable_sync_for_products() {

		$product = $this->get_product();

		// enable sync first to ensure it is then disabled
		Facebook\Products::enable_sync_for_products( [ $product ] );

		Facebook\Products::disable_sync_for_products( [ $product ] );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertFalse( Facebook\Products::is_sync_enabled_for_product( $product ) );
	}


	/** @see Tests that Facebook\Products::is_sync_enabled_for_product() for products that don't have a preference set */
	public function test_is_sync_enabled_for_product_default() {

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $this->get_product() ) );
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


}

