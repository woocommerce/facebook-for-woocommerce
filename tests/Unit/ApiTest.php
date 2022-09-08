<?php
declare( strict_types=1 );

use WooCommerce\Facebook\Api;
use WooCommerce\Facebook\Framework\Api\Exception as ApiException;

/**
 * Api unit test clas.
 */
class ApiTest extends WP_UnitTestCase {

	/**
	 * @var Api
	 */
	private $api;

	/**
	 * Runs before each test is executed.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->api = new Api( 'test-api-key-9678djyad552' );
	}

	/**
	 * Tests preform request succeeds.
	 *
	 * @return void
	 * @throws ApiException In case of failed request.
	 */
	public function test_perform_request_performs_successful_request() {
		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v13.0/726635365295186?fields=name', $url );
			return [
				'body'     => '{"name":"WooCommerce Catalog","id":"726635365295186"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$response = $this->api->get_catalog( '726635365295186' );

		$this->assertEquals( '726635365295186', $response->id );
		$this->assertEquals( 'WooCommerce Catalog', $response->name );
	}

	/**
	 * Tests preform request method returns WP_Error response.
	 *
	 * @return void
	 * @throws ApiException In case of failed request.
	 */
	public function test_perform_request_produces_wp_error() {
		$this->expectException( ApiException::class );
		$this->expectExceptionCode( 007 );
		$this->expectExceptionMessage( 'WP Error Message' );

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v13.0/726635365295186?fields=name', $url );
			return new WP_Error( 007, 'WP Error Message' );
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$this->api->get_catalog( '726635365295186' );
	}

	/**
	 * Tests get installation ids returns installation ids.
	 *
	 * @return void
	 * @throws ApiException In case of failed request.
	 */
	public function test_get_installation_ids_returns_installation_ids() {
		$external_business_id = 'wordpress-facebook-62c3f1add134a';

		$response = function( $result, $parsed_args, $url ) use ( $external_business_id ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v13.0/fbe_business/fbe_installs?fbe_external_business_id=' . $external_business_id, $url );
			return [
				'body'     => '{"data":[{"business_manager_id":"973766133343161","commerce_merchant_settings_id":"1118975075354165","onsite_eligible":false,"pixel_id":"1964583793745557","profiles":["101332922643063"],"ad_account_id":"0","catalog_id":"726635365295186","pages":["101332922643063"],"token_type":"User","installed_features":[{"feature_instance_id":"762898101511280","feature_type":"pixel","connected_assets":{"page_id":"101332922643063","pixel_id":"1964583793745557"},"additional_info":{"onsite_eligible":false}},{"feature_instance_id":"562415265365817","feature_type":"catalog","connected_assets":{"catalog_id":"726635365295186","page_id":"101332922643063","pixel_id":"1964583793745557"},"additional_info":{"onsite_eligible":false}},{"feature_instance_id":"554457382898066","feature_type":"fb_shop","connected_assets":{"catalog_id":"726635365295186","commerce_merchant_settings_id":"1118975075354165","page_id":"101332922643063"},"additional_info":{"onsite_eligible":false}}]}]}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$response = $this->api->get_installation_ids( $external_business_id );

		$this->assertEquals( '101332922643063', $response->get_page_id() );
	}

	/**
	 * Tests get catalog method returns Facebook Catalog name and id.
	 *
	 * @return void
	 * @throws ApiException In case of failed request.
	 */
	public function test_get_catalog_returns_catalog_id_and_name() {
		$catalog_id = '726635365295186';

		$response = function( $result, $parsed_args, $url ) use ( $catalog_id ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( "https://graph.facebook.com/v13.0/{$catalog_id}?fields=name", $url );
			return [
				'body'     => '{"name":"WooCommerce Catalog","id":"726635365295186"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$response = $this->api->get_catalog( $catalog_id );

		$this->assertEquals( '726635365295186', $response->id );
		$this->assertEquals( 'WooCommerce Catalog', $response->name );
	}

	/**
	 * Tests get user returns information about user.
	 *
	 * @return void
	 * @throws ApiException In case of failed request.
	 */
	public function test_get_user_returns_user_information() {
		$user_id = '';

		$response = function( $result, $parsed_args, $url ) {
			$this->assertEquals( 'GET', $parsed_args['method'] );
			$this->assertEquals( 'https://graph.facebook.com/v13.0/me', $url );
			return [
				'body'     => '{"name":"WooCommerce Integration System User","id":"111189594891749"}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$response = $this->api->get_user( $user_id );

		$this->assertEquals( '111189594891749', $response->id );
		$this->assertEquals( 'WooCommerce Integration System User', $response->name );
	}

	/**
	 * Tests delete user permission performs a request to delete user permission.
	 *
	 * @return void
	 */
	public function test_delete_user_permission_deletes_user_permission() {
		$user_id    = '111189594891749';
		$permission = 'manage_business_extension';

		$response = function( $result, $parsed_args, $url ) use ( $user_id, $permission ) {
			$this->assertEquals( 'DELETE', $parsed_args['method'] );
			$this->assertEquals( "https://graph.facebook.com/v13.0/{$user_id}/permissions/{$permission}", $url );
			return [
				'body'     => '{"success":true}',
				'response' => [
					'code'    => 200,
					'message' => 'OK',
				],
			];
		};
		add_filter( 'pre_http_request', $response, 10, 3 );

		$response = $this->api->delete_user_permission( $user_id, $permission );

		$this->assertTrue( $response->success );
	}
}
