<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Catalog\Send_Item_Updates;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Tests the API\Catalog\Send_Item_Updates\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	public function test_get_handles() {

		if ( ! class_exists( API\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Response.php';
		}

		if ( ! class_exists( API\Catalog\Send_Item_Updates\Response::class ) ) {
			require_once facebook_for_woocommerce()->get_plugin_path() . '/includes/API/Catalog/Send_Item_Updates/Response.php';
		}

		$handles  = [ '1', '2', '3' ];
		$raw_json = json_encode( [ 'handles' => $handles ] );
		$response = new API\Catalog\Send_Item_Updates\Response( $raw_json );

		$this->assertEquals( $handles, $response->get_handles() );
	}


}

