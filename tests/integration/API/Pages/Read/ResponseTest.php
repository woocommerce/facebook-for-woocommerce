<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Pages\Read;

use SkyVerge\WooCommerce\Facebook\API\Pages\Read\Response;

/**
 * Tests the API\Pages\Read\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		require_once 'includes/API/Response.php';
		require_once 'includes/API/Pages/Read/Response.php';
	}


	/** Test methods **************************************************************************************************/


	/** @see Response::get_name() */
	public function test_get_name() {

		$raw_response = json_encode( [ 'name' => 'Test Page', 'link' => 'https://example.org' ] );
		$request      = new Response( $raw_response );

		$this->assertEquals( 'Test Page', $request->get_name() );
	}


	/** @see Response::get_url() */
	public function test_get_url() {

		$raw_response = json_encode( [ 'name' => 'Test Page', 'link' => 'https://example.org' ] );
		$request      = new Response( $raw_response );

		$this->assertEquals( 'https://example.org', $request->get_url() );
	}


}
