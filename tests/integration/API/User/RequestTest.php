<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\User;

use SkyVerge\WooCommerce\Facebook\API\User\Request;

/**
 * Tests the API\Catalog\Send_Item_Updates\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Request::class ) ) {
			require_once 'includes/API/Request.php';
		}

		if ( ! class_exists( Request::class ) ) {
			require_once 'includes/API/User/Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor_default() {

		$request = new Request();

		$this->assertEquals( '/me', $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
	}


	/** @see Request::__construct() */
	public function test_constructor() {

		$request = new Request( '1234' );

		$this->assertEquals( '/1234', $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
	}


}
