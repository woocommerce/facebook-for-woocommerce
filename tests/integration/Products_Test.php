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


	/**
	 * Tests Facebook\Products::enable_sync_for_products()
	 */
	public function test_enable_sync_for_products() {

		$product = $this->get_product();

		Facebook\Products::enable_sync_for_products( [ $product ] );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $product ) );

		// repeat for variable products
		$variable_product = $this->get_variable_product();

		// get a fresh product object to ensure the status is stored
		$variable_product = wc_get_product( $variable_product->get_id() );

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $variable_product ) );

	}


	/**
	 * Tests Facebook\Products::disable_sync_for_products()
	 */
	public function test_disable_sync_for_products() {

		$product = $this->get_product();

		// enable sync first to ensure it is then disabled
		Facebook\Products::enable_sync_for_products( [ $product ] );
		// set to explicit "no"
		Facebook\Products::disable_sync_for_products( [ $product ] );

		// get a fresh product object to ensure the status is stored
		$product = wc_get_product( $product->get_id() );

		$this->assertFalse( Facebook\Products::is_sync_enabled_for_product( $product ) );

		// repeat for variable products
		$variable_product = $this->get_product();

		Facebook\Products::enable_sync_for_products( [ $variable_product ] );
		Facebook\Products::disable_sync_for_products( [ $variable_product ] );

		// get a fresh product object to ensure the status is stored
		$variable_product = wc_get_product( $variable_product->get_id() );

		$this->assertFalse( Facebook\Products::is_sync_enabled_for_product( $variable_product ) );
	}


	/**
	 * Tests that Facebook\Products::is_sync_enabled_for_product() for products that don't have a preference set.
	 */
	public function test_is_sync_enabled_for_product_default() {

		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $this->get_product() ) );
		$this->assertTrue( Facebook\Products::is_sync_enabled_for_product( $this->get_variable_product() ) );
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
	 * Gets a new variable product object.
	 *
	 * @param int[] $children array of variation IDs, if unspecified will generate the amount passed (default 3)
	 * @return \WC_Product_Variable
	 */
	private function get_variable_product( $children = [] ) {

		$product    = new \WC_Product_Variable();
		$variations = [];

		if ( empty( $children ) || is_numeric( $children ) ) {

			$total_variations = 0 !== $children && empty( $children ) ? 3 : max( 0, (int) $children );

			for ( $i = 0; $i < $total_variations; $i++ ) {

				$variation = new \WC_Product_Variation();
				$variation->save();

				$variations[] = $variation->get_id();
			}
		}

		$product->set_children( $variations );
		$product->save();

		return $product;
	}


}

