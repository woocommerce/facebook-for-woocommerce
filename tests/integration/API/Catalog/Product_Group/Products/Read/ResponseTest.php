<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Catalog\Product_Group\Products\Read;

use SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Group\Products\Read\Response;

/**
 * Tests the API\Catalog\Product_Group\Products\Read\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		parent::_before();

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Response::class ) ) {
			require_once 'includes/API/Response.php';
		}

		if ( ! trait_exists( \SkyVerge\WooCommerce\Facebook\API\Traits\Paginated_Response::class, false ) ) {
			require_once 'includes/API/Traits/Paginated_Response.php';
		}

		if ( ! class_exists( Response::class ) ) {
			require_once 'includes/API/Catalog/Product_Group/Products/Read/Response.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Response::get_ids()
	 *
	 * @param array $response_data test response data
	 * @param array $product_item_ids expected return value
	 *
	 * @dataProvider provider_get_ids()
	 */
	public function test_get_ids( $response_data, $product_item_ids ) {

		$response = new Response( json_encode( $response_data ) );

		$this->assertEquals( $product_item_ids, $response->get_ids() );
	}


	/** @see test_get_ids() */
	public function provider_get_ids() {

		$response_data = [
			'data' => [
				[
					'id' => '4567',
					'retailer_id' => 'wc_post_id_1234',
				],
			],
		];

		$product_item_ids = [
			'wc_post_id_1234' => '4567',
		];

		return [
			[ $response_data, $product_item_ids ],
			[ [], [] ],
		];
	}


}
