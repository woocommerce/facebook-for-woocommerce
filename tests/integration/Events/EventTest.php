<?php

namespace SkyVerge\WooCommerce\Facebook\Events;

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

		$event = new Event();

		$reflection = new \ReflectionClass( $event );
		$method     = $reflection->getMethod( 'get_current_url' );

		$method->setAccessible( true );

		$this->assertNotEmpty( $method->invoke( $event ) );
	}


	/** @see Event::get_client_ip() */
	public function test_get_client_ip() {

		// TODO: implement
	}


	/** @see Event::get_client_user_agent() */
	public function test_get_client_user_agent() {

		// TODO: implement
	}


	/** @see Event::get_click_id() */
	public function test_get_click_id() {

		// TODO: implement
	}


	/** @see Event::get_browser_id() */
	public function test_get_browser_id() {

		// TODO: implement
	}


	/** @see Event::get_data() */
	public function test_get_data() {

		// TODO: implement
	}


	/** @see Event::get_id() */
	public function test_get_id() {

		// TODO: implement
	}


	/** @see Event::get_name() */
	public function test_get_name() {

		// TODO: implement
	}


	/** @see Event::get_user_data() */
	public function test_get_user_data() {

		// TODO: implement
	}


	/** @see Event::get_custom_data() */
	public function test_get_custom_data() {

		// TODO: implement
	}


}
