<?php

namespace SkyVerge\WooCommerce\Facebook\Events;

use Hoa\Stream\Test\Unit\IStream\In;
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


	/** @see Event::get_version_info() */
	public function test_get_version_info() {

		$version_info = [
			'source'        => 'woocommerce',
			'version'       => WC()->version,
			'pluginVersion' => facebook_for_woocommerce()->get_version(),
		];

		$this->assertEquals( $version_info, Event::get_version_info() );
	}


	/** @see Event::get_platform_identifier() */
	public function get_platform_identifier() {

		$wc_version     = WC()->version;
		$plugin_version = facebook_for_woocommerce()->get_version();

		$this->assertEquals( "woocommerce-{$wc_version}-{$plugin_version}", Event::get_platform_identifier() );
	}


	/** @see Event::__construct() */
	public function test_constructor() {

		$data = [];

		$event = new Event( $data );
		$data  = $event->get_data();

		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );
		$this->assertArrayHasKey( 'user_data', $data );
	}

	/** @see Event::__construct() */
	public function test_constructor_with_pii_data(){
		$data = array(
			"user_data" => array(
				"em" => 'homero@simpson.com',
				"fn" => "Homero",
				"ln" => "Simpson",
				"ph" => "(123) 456 7890",
				"ct" => "Springfield",
				"st" => "Ohio",
				"country" => "US",
				"zp" => "12345",
				"external_id" => "23"
			)
		);
		$event = new Event( $data );
		$data  = $event->get_data();

		$this->assertIsArray( $data );
		$this->assertNotEmpty( $data );
		$this->assertArrayHasKey( 'user_data', $data );
		$user_data = $data['user_data'];
		$this->assertArrayHasKey( 'em', $user_data );
		$this->assertArrayHasKey( 'fn', $user_data );
		$this->assertArrayHasKey( 'ln', $user_data );
		$this->assertArrayHasKey( 'ph', $user_data );
		$this->assertArrayHasKey( 'ct', $user_data );
		$this->assertArrayHasKey( 'st', $user_data );
		$this->assertArrayHasKey( 'country', $user_data );
		$this->assertArrayHasKey( 'zp', $user_data );
		$this->assertArrayHasKey( 'external_id', $user_data );
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
			'action_source'    => 'other',
			'event_time'       => '1234',
			'event_id'         => 'event-id',
			'event_source_url' => 'current-url',
			'custom_data'      => [],
			'custom_thing'     => 'Custom thing',
		];

		$event  = new Event( $data );
		$method = IntegrationTester::getMethod( Event::class, 'prepare_data' );
		$method->invoke( $event, $data );

		$data = $event->get_data();

		$this->assertSame( $expected, $data[ $property ] );
	}


	/** @see test_prepare_data */
	public function provider_prepare_data() {

		return [
		  'action source'     => [ 'action_source',    'other' ],
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
		$method = IntegrationTester::getMethod( Event::class, 'prepare_user_data' );
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

		$method  = IntegrationTester::getMethod( Event::class, 'generate_event_id' );
		$pattern = '/[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}/';

		$this->assertRegExp( $pattern, $method->invoke( new Event() ) );
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

		$data   = [ 'test' => 'test' ];
		$event  = new Event( $data );
		$actual = $event->get_data();

		$this->assertArrayHasKey( 'test', $actual );
		$this->assertEquals( 'test', $actual['test'] );
	}

	/** @see Event::get_data(), Event::prepare_data() */
	public function test_default_value_for_action_source() {

		$event  = new Event( [] );
		$actual = $event->get_data();

		$this->assertArrayHasKey( 'action_source', $actual );
		$this->assertEquals( 'website', $actual['action_source'] );
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
		$actual    = $event->get_user_data();

		$this->assertArrayHasKey( 'user', $actual );
		$this->assertEquals( 'user', $actual['user'] );
	}


	/** @see Event::get_custom_data() */
	public function test_get_custom_data() {

		$custom_data = [ 'test' => 'test' ];
		$data        = [ 'custom_data' => $custom_data ];
		$event       = new Event( $data );

		$this->assertEquals( $custom_data, $event->get_custom_data() );
	}

	/** @see Event::hash_pii_data() */
	public function test_hash_pii_data() {

		$pii_data = array(
			"em" => 'homero@simpson.com',
			"fn" => "Homero",
			"ln" => "Simpson",
			"ph" => "(123) 456 7890",
			"ct" => "Springfield",
			"st" => "Ohio",
			"country" => "US",
			"zp" => "12345",
			"external_id" => "23",
		);

		$method = IntegrationTester::getMethod( Event::class, 'hash_pii_data' );
		$hashed_data = $method->invoke( new Event(), $pii_data );
		$this->assertEquals(9, count($hashed_data));
		foreach( $hashed_data as $key => $value ){
			$this->assertRegExp('/^[A-Fa-f0-9]{64}$/', $value);
		}
	}

}
