<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Installation\Read;

use SkyVerge\WooCommerce\Facebook\API\FBE\Installation\Read\Response;

/**
 * Tests the API\FBE\Installation\Read\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	protected $data = '{"data":[{"business_manager_id":"1234","ad_account_id":"ad-account","pixel_id":"5678","profiles":["123"],"catalog_id":"456","pages":["123"]}]}';


	public function _before() {

		parent::_before();

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Response::class ) ) {
			require_once 'includes/API/Response.php';
		}

		if ( ! class_exists( Response::class ) ) {
			require_once 'includes/API/FBE/Installation/Read/Response.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see Response::get_pixel_id() */
	public function test_get_pixel_id() {

		$response = new Response( $this->data );

		$this->assertEquals( '5678', $response->get_pixel_id() );
	}


	/** @see Response::get_business_manager_id() */
	public function test_get_business_manager_id() {

		$response = new Response( $this->data );

		$this->assertEquals( '1234', $response->get_business_manager_id() );
	}


	/** @see Response::get_ad_account_id() */
	public function test_get_ad_account_id() {

		$response = new Response( $this->data );

		$this->assertEquals( 'ad-account', $response->get_ad_account_id() );
	}


	/** @see Response::get_page_id() */
	public function test_get_page_id() {

		$response = new Response( $this->data );

		$this->assertEquals( '123', $response->get_page_id() );
	}


	/** @see Response::get_catalog_id() */
	public function test_get_catalog_id() {

		$response = new Response( $this->data );

		$this->assertEquals( '456', $response->get_catalog_id() );
	}


	/** @see Response::get_data() */
	public function test_get_data() {

		$response = new Response( $this->data );

		$this->assertIsObject( $response->get_data() );
	}


}
