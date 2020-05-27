<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Pixel\Events;

use SkyVerge\WooCommerce\Facebook\API\Pixel\Events\Request;
use SkyVerge\WooCommerce\Facebook\Events\Event;

/**
 * Tests the Pixel events API request class.
 */
class RequestTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		require_once 'includes/Events/Event.php';

		if ( ! class_exists( \SkyVerge\WooCommerce\Facebook\API\Request::class ) ) {
			require_once 'includes/API/Request.php';
		}

		if ( ! class_exists( Request::class ) ) {
			require_once 'includes/API/Pixel/Events/Request.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see Request::__construct() */
	public function test_constructor() {

		$event = new Event( [
			'event_name' => 'Test',
		] );

		$request = new Request( '1234', [ $event ] );

		$this->assertEquals( '/1234/events', $request->get_path() );
		$this->assertEquals( 'POST', $request->get_method() );
	}


	/** @see Request::get_data() */
	public function test_get_data() {

		$event = new Event( [
			'event_name' => 'Test',
		] );

		$request = new Request( '1234', [ $event ] );
		$data    = $request->get_data();

		$this->assertArrayHasKey( 'data', $data );
		$this->assertIsArray( $data['data'] );
		$this->assertNotEmpty( $data['data'] );
		$this->assertArrayHasKey( 'event_name', $data['data'][0] );
	}


}
