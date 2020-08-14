<?php

class MessengerCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I
	 * @throws \Codeception\Exception\ModuleException
	 */
	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '1234' );

		$I->loginAsAdmin();
	}


	/**
	 * Test that the Messenger fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_messenger_fields_present( AcceptanceTester $I ) {

		$this->add_get_configuration_success_response( $I );

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=messenger' );

		$I->wantTo( 'Test that the Messenger fields are present' );

		$I->see( 'Enable Messenger', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER );

		$I->see( 'Language', 'th.titledesc' );
		$I->see( 'English (United States)', 'td.forminp-messenger_locale p' );

		$I->see( 'Greeting & Colors', 'th.titledesc' );
		$I->see( 'Click here to manage your Messenger greeting and colors.', 'td.forminp-messenger_greeting p' );
	}


	/**
	 * Test that the Messenger fields are saved.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_messenger_fields_save( AcceptanceTester $I ) {

		$this->add_get_configuration_success_response( $I );

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=messenger' );

		$I->wantTo( 'Test that the Messenger fields are saved' );

		$this->add_update_configuration_success_response( $I );
		$this->add_get_configuration_success_response( $I, false );

		$I->uncheckOption( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER );
		$I->click( '#save_messenger_settings' );
		$I->waitForText( 'Your settings have been saved.' );
		$I->dontSeeCheckboxIsChecked( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER );

		$this->add_update_configuration_failure_response( $I );

		$I->checkOption( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER );
		$I->click( '#save_messenger_settings' );
		$I->waitForText( 'Your settings could not be saved.' );
		$I->dontSeeCheckboxIsChecked( 'input[type=checkbox]#' . \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER );
	}


	/**
	 * Test that the Messenger fields are hidden when the API request fails.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_messenger_fields_hidden_on_failure( AcceptanceTester $I ) {

		$I->amOnAdminPage('admin.php?page=wc-facebook&tab=messenger' );

		$I->wantTo( 'Test that the Messenger fields are present' );

		$I->dontSee( 'Enable Messenger', 'th.titledesc' );
		$I->see( 'There was an error communicating with the Facebook Business Extension.' );
	}


	/**
	 * Adds a MU plugin to filter the FBE API response and simulate a successful GET of the configuration.
	 *
	 * @param AcceptanceTester $I
	 * @param bool $enabled whether the Messenger should return as enabled or not
	 */
	private function add_get_configuration_success_response(  AcceptanceTester $I, $enabled = true ) {

		$is_enabled = $enabled ? 'true' : 'false';

		// simulate a successful configuration response
		$code = <<<PHP
add_filter( 'pre_http_request', function( \$response, \$args, \$url ) {

	if ( false !== strpos( \$url, 'fbe_business' ) && 'GET' === \$args['method'] ) {

		\$response = [
			'headers'       => [],
			'body'          => json_encode(
				[ 'messenger_chat' => [
					'enabled' => $is_enabled,
					'domains' => [
						'https://facebook.com/',
						'https://wc-tests.test/',
					],
					'default_locale' => 'en_US',
				] ] ),
			'response'      => [
				'code'    => 200,
				'message' => 'Ok',
			],
			'cookies'       => [],
			'http_response' => null,
		];
	}

	return \$response;

}, 10, 3 );
PHP;

		$I->haveMuPlugin( 'get-configuration-response-filter.php', $code );
	}


	/**
	 * Adds a MU plugin to filter the FBE API response and simulate a successful POST of the configuration.
	 *
	 * @param AcceptanceTester $I
	 */
	private function add_update_configuration_success_response(  AcceptanceTester $I ) {

		// simulate a successful configuration response
		$code = <<<PHP
add_filter( 'pre_http_request', function( \$response, \$args, \$url ) {

	if ( false !== strpos( \$url, 'fbe_business' ) && 'POST' === \$args['method'] ) {

		\$response = [
			'headers'       => [],
			'body'          => json_encode(
				[ 'success' => true ] ),
			'response'      => [
				'code'    => 200,
				'message' => 'Ok',
			],
			'cookies'       => [],
			'http_response' => null,
		];
	}

	return \$response;

}, 10, 3 );
PHP;

		$I->haveMuPlugin( 'update-configuration-response-filter.php', $code );
	}


	/**
	 * Adds a MU plugin to filter the FBE API response and simulate a failed POST of the configuration.
	 *
	 * @param AcceptanceTester $I
	 */
	private function add_update_configuration_failure_response(  AcceptanceTester $I ) {

		// simulate a successful configuration response
		$code = <<<PHP
add_filter( 'pre_http_request', function( \$response, \$args, \$url ) {

	if ( false !== strpos( \$url, 'fbe_business' ) && 'POST' === \$args['method'] ) {

		\$response = [
			'headers'       => [],
			'body'          => json_encode( [ 'error' => 'Sorry', 'code' => 33 ] ),
			'response'      => [
				'code'    => 400,
				'message' => 'Bad Request',
			],
			'cookies'       => [],
			'http_response' => null,
		];
	}

	return \$response;

}, 10, 3 );
PHP;

		$I->haveMuPlugin( 'update-configuration-response-filter.php', $code );
	}


}
