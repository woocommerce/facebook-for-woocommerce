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

		if ( ! class_exists( API\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Response.php';
		}

		if ( ! class_exists( API\Business_Manager\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Business_Manager/Response.php';
		}
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


	/** @see API\Catalog\Response::get_url() */
	public function test_get_url() {

		$data = [
			'name' => 'Test',
			'link' => 'https://example.com',
		];

		$response = new API\Business_Manager\Response( json_encode( $data ) );

		$this->assertSame( 'https://example.com', $response->get_url() );
	}


}
