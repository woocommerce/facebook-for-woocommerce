<?php

use SkyVerge\WooCommerce\Facebook\Products\Stock;

/**
 * Tests the Stock class.
 */
class StockTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	public function test_set_product_stock_with_simple_product() {

		$product = $this->tester->get_product();

		$this->tester->clearSyncRequests();

		$this->get_stock()->set_product_stock( $product );

		$this->tester->assertProductsAreScheduledForSync( [ $product->get_id() ] );
	}


	public function test_set_product_stock_with_variable_product() {

		$product = $this->tester->get_variable_product();

		$this->tester->clearSyncRequests();

		$this->get_stock()->set_product_stock( $product );

		$this->tester->assertProductsAreScheduledForSync( $product->get_children() );
	}


	public function test_set_product_stock_with_invalid_product() {

		$this->tester->clearSyncRequests();

		$this->get_stock()->set_product_stock( null );

		$this->tester->assertProductsAreNotScheduledForSync();
	}


	public function test_set_product_stock_with_out_of_stock_product() {

		update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );

		$product = $this->tester->get_product();

		$product->set_manage_stock( true );
		$product->set_stock_quantity( 0 );
		$product->save();

		$this->tester->clearSyncRequests();

		$this->get_stock()->set_product_stock( $product );

		$this->tester->assertProductsAreScheduledForDelete( [ $product->get_id() ] );
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the handler instance.
	 *
	 * @return Stock
	 */
	private function get_stock() {

		return new Stock();
	}


}
