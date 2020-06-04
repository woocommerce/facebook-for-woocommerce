<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\User\Permissions\Delete;

use SkyVerge\WooCommerce\Facebook\API\User\Permissions\Delete\Request;

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
			require_once 'includes/API/User/Permissions/Delete/Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$request = new Request( '1234', 'some_permission' );

		$this->assertEquals( '/1234/permissions/some_permission', $request->get_path() );
		$this->assertEquals( 'DELETE', $request->get_method() );
	}


}
