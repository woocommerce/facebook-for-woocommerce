<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API;

use SkyVerge\WooCommerce\Facebook\API\Response;

/**
 * Tests the API\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		require_once 'includes/API/Response.php';
	}


	/** Test methods **************************************************************************************************/


	/** @see Response::get_id() */
	public function test_get_id() {

		$raw_response = json_encode( [ 'id' => '1234' ] );
		$response     = new Response( $raw_response );

		$this->assertEquals( '1234', $response->get_id() );
	}


}
