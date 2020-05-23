<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Installation;

use SkyVerge\WooCommerce\Facebook\API\FBE\Installation\Request;

/**
 * Tests the API\Request class.
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
			require_once 'includes/API/FBE/Installation/Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Request::__construct()
	 */
	public function test_constructor() {

		$path   = 'path';
		$method = 'GET';

		$request = new Request( $path, $method );

		$this->assertEquals( "/fbe_business/{$path}", $request->get_path() );
		$this->assertEquals( $method, $request->get_method() );
	}


}
