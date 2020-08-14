<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Configuration\Read;

use SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Read\Response;
use SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Messenger;

/**
 * Tests the API\FBE\Configuration\Read\Response class.
 */
class ResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	protected $data = '{"messenger_chat":{"enabled":true,"domains":["https://www.facebook.com/","https://wc-tests.test/"],"default_locale":"en_US"}}';


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/** @see Response::get_messenger_configuration() */
	public function test_get_messenger_configuration() {

		$response = new Response( $this->data );

		$this->assertInstanceOf( Messenger::class, $response->get_messenger_configuration() );
	}


	/** @see Response::get_messenger_configuration() */
	public function test_get_messenger_configuration_missing() {

		$response = new Response( '{}' );

		$this->assertNull( $response->get_messenger_configuration() );
	}


}
