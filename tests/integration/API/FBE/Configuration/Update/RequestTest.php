<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Configuration\Update;

use SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Messenger;
use SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Update\Request;

/**
 * Tests the API\FBE\Configuration\Update\Request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Request::__construct()
	 */
	public function test_constructor() {

		$external_business_id = '1234';

		$request = new Request( $external_business_id );

		$this->assertEquals( '/fbe_business', $request->get_path() );
		$this->assertArrayHasKey( 'fbe_external_business_id', $request->get_data() );
		$this->assertEquals( $external_business_id, $request->get_data()['fbe_external_business_id'] );
		$this->assertEquals( 'POST', $request->get_method() );
	}


	/**
	 * @see Request::set_messenger_configuration()
	 */
	public function test_set_messenger_configuration() {

		$request = new Request( '1234' );

		$request->set_messenger_configuration( new Messenger( [
			'enabled' => true,
			'domains' => [
				'https://test.test/',
			],
		] ) );

		$data = $request->get_data();

		$this->assertArrayHasKey( 'messenger_chat', $data );

		$this->assertArrayHasKey( 'enabled', $data['messenger_chat'] );
		$this->assertTrue( $data['messenger_chat']['enabled'] );

		$this->assertArrayHasKey( 'domains', $data['messenger_chat'] );
		$this->assertSame( [ 'https://test.test/' ], $data['messenger_chat']['domains'] );
	}


}
