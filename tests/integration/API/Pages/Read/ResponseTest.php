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

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Response::class ) ) {
			require_once 'includes/API/Response.php';
		}

		if ( ! class_exists( Response::class ) ) {
			require_once 'includes/API/Pages/Read/Response.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Response::get_name()
	 *
	 * @param array $response_body response body
	 * @param string|null $page_name expected page name
	 *
	 * @dataProvider provider_get_name
	 */
	public function test_get_name( $response_body, $page_name ) {

		$response = new Response( json_encode( $response_body ) );

		$this->assertSame( $page_name, $response->get_name() );
	}


	/** @see test_get_name() */
	public function provider_get_name() {

		return [
			[ [ 'name' => 'Test Page', 'link' => 'https://example.org' ], 'Test Page' ],
			[ [ 'error' => [ 'type' => 'OAuthException', 'code' => 100 ] ], null ],
		];
	}


	/**
	 * @see Response::get_url()
	 *
	 * @param array $response_body response body
	 * @param string|null $page_url expected page url
	 *
	 * @dataProvider provider_get_url
	 */
	public function test_get_url( $response_body, $page_url ) {

		$response = new Response( json_encode( $response_body ) );

		$this->assertSame( $page_url, $response->get_url() );
	}


	/** @see test_get_url() */
	public function provider_get_url() {

		return [
			[ [ 'name' => 'Test Page', 'link' => 'https://example.org' ], 'https://example.org' ],
			[ [ 'error' => [ 'type' => 'OAuthException', 'code' => 100 ] ], null ],
		];
	}


}
