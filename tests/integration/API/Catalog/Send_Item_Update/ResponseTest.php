<?php

use Codeception\Stub;
use SkyVerge\WooCommerce\Facebook\API\Catalog\Send_Item_Update\Response;

/**
 * Tests the API\Catalog\Send_Item_Update\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/** Test methods **************************************************************************************************/


	public function test_get_handles() {

		$handles  = [ '1', '2', '3' ];
		$raw_json = json_encode( [ 'handles' => $handles ] );
		$response = new Response( $raw_json );

		$this->assertEquals( $handles, $response->get_handles() );
	}


}

