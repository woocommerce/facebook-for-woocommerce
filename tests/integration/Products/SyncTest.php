<?php

use SkyVerge\WooCommerce\Facebook\Products\Sync;

/**
 * Tests the Sync class.
 */
class SyncTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	/** @see Sync::create_or_update_all_products() */
	public function test_create_or_update_all_products() {

		// create a variable product with three variations and define their prices
		$variable_product = $this->tester->get_variable_product( 3 );

		foreach ( $variable_product->get_children() as $variation_id ) {

			$variation = wc_get_product( $variation_id );
			$variation->set_regular_price( 4.99 );
			$variation->save();
		}

		// create a variable product with three variations but no price
		$this->tester->get_variable_product( 3 );

		// create a simple product with price
		$simple_product = $this->tester->get_product();
		$simple_product->set_regular_price( 10 );
		$simple_product->save();

		// add all eligible products to the sync queue
		$sync = $this->get_sync();

		$sync->create_or_update_all_products();

		$requests_property = new \ReflectionProperty( Sync::class, 'requests' );
		$requests_property->setAccessible( true );

		$requests = $requests_property->getValue( $sync );

		// test that create_or_update_all_products() added the three variations with price to the sync queue
		foreach ( $variable_product->get_children() as $variation_id ) {

			$index = Sync::PRODUCT_INDEX_PREFIX . $variation_id;

			$this->assertArrayHasKey( $index, $requests );
			$this->assertEquals( 'UPDATE', $requests[ $index ] );
		}

		// test that create_or_update_all_products() added the simple product with price to the sync queue
		$this->assertArrayHasKey( Sync::PRODUCT_INDEX_PREFIX . $simple_product->get_id(), $requests );
		$this->assertEquals( 'UPDATE', $requests[ Sync::PRODUCT_INDEX_PREFIX . $simple_product->get_id() ] );

		// test no other products or variations were added
		$this->assertEquals( count( $variable_product->get_children() ) + 1, count( $requests ) );
	}


	/** @see Sync::create_or_update_products() */
	public function test_create_or_update_products() {

		$product_ids = [ 123, 456 ];

		$sync = $this->get_sync();

		$sync->create_or_update_products( $product_ids );

		$requests_property = new \ReflectionProperty( Sync::class, 'requests' );
		$requests_property->setAccessible( true );

		$requests = $requests_property->getValue( $sync );

		$this->assertIsArray( $requests );
		$this->assertArrayHasKey( 'p-123', $requests );
		$this->assertArrayHasKey( 'p-456', $requests );
		$this->assertEquals( 'UPDATE', $requests['p-123'] );
		$this->assertEquals( 'UPDATE', $requests['p-456'] );
	}


	/**
     * @see Sync::delete_products()
     *
     * @dataProvider provider_delete_products()
     */
    public function test_delete_products( $product_ids ) {

        // TODO: remove when this file is included in the main plugin class {WV 2020-05-19}
	    if ( ! class_exists( Sync::class ) ) {
            require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/Products/Sync.php';
	    }

        $sync = new Sync();

        $sync->delete_products( $product_ids );

        $requests_property = new ReflectionProperty( Sync::class, 'requests' );
        $requests_property->setAccessible( true );

        $requests = $requests_property->getValue( $sync );

        foreach ( $product_ids as $product_id ) {
            $this->assertEquals( Sync::ACTION_DELETE, $requests["p-{$product_id}"] );
        }

        $this->assertEquals( count( $requests ), count( $product_ids ) );
    }


    /** @see test_delete_products() */
    public function provider_delete_products() {

        return [
            [ [] ],
            [ [ 1, 2, 3, 4 ] ],
        ];
	}


	/** @see Sync::schedule_sync() */
	public function test_schedule_sync() {

		$background  = facebook_for_woocommerce()->get_products_sync_background_handler();
		$sync        = $this->get_sync();
		$product_ids = [ 123, 456 ];

		$sync->create_or_update_products( $product_ids );

		$requests_property = new ReflectionProperty( Sync::class, 'requests' );
		$requests_property->setAccessible( true );

		$requests = $requests_property->getValue( $sync );
		$job      = $sync->schedule_sync();
		$bg_job   = $background->get_job( $job->id );

		$this->assertNotNull( $bg_job );
		$this->assertEquals( $requests, $bg_job->requests );
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the handler instance.
	 *
	 * @return Sync
	 */
	private function get_sync() {

		return new Sync();
	}


}

