<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

/**
 * Tests the Connection class.
 */
class ConnectionTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	public function _before() {

		parent::_before();
	}


	/** Test methods **************************************************************************************************/


	/** @see Connection::__construct() */
	public function test_constructor() {

        $connection = $this->get_connection();

        $this->assertInstanceOf( Connection::class, $connection );
	}


	/** @see Connection::handle_connect() */
	public function test_handle_connect() {

		$this->assertNull( $this->get_connection()->handle_connect() );
	}


	/** @see Connection::get_access_token() */
	public function test_get_access_token() {

		$access_token = 'access token';

		$this->get_connection()->update_access_token( $access_token );

		$this->assertSame( $access_token, $this->get_connection()->get_access_token() );
	}


	/** @see Connection::get_merchant_access_token() */
	public function test_get_merchant_access_token() {

		$access_token = 'access token';

		$this->get_connection()->update_merchant_access_token( $access_token );

		$this->assertSame( $access_token, $this->get_connection()->get_merchant_access_token() );
	}


	/** @see Connection::get_access_token() */
	public function test_get_access_token_filter() {

		add_filter( 'wc_facebook_connection_access_token', function() {

			return 'filtered';
		} );

		$this->assertSame( 'filtered', $this->get_connection()->get_access_token() );
	}


	/** @see Connection::get_connect_url() */
	public function test_get_connect_url() {

		$connection     = $this->get_connection();
		$connection_url = $connection->get_connect_url();

		$this->assertIsString( $connection_url );
		$this->assertStringContainsString( Connection::OAUTH_URL, $connection_url );
		$this->assertEquals( add_query_arg( rawurlencode_deep( $connection->get_connect_parameters() ), Connection::OAUTH_URL ), $connection_url );
	}


	/** @see Connection::get_disconnect_url() */
	public function test_get_disconnect_url() {

		$this->assertIsString( $this->get_connection()->get_disconnect_url() );
	}


	/**
	 * @see Connection::get_scopes()
	 *
	 * @param string $scope an API scope that should be included
	 *
	 * @dataProvider provider_get_scopes
	 */
	public function test_get_scopes( $scope ) {

		$scopes = $this->get_connection()->get_scopes();

		$this->assertContains( $scope, $scopes );
	}


	/** @see test_get_scopes() */
	public function provider_get_scopes() {

		return [
			'manage_business_extension' => [ 'manage_business_extension' ],
			'catalog_management'        => [ 'catalog_management' ],
			'business_management'       => [ 'business_management' ],
		];
	}


	/** @see Connection::get_scopes() */
	public function test_get_scopes_filter() {

		add_filter( 'wc_facebook_connection_scopes', function() {

			return [ 'filtered' ];
		} );

		$this->assertSame( [ 'filtered' ], $this->get_connection()->get_scopes() );
	}


	/** @see Connection::get_external_business_id() */
	public function test_get_external_business_id() {

		update_option( Connection::OPTION_EXTERNAL_BUSINESS_ID, 'external business id' );

		$this->assertSame( 'external business id', $this->get_connection()->get_external_business_id() );
	}


	/** @see Connection::get_external_business_id() */
	public function test_get_external_business_id_generation() {

		// force the generation of a new ID
		delete_option( Connection::OPTION_EXTERNAL_BUSINESS_ID );

		$connection           = $this->get_connection();
		$external_business_id = $connection->get_external_business_id();

		$this->assertNotEmpty( $external_business_id );
		$this->assertIsString( $external_business_id );

		$this->assertEquals( $external_business_id, $connection->get_external_business_id() );
		$this->assertEquals( $external_business_id, get_option( Connection::OPTION_EXTERNAL_BUSINESS_ID ) );
	}


	/** @see Connection::get_external_business_id() */
	public function test_get_external_business_id_filter() {

		add_filter( 'wc_facebook_external_business_id', function() {

			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->get_connection()->get_external_business_id() );
	}


	/**
	 * @see Connection::get_business_name()
	 *
	 * @dataProvider provider_get_business_name
	 *
	 * @param string $option_value the option value to set
	 */
	public function test_get_business_name( $option_value ) {

		update_option( 'blogname', $option_value );

		$this->assertSame( $option_value, $this->get_connection()->get_business_name() );
	}


	/** @see test_get_business_name() */
	public function provider_get_business_name() {

		return [
			[ 'Test Store' ],
			[ 'Tèst Store' ],
			[ "Test's Store" ],
			[ 'Test "Store"' ],
			[ 'Test & Store' ]
		];
	}


	/** @see Connection::get_business_name() */
	public function test_get_business_name_filtered() {

		$option_value = 'Test Store';

		update_option( 'blogname', $option_value );

		add_filter( 'wc_facebook_connection_business_name', function() {
			return 'Filtered Test Store';
		} );

		$this->assertSame( 'Filtered Test Store', $this->get_connection()->get_business_name() );
	}


	/** @see Connection::get_business_manager_id() */
	public function test_get_business_manager_id() {

		$business_manager_id = 'business manager id';

		$this->get_connection()->update_business_manager_id( $business_manager_id );

		$this->assertSame( $business_manager_id, $this->get_connection()->get_business_manager_id() );
	}


	/** @see Connection::get_ad_account_id() */
	public function test_get_ad_account_id() {

		$ad_account_id = 'ad account id';

		$this->get_connection()->update_ad_account_id( $ad_account_id );

		$this->assertSame( $ad_account_id, $this->get_connection()->get_ad_account_id() );
	}


	/** @see Connection::get_system_user_id() */
	public function test_get_system_user_id() {

		$system_user_id = 'system user id';

		$this->get_connection()->update_system_user_id( $system_user_id );

		$this->assertSame( $system_user_id, $this->get_connection()->get_system_user_id() );
	}


	/** @see Connection::get_proxy_url() */
	public function test_get_proxy_url() {

		$proxy_url = $this->get_connection()->get_proxy_url();

		$this->assertIsString( $proxy_url );
		$this->assertEquals( Connection::PROXY_URL, $proxy_url );
	}


	/** @see Connection::get_proxy_url() */
	public function test_get_proxy_url_filter() {

		add_filter( 'wc_facebook_connection_proxy_url', static function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->get_connection()->get_proxy_url() );
	}


	/** @see Connection::get_redirect_url() */
	public function test_get_redirect_url() {

		$redirect_url = $this->get_connection()->get_redirect_url();

		$this->assertIsString( $redirect_url );
		$this->assertStringContainsString( home_url(), $redirect_url );
		$this->assertStringContainsString( 'wc-api=' . Connection::ACTION_CONNECT, $redirect_url );
		$this->assertStringContainsString( 'nonce=', $redirect_url );
	}


	/** @see Connection::get_redirect_url() */
	public function test_get_redirect_url_filter() {

		add_filter( 'wc_facebook_connection_redirect_url', static function() {
			return 'filtered';
		} );

		$this->assertEquals( 'filtered', $this->get_connection()->get_redirect_url() );
	}


	/** @see Connection::get_connect_parameters() */
	public function test_get_connect_parameters() {

		$connection            = $this->get_connection();
		$connection_parameters = $connection->get_connect_parameters();

		$this->assertIsArray( $connection_parameters );
		$this->assertArrayHasKey( 'client_id', $connection_parameters );
		$this->assertArrayHasKey( 'redirect_uri', $connection_parameters );
		$this->assertArrayHasKey( 'state', $connection_parameters );
		$this->assertArrayHasKey( 'display', $connection_parameters );
		$this->assertArrayHasKey( 'response_type', $connection_parameters );
		$this->assertArrayHasKey( 'scope', $connection_parameters );
		$this->assertArrayHasKey( 'extras', $connection_parameters );

		$this->assertEquals( Connection::CLIENT_ID, $connection_parameters['client_id'] );
		$this->assertEquals( Connection::PROXY_URL, $connection_parameters['redirect_uri'] );
		$this->assertEquals( $connection->get_redirect_url(), $connection_parameters['state'] );
		$this->assertEquals( 'page', $connection_parameters['display'] );
		$this->assertEquals( 'code', $connection_parameters['response_type'] );
		$this->assertEquals( implode( ',', $connection->get_scopes() ), $connection_parameters['scope'] );
		$this->assertJson( $connection_parameters['extras'] );
	}


	/** @see Connection::get_connect_parameters_extras() */
	public function test_get_connect_parameters_extras() {

		$connection = $this->get_connection();

		$method = IntegrationTester::getMethod( Connection::class, 'get_connect_parameters_extras' );
		$extras = $method->invoke( $connection );

		$this->assertIsArray( $extras );
		$this->assertArrayHasKey( 'setup', $extras );
		$this->assertArrayHasKey( 'business_config', $extras );
		$this->assertArrayHasKey( 'repeat', $extras );

		$this->assertIsArray( $extras['setup'] );
		$this->assertIsArray( $extras['business_config'] );
		$this->assertFalse( $extras['repeat'] );

		$this->assertArrayHasKey( 'external_business_id', $extras['setup'] );
		$this->assertArrayHasKey( 'timezone', $extras['setup'] );
		$this->assertArrayHasKey( 'currency', $extras['setup'] );
		$this->assertArrayHasKey( 'business_vertical', $extras['setup'] );

		// merchant settings ID shouldn't be present by default
		$this->assertArrayNotHasKey( 'merchant_settings_id', $extras['setup'] );

		$this->assertEquals( $connection->get_external_business_id(), $extras['setup']['external_business_id'] );
		$this->assertEquals( wc_timezone_string(), $extras['setup']['timezone'] );
		$this->assertEquals( get_woocommerce_currency(), $extras['setup']['currency'] );
		$this->assertEquals( 'ECOMMERCE', $extras['setup']['business_vertical'] );

		$this->assertArrayHasKey( 'business', $extras['business_config'] );

		$this->assertEquals( $connection->get_business_name(), $extras['business_config']['business']['name'] );
	}


	/** @see Connection::get_connect_parameters_extras() */
	public function test_get_connect_parameters_extras_migrating() {

		facebook_for_woocommerce()->get_integration()->update_external_merchant_settings_id( '1234' );

		$connection = $this->get_connection();

		$method = IntegrationTester::getMethod( Connection::class, 'get_connect_parameters_extras' );
		$extras = $method->invoke( $connection );

		$this->assertArrayHasKey( 'merchant_settings_id', $extras['setup'] );
		$this->assertSame( '1234', $extras['setup']['merchant_settings_id'] );
	}


	/** @see Connection::update_business_manager_id() */
	public function test_update_business_manager_id() {

		$business_manager_id = 'business manager id';

		$this->get_connection()->update_business_manager_id( $business_manager_id );

		$this->assertSame( $business_manager_id, $this->get_connection()->get_business_manager_id() );
	}


	/** @see Connection::update_ad_account_id() */
	public function test_update_ad_account_id() {

		$ad_account_id = 'ad account id';

		$this->get_connection()->update_ad_account_id( $ad_account_id );

		$this->assertSame( $ad_account_id, $this->get_connection()->get_ad_account_id() );
	}


	/** @see Connection::update_system_user_id() */
	public function test_update_system_user_id() {

		$system_user_id = 'system user id';

		$this->get_connection()->update_system_user_id( $system_user_id );

		$this->assertSame( $system_user_id, $this->get_connection()->get_system_user_id() );
	}


	/** @see Connection::update_access_token() */
	public function test_update_access_token() {

		$access_token = 'access token';

		$this->get_connection()->update_access_token( $access_token );

		$this->assertSame( $access_token, $this->get_connection()->get_access_token() );
	}


	/** @see Connection::update_merchant_access_token() */
	public function test_update_merchant_access_token() {

		$access_token = 'access token';

		$this->get_connection()->update_merchant_access_token( $access_token );

		$this->assertSame( $access_token, $this->get_connection()->get_merchant_access_token() );
	}


	/** @see Connection::is_connected() */
	public function test_is_not_connected_without_access_token() {

		$this->assertFalse( $this->get_connection()->is_connected() );
	}


	/** @see Connection::is_connected() */
	public function test_is_connected() {

		$connection = $this->get_connection();

		$connection->update_access_token( 'access token' );

		$this->assertTrue( $connection->is_connected() );
	}


	/**
	 * @see Connection::has_previously_connected_fbe_2()
	 *
	 * @dataProvider provider_has_previously_connected_fbe_2
	 *
	 * @param string|null $option_value option value to set, or null to not set one
	 * @param bool $expected_value expected result
	 */
	public function test_has_previously_connected_fbe_2( $option_value, $expected_value ) {

		if ( $option_value ) {
			update_option( 'wc_facebook_has_connected_fbe_2', $option_value );
		}

		$this->assertSame( $expected_value, $this->get_connection()->has_previously_connected_fbe_2() );
	}


	/** @see test_has_previously_connected_fbe_2 */
	public function provider_has_previously_connected_fbe_2() {

		return [
			'not set'                  => [ null, false ],
			'not previously connected' => [ 'no', false ],
			'previously connected'     => [ 'yes', true ],
		];
	}


	/**
	 * @see Connection::has_previously_connected_fbe_1()
	 *
	 * @dataProvider provider_has_previously_connected_fbe_1
	 *
	 * @param string|null $option_value option value to set, or null to not set one
	 * @param bool $expected_value expected result
	 */
	public function test_has_previously_connected_fbe_1( $option_value, $expected_value ) {

		facebook_for_woocommerce()->get_integration()->update_external_merchant_settings_id( $option_value );

		$this->assertSame( $expected_value, $this->get_connection()->has_previously_connected_fbe_1() );
	}


	/** @see test_has_previously_connected_fbe_1 */
	public function provider_has_previously_connected_fbe_1() {

		return [
			'not previously connected' => [ '', false ],
			'previously connected'     => [ '1234', true ],
		];
	}


	/** Helper methods **************************************************************************************************/


	/**
	 * Gets the connection instance.
	 *
	 * @return Connection
	 */
	private function get_connection() {

		return new Connection( facebook_for_woocommerce() );
	}


}
