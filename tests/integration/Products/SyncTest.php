<?php

use SkyVerge\WooCommerce\Facebook\Products\Sync;

/**
 * Tests the Feed class.
 */
class SyncTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		require_once 'includes/Products/Sync.php';
	}


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


	/** @see Sync::delete_products() */
	public function test_delete_products() {
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

