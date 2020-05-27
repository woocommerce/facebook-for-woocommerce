<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Catalog;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Tests the API\Catalog\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		if ( ! class_exists( API\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Response.php';
		}

		if ( ! class_exists( API\Catalog\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Catalog/Response.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see API\Catalog\Response::get_name() */
	public function test_get_name() {

		$data = [
			'name' => 'Test',
		];

		$response = new API\Catalog\Response( json_encode( $data ) );

		$this->assertSame( 'Test', $response->get_name() );
	}


}
