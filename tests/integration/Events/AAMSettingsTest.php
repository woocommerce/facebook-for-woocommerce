<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\Events;

use SkyVerge\WooCommerce\Facebook\Events\AAMSettings;

/**
 * Tests the AAMSettings class
 */
class AAMSettingsTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		if ( ! class_exists( AAMSettings::class ) ) {
			require_once 'includes/Events/AAMSettings.php';
		}
	}


	/** Test methods **************************************************************************************************/


	/** @see AAMSettings:__construct() */
	public function test_constructor() {
		$data = array(
			'enableAutomaticMatching' => true,
			'enabledAutomaticMatchingFields' => ['em', 'fn', 'ln', 'ph', 'zp', 'ct', 'st', 'country']
		);
		$aam_settings = new AAMSettings($data);
		$this->assertEquals($data['enableAutomaticMatching'], $aam_settings->get_enable_automatic_matching());
		$this->assertEquals($data['enabledAutomaticMatchingFields'], $aam_settings->get_enabled_automatic_matching_fields());
	}


	/** @see AAMSettings:get_url */
	public function test_url_generation() {
		$pixel_id = '23';
		$aam_settings_url = 'https://connect.facebook.net/signals/config/json/23';
		$this->assertEquals($aam_settings_url, AAMSettings::get_url($pixel_id));
	}

	/** @see AAMSettings:build_from_pixel_id */
	public function test_null_settings_for_invalid_pixel() {
		$pixel_id = '23';
		$this->assertNull(AAMSettings::build_from_pixel_id($pixel_id));
	}

	/** @see AAMSettings:__toString */
	public function test_to_string() {
		$data = array(
			'enableAutomaticMatching' => true,
			'enabledAutomaticMatchingFields' => ['em', 'fn', 'ln', 'ph', 'zp', 'ct', 'st', 'country']
		);
		$aam_settings = new AAMSettings($data);
		$expected_json = json_encode($data);
		$this->assertEquals($expected_json, strval($aam_settings));
	}

}
