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
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see Event::__construct() */
	public function test_constructor() {

		$data = [];

		$event = new Event( $data );
		$data  = $event->get_data();

		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );
		$this->assertArrayHasKey( 'user_data', $data );
	}


	/**
	 * @see Event::prepare_data()
	 *
	 * @dataProvider provider_prepare_data
	 *
	 * @param string $property property to test
	 * @param string|array $expected expected value
	 * @throws \ReflectionException
	 */
	public function test_prepare_data( $property, $expected ) {

		$data = [
			'event_time'       => '1234',
			'event_id'         => 'event-id',
			'event_source_url' => 'current-url',
			'custom_data'      => [],
			'custom_thing'     => 'Custom thing',
		];

		$event  = new Event( $data );
		$method = new \ReflectionMethod( Event::class, 'prepare_data' );
		$method->setAccessible( true );
		$method->invoke( $event, $data );

		$data = $event->get_data();

		$this->assertSame( $expected, $data[ $property ] );
	}


	/** @see test_prepare_data */
	public function provider_prepare_data() {

		return [
			'event time'        => [ 'event_time',       '1234' ],
			'event id'          => [ 'event_id',         'event-id' ],
			'event source url'  => [ 'event_source_url', 'current-url' ],
			'custom property'   => [ 'custom_thing',     'Custom thing' ],
			'event custom data' => [ 'custom_data',      [] ],
		];
	}


	/**
	 * @see Event::prepare_user_data()
	 *
	 * @dataProvider provider_prepare_user_data
	 *
	 * @param string $property property to test
	 * @param string|array $expected expected value
	 * @throws \ReflectionException
	 */
	public function test_prepare_user_data( $property, $expected ) {

		$data = [
			'client_ip_address' => '123.123.1234',
			'client_user_agent' => '007',
			'click_id'          => 'Clicky',
			'browser_id'        => 'Netscape Navigator',
			'custom_thing'      => 'Custom thing',
		];

		$event  = new Event( $data );
		$method = new \ReflectionMethod( Event::class, 'prepare_user_data' );
		$method->setAccessible( true );
		$method->invoke( $event, $data );

		$data = $event->get_data();
		$data = $data['user_data'];

		$this->assertSame( $expected, $data[ $property ] );
	}


	/** @see test_prepare_user_data */
	public function provider_prepare_user_data() {

		return [
			'client ip address' => [ 'client_ip_address', '123.123.1234' ],
			'client user agent' => [ 'client_user_agent', '007' ],
			'click id'          => [ 'click_id',          'Clicky' ],
			'browser id'        => [ 'browser_id',        'Netscape Navigator' ],
			'custom property'   => [ 'custom_thing',      'Custom thing' ],
		];
	}


	/** @see Event::generate_event_id() */
	public function test_generate_event_id() {

		// TODO: implement
	}


	/** @see Event::get_current_url() */
	public function test_get_current_url() {

		// TODO: implement
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
