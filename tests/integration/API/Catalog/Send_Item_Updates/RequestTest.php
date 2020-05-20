<?php

use SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Updates\Request;

/**
 * Tests the API\Catalog\Send_Item_Updates\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		require_once 'includes/API/Request.php';
		require_once 'includes/API/Catalog/Send_Item_Updates/Request.php';
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$request = new Request( '1234' );

		$this->assertEquals( '/1234/batch', $request->get_path() );
		$this->assertEquals( 'POST', $request->get_method() );
	}


	/** @see Request::set_requests() */
	public function test_set_requests() {

		$request = new Request( '1234 ');

		$request->set_requests( [] );

		// TODO: assert that the array of requests is included in get_data()
	}


	/** @see Request::set_allow_upsert() */
	public function test_set_allow_upsert() {

		$request = new Request( '1234 ');

		$request->set_allow_upsert( false );

		// TODO: assert that the allow upsert is included in get_data()
	}


}
