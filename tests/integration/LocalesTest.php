<?php

use SkyVerge\WooCommerce\Facebook\Locales;

/**
 * Tests the Admin class.
 */
class LocalesTest extends \Codeception\TestCase\WPTestCase {


	/** @see Locales::get_supported_locales() */
	public function test_get_supported_locales() {

		$locales = Locales::get_supported_locales();

		$this->assertIsArray( $locales );
		$this->assertArrayHasKey( 'en_US', $locales );
	}


	/** @see Locales::get_supported_locales() */
	public function test_get_supported_locales_filter() {

		add_filter( 'wc_facebook_messenger_supported_locales', static function( $locales ) {

			$locales['custom'] = 'Custom';

			return $locales;

		} );

		$this->assertArrayHasKey( 'custom', Locales::get_supported_locales() );
	}


	/** @see Locales::is_supported_locale() */
	public function test_is_supported_locale() {

		$this->assertTrue( Locales::is_supported_locale( 'en_US' ) );
		$this->assertFalse( Locales::is_supported_locale( 'invalid_Locale' ) );
	}


}
