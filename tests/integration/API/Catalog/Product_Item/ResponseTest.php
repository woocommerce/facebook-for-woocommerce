<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Catalog\Product_Item;

use SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item\Response;

/**
 * Tests the API\Catalog\Product_Item\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		if ( ! class_exists( Response::class ) ) {
			require_once 'includes/API/Catalog/Product_Item/Response.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Response::get_group_id()
	 *
	 * @param string $raw_response JSON encoded response
	 * @param string $product_group_id expected product group ID
	 *
	 * @dataProvider provider_get_group_id()
	 */
	public function test_get_group_id( $raw_response, $product_group_id ) {

		$response = new Response( $raw_response );

		$this->assertEquals( $product_group_id, $response->get_group_id() );
	}


	public function provider_get_group_id() {

		return [
			[ json_encode( [ 'product_group' => [ 'id' => '1234' ] ] ), '1234' ],
			[ json_encode( [ 'id' => '1234' ] ),                        '' ],
		];
	}


}
