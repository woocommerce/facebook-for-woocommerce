<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Installation\Read;

use SkyVerge\WooCommerce\Facebook\API\FBE\Installation\Read\Request;

/**
 * Tests the API\Pages\Read\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Request::class ) ) {
			require_once 'includes/API/Request.php';
		}

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\FBE\Installation\Request::class ) ) {
			require_once 'includes/API/FBE/Installation/Request.php';
		}

		if ( ! class_exists( Request::class ) ) {
			require_once 'includes/API/FBE/Installation/Read/Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$request = new Request( '1234' );

		$this->assertStringContainsString( '/fbe_installs', $request->get_path() );
		$this->assertEquals( 'GET', $request->get_method() );
		$this->assertEquals( [ 'fbe_external_business_id' => '1234', ], $request->get_params() );
	}


}
