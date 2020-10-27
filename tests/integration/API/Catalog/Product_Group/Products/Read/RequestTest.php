<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Catalog\Product_Group\Products\Read;

use SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Group\Products\Read\Request;

/**
 * Tests the API\Catalog\Product_Group\Products\Read\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		parent::_before();

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Request::class ) ) {
			require_once 'includes/API/Request.php';
		}

		if ( ! class_exists( Request::class ) ) {
			require_once 'includes/API/Catalog/Product_Group/Products/Read/Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$product_group_id = '165835951532406';
		$limit            = 100;

		$expected_params = [
			'fields' => 'id,retailer_id',
			'limit'  => 100,
		];

		$request = new Request( $product_group_id, $limit );

		$this->assertInstanceOf( Request::class, $request );
		$this->assertEquals( "/165835951532406/products", $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
		$this->assertEquals( $expected_params, $request->get_params() );
	}


	/** @see Request::get_rate_limit_id() */
	public function test_get_rate_limit_id() {

		$this->assertEquals( 'ads_management', Request::get_rate_limit_id() );
	}


}
