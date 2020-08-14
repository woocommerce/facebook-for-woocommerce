<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API;

use SkyVerge\WooCommerce\Facebook\API\Response;
use SkyVerge\WooCommerce\Facebook\API\Traits\Rate_Limited_Response;

/**
 * Tests the API\Traits\Rate_Limited_Response trait.
 */
class RateLimitedResponseTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/**
	 * @see Rate_Limited_Response::get_rate_limit_usage()
	 *
	 * @param array $headers response headers
	 * @param int $value expected value
	 *
	 * @dataProvider provider_get_rate_limit_usage
	 */
	public function test_get_rate_limit_usage( $headers, $value ) {

		$response = new Response( '' );

		$this->assertEquals( $value, $response->get_rate_limit_usage( $headers ) );
	}


	/** @see test_get_rate_limit_usage() */
	public function provider_get_rate_limit_usage() {

		return [
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 28 ],
			[ [ 'x-business-use-case-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 28 ],
			[ [ 'X-App-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 28 ],
			[ [ 'x-app-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 28 ],
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ], 'X-App-Usage' => [ 'call_count' => 39, 'total_time' => 35, 'total_cputime' => 36 ] ], 28 ],
			[ [], 0 ],
		];
	}


	/**
	 * @see Rate_Limited_Response::get_rate_limit_total_time()
	 *
	 * @param array $headers response headers
	 * @param int $value expected value
	 *
	 * @dataProvider provider_get_rate_limit_total_time
	 */
	public function test_get_rate_limit_total_time( $headers, $value ) {

		$response = new Response( '' );

		$this->assertEquals( $value, $response->get_rate_limit_total_time( $headers ) );
	}


	/** @see test_get_rate_limit_total_time() */
	public function provider_get_rate_limit_total_time() {

		return [
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 25 ],
			[ [ 'x-business-use-case-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 25 ],
			[ [ 'X-App-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 25 ],
			[ [ 'x-app-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 25 ],
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ], 'X-App-Usage' => [ 'call_count' => 39, 'total_time' => 35, 'total_cputime' => 36 ] ], 25 ],
			[ [], 0 ],
		];
	}


	/**
	 * @see Rate_Limited_Response::get_rate_limit_total_cpu_time()
	 *
	 * @param array $headers response headers
	 * @param int $value expected value
	 *
	 * @dataProvider provider_get_rate_limit_total_cpu_time
	 */
	public function test_get_rate_limit_total_cpu_time( $headers, $value ) {

		$response = new Response( '' );

		$this->assertEquals( $value, $response->get_rate_limit_total_cpu_time( $headers ) );
	}


	/** @see test_get_rate_limit_total_cpu_time() */
	public function provider_get_rate_limit_total_cpu_time() {

		return [
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 26 ],
			[ [ 'x-business-use-case-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 26 ],
			[ [ 'X-App-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 26 ],
			[ [ 'x-app-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ] ], 26 ],
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26 ], 'X-App-Usage' => [ 'call_count' => 39, 'total_time' => 35, 'total_cputime' => 36 ] ], 26 ],
			[ [], 0 ],
		];
	}


	/**
	 * @see Rate_Limited_Response::get_rate_limit_estimated_time_to_regain_access()
	 *
	 * @param array $headers response headers
	 * @param int|null $value expected value
	 *
	 * @dataProvider provider_get_rate_limit_estimated_time_to_regain_access
	 */
	public function test_get_rate_limit_estimated_time_to_regain_access( $headers, $value ) {

		$response = new Response( '' );

		$this->assertEquals( $value, $response->get_rate_limit_estimated_time_to_regain_access( $headers ) );
	}


	/** @see test_get_rate_limit_estimated_time_to_regain_access() */
	public function provider_get_rate_limit_estimated_time_to_regain_access() {

		return [
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26, 'estimated_time_to_regain_access' => 15 ] ], 15 ],
			[ [ 'x-business-use-case-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26, 'estimated_time_to_regain_access' => 15 ] ], 15 ],
			[ [ 'X-App-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26, 'estimated_time_to_regain_access' => 15 ] ], 15 ],
			[ [ 'x-app-usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26, 'estimated_time_to_regain_access' => 15 ] ], 15 ],
			[ [ 'X-Business-Use-Case-Usage' => [ 'call_count' => 28, 'total_time' => 25, 'total_cputime' => 26, 'estimated_time_to_regain_access' => 15 ], 'X-App-Usage' => [ 'call_count' => 39, 'total_time' => 35, 'total_cputime' => 36, 'estimated_time_to_regain_access' => 20 ] ], 15 ],
			[ [], null ],
		];
	}


}
