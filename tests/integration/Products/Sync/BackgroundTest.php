<?php

use Codeception\Stub;
use SkyVerge\WooCommerce\Facebook\Products\Sync;
use SkyVerge\WooCommerce\Facebook\Products\Sync\Background;

/**
 * Tests the Sync\Background class.
 */
class BackgroundTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	/**
	 * @see Background::process_job()
	 *
	 * @dataProvider provider_process_job_calls_process_items
	 */
	public function test_process_job_calls_process_items( $requests ) {

		$background = Stub::make( Background::class, [
			'process_items' => \Codeception\Stub\Expected::exactly( empty( $requests ) ? 0 : 1 ),
		], $this );

		$background->process_job( $this->get_test_job( [ 'requests' => $requests ] ) );
	}


	/** @see test_process_job_calls_process_items() */
	public function provider_process_job_calls_process_items() {

		return [
			[ [] ],
			[ [
				Sync::PRODUCT_INDEX_PREFIX . '1' => Sync::ACTION_UPDATE
			] ],
			[ [
				Sync::PRODUCT_INDEX_PREFIX . '1' => Sync::ACTION_UPDATE,
				Sync::PRODUCT_INDEX_PREFIX . '2' => Sync::ACTION_UPDATE,
				Sync::PRODUCT_INDEX_PREFIX . '3' => Sync::ACTION_DELETE,
			] ],
		];
	}


	/** @see Background::process_items() */
	public function test_process_items() {

		$requests = [
			Sync::PRODUCT_INDEX_PREFIX . '1' => Sync::ACTION_UPDATE,
			Sync::PRODUCT_INDEX_PREFIX . '2' => Sync::ACTION_UPDATE,
			Sync::PRODUCT_INDEX_PREFIX . '3' => Sync::ACTION_DELETE,
		];

		$job = $this->get_test_job( [ 'requests' => $requests ] );

		$background = Stub::make( Background::class, [
			'start_time'   => time(),
			'process_item' => function( $item, $job ) {

				// assert the $item has two elements
				$this->assertEquals( 2, count( $item ) );

				// assert the first position is one of the product IDs
				$this->assertContains( $item[0], [ 1, 2, 3 ] );

				// assert the second position is one of the accepted sync methods
				$this->assertContains( $item[1], [ Sync::ACTION_UPDATE, Sync::ACTION_DELETE ] );
			},
		] );

		$background->process_items( $job, $requests );
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets a test job object.
	 *
	 * @return object
	 */
	private function get_test_job( array $props = [] ) {

		$defaults = [
			'id'       => uniqid(),
			'status'   => 'queued',
			'requests' => [],
			'progress' => 0,
		];

		return (object) array_merge( $defaults, $props );
	}


}

