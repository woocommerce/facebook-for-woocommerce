<?php

use SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item\Find\Request;

/**
 * Tests the Find Product Item Request class.
 */
class Request_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/API/Request.php';
		require_once 'includes/API/Catalog/Product_Item/Find/Request.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see \SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item\Find\Request::__construct */
	public function test_constructor() {

		$catalog_id  = '165835951532406';
		$retailer_id = base64_decode( 'd3BfcG9zdF8xMDQx' );

		$request = new Request( $catalog_id, $retailer_id );

		$this->assertInstanceOf( Request::class, $request );
		$this->assertEquals( 'catalog:165835951532406:d3BfcG9zdF8xMDQx', $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
	}


	/** @see \SkyVerge\WooCommerce\Facebook\API\Catalog\Product_Item\Find\Request::get_rate_limit_id() */
	public function test_get_rate_limit_id() {

		$this->assertEquals( 'wc_facebook_ads_management_api_request', Request::get_rate_limit_id() );
	}


}
