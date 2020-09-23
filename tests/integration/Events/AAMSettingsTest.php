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
			'enabledAutomaticMatchingFields' => ['em', 'fn', 'ln', 'ph', 'zp', 'ct', 'st', 'country'],
			'pixelId' => '123',
		);
		$aam_settings = new AAMSettings($data);
		$this->assertEquals($data['enableAutomaticMatching'], $aam_settings->get_enable_automatic_matching());
		$this->assertEquals($data['enabledAutomaticMatchingFields'], $aam_settings->get_enabled_automatic_matching_fields());
		$this->assertEquals($data['pixelId'], $aam_settings->get_pixel_id());
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
		$args = [
			'request_path'     => 'signals/config/json/'.$pixel_id,
			'response_body'    => [
				'errorMessage' => 'Not found'
			],
		];

		$this->prepare_request_response( $args );
		$this->assertNull(AAMSettings::build_from_pixel_id($pixel_id));
	}

	/** @see AAMSettings:build_from_pixel_id */
	public function test_not_null_settings_for_valid_pixel() {
		$pixel_id = '23';
		// Pixel id is not returned by the endpoint
		$data = [
			'enableAutomaticMatching' => true,
			'enabledAutomaticMatchingFields' => ['em', 'fn', 'ln', 'ph', 'zp', 'ct', 'st', 'country']
		];
		$args = [
			'request_path'     => 'signals/config/json/'.$pixel_id,
			'response_body'    => [
				'matchingConfig' => $data
			]
		];
		$this->prepare_request_response( $args );
		$aam_settings = AAMSettings::build_from_pixel_id($pixel_id);
		$this->assertNotNull($aam_settings);
		$this->assertEquals($data['enableAutomaticMatching'], $aam_settings->get_enable_automatic_matching());
		$this->assertEquals($data['enabledAutomaticMatchingFields'], $aam_settings->get_enabled_automatic_matching_fields());
		$this->assertEquals($pixel_id, $aam_settings->get_pixel_id());
	}

	/** @see AAMSettings:__toString */
	public function test_to_string() {
		$data = array(
			'enableAutomaticMatching' => true,
			'enabledAutomaticMatchingFields' => ['em', 'fn', 'ln', 'ph', 'zp', 'ct', 'st', 'country'],
			'pixelId' => '123'
		);
		$aam_settings = new AAMSettings($data);
		$expected_json = json_encode($data);
		$this->assertEquals($expected_json, strval($aam_settings));
	}

	/**
	 * Intercepts HTTP requests and returns a prepared response.
	 *
	 * @param array $args {
	 *     @type string $request_path a fragment of the URL that will be intercepted
	 *     @type array $response_headers HTTP headers for the response
	 *     @type array $response_body response data that will be JSON-encoded
	 *     @type int $response_code HTTP response code
	 *     @type string $response_message HTTP response message
	 * }
	 */
	private function prepare_request_response( $args ) {

		$args = wp_parse_args( $args, [
			'request_path'     => '',
			'response_headers' => [],
			'response_body'    => [],
			'response_code'    => 200,
			'response_message' => 'Ok'
		] );

		add_filter( 'pre_http_request', static function( $response, $parsed_args, $url ) use ( $args ) {

			if ( false !== strpos( $url, $args['request_path'] ) ) {

				$response = [
					'headers'       => $args['response_headers'],
					'body'          => json_encode( $args['response_body'] ),
					'response'      => [
						'code'    => $args['response_code'],
						'message' => $args['response_message'],
					],
					'cookies'       => [],
					'http_response' => null,
				];
			}

			return $response;
		}, 10, 3 );
	}

}
