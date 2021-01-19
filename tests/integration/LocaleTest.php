<?php

use SkyVerge\WooCommerce\Facebook\Locale;

/**
 * Tests the Admin class.
 */
class LocaleTest extends \Codeception\TestCase\WPTestCase {


	/** @see Locale::get_supported_locales() */
	public function test_get_supported_locales() {

		$locales = Locale::get_supported_locales();

		$this->assertIsArray( $locales );
		$this->assertArrayHasKey( 'en_US', $locales );
	}


	/** @see Locale::get_supported_locales() */
	public function test_get_supported_locales_filter() {

		add_filter( 'wc_facebook_messenger_supported_locales', static function( $locales ) {

			$locales['custom'] = 'Custom';

			return $locales;

		} );

		$this->assertArrayHasKey( 'custom', Locale::get_supported_locales() );
	}


	/** @see Locale::is_supported_locale() */
	public function test_is_supported_locale() {

		$this->assertTrue( Locale::is_supported_locale( 'en_US' ) );
		$this->assertFalse( Locale::is_supported_locale( 'invalid_Locale' ) );
	}


}
