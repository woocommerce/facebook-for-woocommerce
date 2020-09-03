<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\Exceptions;

use SkyVerge\WooCommerce\Facebook\API;

/**
 * Tests the API\Exceptions\Request_Limit_Reached_Test object.
 */
class Request_Limit_Reached_Test extends \Codeception\TestCase\WPTestCase {


	/**
	 * Runs before each test.
	 */
	public function _before() {

		require_once( 'includes/API/Exceptions/Request_Limit_Reached.php' );

		parent::_before();
	}



	/** @see API\Exceptions\Request_Limit_Reached::get_throttle_end() */
	public function test_get_throttle_end() {

		$exception = new API\Exceptions\Request_Limit_Reached( 'Help!', 500 );

		$reflection = new \ReflectionClass( $exception );
		$property = $reflection->getProperty( 'throttle_end' );
		$property->setAccessible( true) ;
		$property->setValue( $exception, new \DateTime() );

		$this->assertInstanceOf( \DateTime::class, $exception->get_throttle_end() );
	}



	/** @see API\Exceptions\Request_Limit_Reached::set_throttle_end() */
	public function test_set_throttle_end() {

		$exception = new API\Exceptions\Request_Limit_Reached( 'Help!', 500 );

		$exception->set_throttle_end( new \DateTime() );

		$this->assertInstanceOf( \DateTime::class, $exception->get_throttle_end() );
	}


}
