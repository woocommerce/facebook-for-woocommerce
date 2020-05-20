<?php

use Codeception\Stub;
use SkyVerge\WooCommerce\Facebook\Products\Sync;
use SkyVerge\WooCommerce\Facebook\Products\Sync\Background;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

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


	/** @see Background::process_item() */
	public function test_process_item_update_request_with_simple_product() {

		$product_simple = new \WC_Product_Simple();
		$product_simple->save();

		// retailer_id and retailer_product_group_id should match for simple products
		$retailer_id = "wc_post_id_{$product_simple->get_id()}";

		$request = [
			'retailer_id' => $retailer_id,
			'data'        => [
				'retailer_product_group_id' => $retailer_id,
			],
		];

		$this->check_process_item_update_request( $product_simple, $request );
	}


	/**
	 * Tests that process_item() returns accurate data for an update sync request.
	 *
	 * It compares data entries explicitly set in the $request parameter only to allow testing scenarios for each product field separately.
	 *
	 * @see Background::process_item()
	 *
	 * @param \WC_Product $product product object
	 * @param array $request expect result
	 */
	private function check_process_item_update_request( $product, $request ) {

		$item = [ $product->get_id(), Sync::ACTION_UPDATE ];
		$job  = $this->get_test_job();

		$result = $this->get_background()->process_item( $item, $job );

		$this->assertIsArray( $result );
		$this->assertEquals( Sync::ACTION_UPDATE, $result['method'] );

		$data = $result['data'];

		// validate data type and allowed values for fields that are always included
		$this->assertIsArray( $data['additional_image_urls'] );
		$this->assertTrue( in_array( $data['availability'], [ 'in stock', 'out of stock' ], true ) );
		$this->assertIsString( $data['brand'] );
		$this->assertEquals( 'new', $data['condition'] );
		$this->assertIsString( $data['description'] );
		$this->assertIsString( $data['image_url'] );
		$this->assertIsString( $data['name'] );
		$this->assertIsInt( $data['price'] );
		$this->assertIsString( $data['product_type'] );
		$this->assertIsString( $data['retailer_id'] );
		$this->assertIsString( $data['retailer_product_group_id'] );
		$this->assertIsInt( $data['sale_price'] );
		$this->assertIsString( $data['url'] );
		$this->assertTrue( in_array( $data['visibility'], [ 'published', 'staging' ], true ) );

		// compare results with specific values from the test case
		if ( isset( $request['retailer_id'] ) ) {
			$this->assertEquals( $request['retailer_id'], $result['retailer_id'] );
		}

		if ( isset( $request['data'] ) ) {

			foreach ( $request['data'] as $key => $value ) {
				$this->assertEquals( $request['data'][ $key ], $result['data'][ $key ] );
			}
		}
	}


	/** @see Background::process_item() */
	public function test_process_item_update_request_with_product_variation() {

		$parent_product = new \WC_Product_Variable();
		$parent_product->save();

		$product_variation = new \WC_Product_Variation();
		$product_variation->save();

		$product_variation->set_parent_id( $parent_product->get_id() );
		$product_variation->save();

		$parent_product->set_children( [ $product_variation->get_id() ] );
		$parent_product->save();

		$request = [
			'retailer_id' => "wc_post_id_{$product_variation->get_id()}",
			'data'        => [
				'retailer_product_group_id' => "wc_post_id_{$parent_product->get_id()}"
			],
		];

		$this->check_process_item_update_request( $product_variation, $request );
	}


	/**
	 * Tests that the retailer ID field uses the product SKU if avaiable.
	 *
	 * @see Background::process_item()
	 *
	 * @param string $sku product SKU
	 * @param string $reatiler_id expected retailer ID
	 *
	 * @dataProvider provider_process_item_update_request_retailer_id_generation
	 */
	public function test_process_item_update_request_retailer_id_generation( $sku, $retailer_id ) {

		$product = new \WC_Product_Simple();
		$product->set_sku( $sku );
		$product->save();

		$retailer_id = sprintf( $retailer_id, $product->get_id() );

		$request = [
			'retailer_id' => $retailer_id,
			'data'        => [
				'retailer_product_group_id' => $retailer_id,
			],
		];

		$this->check_process_item_update_request( $product, $request );
	}


	/** @see test_process_item_update_request_retailer_id_generation() */
	public function provider_process_item_update_request_retailer_id_generation() {

		return [
			[ 'SKU-123', 'SKU-123_%d' ],
			[ '',        'wc_post_id_%d' ],
		];
	}


	/** @see Background::process_item() */
	public function test_process_item_delete_request() {

		$product = new \WC_Product_Simple();
		$product->save();

		$item = [ $product->get_id(), Sync::ACTION_DELETE ];

		$result = $this->get_background()->process_item( $item, null );

		$this->assertEquals( "wc_post_id_{$product->get_id()}", $result['retailer_id'] );
		$this->assertEquals( Sync::ACTION_DELETE, $result['method'] );
	}


	/**
	 * Tests that an exception is thrown if no product is found with the given product ID.
	 *
	 * @see Background::process_item()
	 *
	 * @dataProvider provider_process_item_exceptions
	 */
	public function test_process_item_exceptions( $product, $method ) {

		$this->expectException( Framework\SV_WC_Plugin_Exception::class );

		$item = [ $product->get_id(), $method ];

		$this->get_background()->process_item( $item, null );
	}


	/** @see test_process_item_exceptions() */
	public function provider_process_item_exceptions() {

		$valid_product = new \WC_Product_Simple();
		$valid_product->save();

		$product_without_id = new \WC_Product_Simple();

		$product_variation_without_parent = new \WC_Product_Variation();
		$product_variation_without_parent->save();

		return [
			[ $product_without_id,               Sync::ACTION_UPDATE ],
			[ $product_variation_without_parent, Sync::ACTION_UPDATE ],
			[ $valid_product,                    'INVALID' ],
		];
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets a background sync instance.
	 *
	 * @return Background
	 */
	private function get_background() {

		return new Background();
	}

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

