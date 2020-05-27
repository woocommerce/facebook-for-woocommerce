<?php

namespace SkyVerge\WooCommerce\Facebook\Events;

use IntegrationTester;

/**
 * Tests the Event class.
 */
class EventTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		parent::_before();

		if ( ! class_exists( Event::class ) ) {
			require_once 'includes/Events/Event.php';
		}
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Event::__construct() */
	public function test_constructor() {

		// TODO: implement
	}


	/** @see Event::prepare_data() */
	public function test_prepare_data() {

		// TODO: implement
	}


	/** @see Event::prepare_user_data() */
	public function test_prepare_user_data() {

		// TODO: implement
	}


	/** @see Event::generate_event_id() */
	public function test_generate_event_id() {

		// TODO: implement
	}


	/** @see Event::get_current_url() */
	public function test_get_current_url() {

		$method = IntegrationTester::getMethod( Event::class, 'get_current_url' );

		$this->assertNotEmpty( $method->invoke( new Event() ) );
	}


	/** @see Event::get_client_ip() */
	public function test_get_client_ip() {

		$method    = IntegrationTester::getMethod( Event::class, 'get_client_ip' );
		$client_ip = $method->invoke( new Event() );

		$this->assertNotEmpty( $client_ip );
		$this->assertNotEmpty( rest_is_ip_address( $client_ip ) );
	}


	/** @see Event::get_client_user_agent() */
	public function test_get_client_user_agent() {

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7) Gecko/20040803 Firefox/0.9.3';

		$method = IntegrationTester::getMethod( Event::class, 'get_client_user_agent' );

		$this->assertNotEmpty( $method->invoke( new Event() ) );
	}


	/** @see Event::get_click_id() */
	public function test_get_click_id_from_cookie() {

		$_COOKIE['_fbc'] = 'fb.1.1554763741205.AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

		$method = IntegrationTester::getMethod( Event::class, 'get_click_id' );

		$this->assertEquals( $_COOKIE['_fbc'], $method->invoke( new Event() ) );
	}


	/** @see Event::get_click_id() */
	public function test_get_click_id_from_query() {

		$_REQUEST['fbclid'] = 'AbCdEfGhIjKlMnOpQrStUvWxYz1234567890';

		$method = IntegrationTester::getMethod( Event::class, 'get_click_id' );

		$click_id = $method->invoke( new Event() );

		$this->assertStringContainsString( 'fb.1.', $click_id );
		$this->assertStringContainsString( '.AbCdEfGhIjKlMnOpQrStUvWxYz1234567890', $click_id );
	}


	/** @see Event::get_browser_id() */
	public function test_get_browser_id() {

		$_COOKIE['_fbp'] = 'fb.2.1577994917604.1910581703';

		$method = IntegrationTester::getMethod( Event::class, 'get_browser_id' );

		$this->assertEquals( $_COOKIE['_fbp'], $method->invoke( new Event() ) );
	}


	/** @see Event::get_data() */
	public function test_get_data() {

		$data  = [ 'test' => 'test' ];
		$event = new Event( $data );

		$this->assertEquals( $data, $event->get_data() );
	}


	/** @see Event::get_id() */
	public function test_get_id() {

		$data  = [ 'event_id' => 'test-id' ];
		$event = new Event( $data );

		$this->assertEquals( 'test-id', $event->get_id() );
	}


	/** @see Event::get_name() */
	public function test_get_name() {

		$data  = [ 'event_name' => 'test-name' ];
		$event = new Event( $data );

		$this->assertEquals( 'test-name', $event->get_name() );
	}


	/** @see Event::get_user_data() */
	public function test_get_user_data() {

		$user_data = [ 'user' => 'user' ];
		$data      = [ 'user_data' => $user_data ];
		$event     = new Event( $data );

		$this->assertEquals( $user_data, $event->get_user_data() );
	}


	/** @see Event::get_custom_data() */
	public function test_get_custom_data() {

		$custom_data = [ 'test' => 'test' ];
		$data        = [ 'custom_data' => $custom_data ];
		$event       = new Event( $data );

		$this->assertEquals( $custom_data, $event->get_custom_data() );
	}


}
