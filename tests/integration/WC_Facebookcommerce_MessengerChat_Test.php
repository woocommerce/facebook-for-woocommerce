<?php

/**
 * Tests the messenger chat class.
 */
class WC_Facebookcommerce_MessengerChat_Test extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {


	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	/** @see \WC_Facebookcommerce_MessengerChat::get_supported_locales() */
	public function test_get_supported_locales() {

		$locales = \WC_Facebookcommerce_MessengerChat::get_supported_locales();

		$this->assertIsArray( $locales );
		$this->assertArrayHasKey( 'en_US', $locales );
	}


	/** @see \WC_Facebookcommerce_MessengerChat::get_supported_locales() */
	public function test_get_supported_locales_filter() {

		add_filter( 'wc_facebook_messenger_supported_locales', static function( $locales ) {

			$locales['custom'] = 'Custom';

			return $locales;

		} );

		$this->assertArrayHasKey( 'custom', \WC_Facebookcommerce_MessengerChat::get_supported_locales() );
	}


}

