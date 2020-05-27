<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API;

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\API\Traits\Rate_Limited_API;

/**
 * Tests the API\Traits\Rate_Limited_API trait.
 */
class RateLimitedAPITest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	/** @var Rate_Limited_API */
	protected $api;


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		$this->api = facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Rate_Limited_API::set_rate_limit_delay()
	 *
	 * @param string $rate_limit_id rate limit ID
	 * @param int $value expected value
	 *
	 * @dataProvider provider_set_rate_limit_delay
	 */
	public function test_set_rate_limit_delay( $rate_limit_id, $value ) {

		$this->api->set_rate_limit_delay( $rate_limit_id, $value );

		$this->assertEquals( get_option( "wc_facebook_rate_limit_${rate_limit_id}" ), $value );
	}


	/** @see test_set_rate_limit_delay() */
	public function provider_set_rate_limit_delay() {

		return [
			[ 'ads_management_api_request', 15 ],
		];
	}


	/**
	 * @see Rate_Limited_API::get_rate_limit_delay()
	 *
	 * @param string $rate_limit_id rate limit ID
	 * @param string $option_value option value
	 * @param int $expected_value expected value
	 *
	 * @dataProvider provider_get_rate_limit_delay
	 */
	public function test_get_rate_limit_delay( $rate_limit_id, $option_value, $expected_value ) {

		update_option( "wc_facebook_rate_limit_${rate_limit_id}", $option_value );

		$this->assertEquals( $expected_value, $this->api->get_rate_limit_delay( $rate_limit_id ) );
	}


	/** @see test_get_rate_limit_delay() */
	public function provider_get_rate_limit_delay() {

		return [
			[ 'ads_management_api_request', 15, 15 ],
			[ 'ads_management_api_request', '15', 15 ],
			[ 'ads_management_api_request', '', 0 ],
			[ 'ads_management_api_request', false, 0 ],
		];
	}


	/**
	 * @see Rate_Limited_API::calculate_rate_limit_delay()
	 *
	 * @param array $headers response headers
	 * @param int $expected_value expected value
	 * @throws \ReflectionException
	 *
	 * @dataProvider provider_calculate_rate_limit_delay
	 */
	public function test_calculate_rate_limit_delay( $headers, $expected_value ) {

		$reflection = new \ReflectionClass( $this->api );
		$method     = $reflection->getMethod( 'calculate_rate_limit_delay' );

		$method->setAccessible( true );

		$this->assertEquals( $expected_value, $method->invokeArgs( $this->api, [ new API\Response( '' ), $headers ] ) );
	}


	/** @see test_calculate_rate_limit_delay() */
	public function provider_calculate_rate_limit_delay() {

		return [
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26, 'estimated_time_to_regain_access' => 15 ] ], 15 ],
		];
	}


}
