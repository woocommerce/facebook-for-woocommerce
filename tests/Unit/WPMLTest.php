<?php

class WPMLTest extends WP_UnitTestCase {

	/**
	 * Tears down the fixture, for example, close a network connection.
	 *
	 * This method is called after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		WC_Facebook_WPML_Injector::$settings     = null;
		WC_Facebook_WPML_Injector::$default_lang = null;
		remove_all_filters( 'wpml_post_language_details' );
	}

	public function test_should_hide_product_when_wpml_filter_not_applied() {
		$this->assertTrue( WC_Facebook_WPML_Injector::should_hide( 1 ) );
	}

	public function test_product_hidden_when_wpml_filter_returns_wp_error() {
		add_filter(
			'wpml_post_language_details',
			function() {
				return new WP_Error();
			}
		);

		$this->assertTrue( WC_Facebook_WPML_Injector::should_hide( 1 ) );
	}

	public function test_product_hidden_no_settings_and_not_default() {
		WC_Facebook_WPML_Injector::$default_lang = 'en_US';

		add_filter(
			'wpml_post_language_details',
			function() {
				return [
					'language_code' => 'fr_FR',
				];
			}
		);

		$this->assertTrue( WC_Facebook_WPML_Injector::should_hide( 1 ) );
	}

	public function test_product_not_hidden_no_settings_and_default() {
		WC_Facebook_WPML_Injector::$default_lang = 'en_US';
		add_filter(
			'wpml_post_language_details',
			function() {
				return [
					'language_code' => 'en_US',
				];
			}
		);

		$this->assertFalse( WC_Facebook_WPML_Injector::should_hide( 1 ) );
	}

	public function test_product_hidden_language_setting_not_visible() {
		WC_Facebook_WPML_Injector::$settings = [
			'fr_FR' => FB_WPML_Language_Status::HIDDEN,
		];

		add_filter(
			'wpml_post_language_details',
			function() {
				return [
					'language_code' => 'fr_FR',
				];
			}
		);

		$this->assertTrue( WC_Facebook_WPML_Injector::should_hide( 1 ) );
	}

	public function test_product_not_hidden_language_setting_visible() {
		WC_Facebook_WPML_Injector::$settings = [
			'fr_FR' => FB_WPML_Language_Status::VISIBLE,
		];

		add_filter(
			'wpml_post_language_details',
			function() {
				return [
					'language_code' => 'fr_FR',
				];
			}
		);

		$this->assertFalse( WC_Facebook_WPML_Injector::should_hide( 1 ) );
	}
}
