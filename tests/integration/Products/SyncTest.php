<?php

use SkyVerge\WooCommerce\Facebook\Products\Sync;

/**
 * Tests the Sync class.
 */
class SyncTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


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
        require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/Products/Sync.php';

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

