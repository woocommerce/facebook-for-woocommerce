<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Business_Manager;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Tests the API\Business_Manager\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/** @see API\Business_Manager\Response::get_name() */
	public function test_get_name() {

		$data = [
			'name' => 'Test',
			'link' => 'https://example.com',
		];

		$response = new API\Business_Manager\Response( json_encode( $data ) );

		$this->assertSame( 'Test', $response->get_name() );
	}


	/** @see API\Business_Manager\Response::get_url() */
	public function test_get_url() {

		$data = [
			'name' => 'Test',
			'link' => 'https://example.com',
		];

		$response = new API\Business_Manager\Response( json_encode( $data ) );

		$this->assertSame( 'https://example.com', $response->get_url() );
	}


}
