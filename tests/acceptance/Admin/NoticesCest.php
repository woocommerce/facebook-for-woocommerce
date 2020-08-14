<?php

/**
 * Tests for standalone admin notices.
 */
class NoticesCest {


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Codeception\Exception\ModuleException
	 */
	public function _before( AcceptanceTester $I ) {

		// prevent API calls
		$I->haveTransientInDatabase( 'wc_facebook_connection_refresh', time() );
		$I->haveTransientInDatabase( 'wc_facebook_business_configuration_refresh', time() );

		$I->loginAsAdmin();
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\API::do_post_parse_response_validation()
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_invalid_connection_notice_with_token( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );
		$I->haveTransientInDatabase( 'wc_facebook_connection_invalid', time() );

		$I->amOnPluginsPage();

		$I->see( 'Your connection to Facebook is no longer valid', '.notice' );
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\API::do_post_parse_response_validation()
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_invalid_connection_notice_without_token( AcceptanceTester $I ) {

		$I->haveTransientInDatabase( 'wc_facebook_connection_invalid', time() );

		$I->amOnPluginsPage();

		$I->dontSee( 'Your connection to Facebook is no longer valid', '.notice' );
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\API::do_post_parse_response_validation()
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_invalid_connection_notice_valid_connection( AcceptanceTester $I ) {

		$I->dontHaveTransientInDatabase( 'wc_facebook_connection_invalid' );

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );

		$I->amOnPluginsPage();

		$I->dontSee( 'Your connection to Facebook is no longer valid', '.notice' );
	}


	/**
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_messenger_prompt_new_install( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );

		$I->amOnPluginsPage();

		$I->dontSee( 'Heads up! If you\'ve customized your Facebook Messenger color or greeting settings, please update those settings again', '.notice' );
	}


	/**
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_messenger_prompt_upgrade_messenger_disabled( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '12345' );

		$I->amOnPluginsPage();

		$I->dontSee( 'Heads up! If you\'ve customized your Facebook Messenger color or greeting settings, please update those settings again', '.notice' );
	}


	/**
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_messenger_prompt_upgrade_messenger_enabled_default( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '12345' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, 'yes' );

		$I->amOnPluginsPage();

		$I->dontSee( 'Heads up! If you\'ve customized your Facebook Messenger color or greeting settings, please update those settings again', '.notice' );
	}


	/**
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_messenger_prompt_upgrade_messenger_enabled_customized_color( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '12345' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, 'yes' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX, 'custom' );

		$I->amOnPluginsPage();

		$I->see( 'Heads up! If you\'ve customized your Facebook Messenger color or greeting settings, please update those settings again', '.notice' );
	}


	/**
	 * @see \WC_Facebookcommerce::add_admin_notices()
	 */
	public function try_messenger_prompt_upgrade_messenger_enabled_customized_greeting( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( \SkyVerge\WooCommerce\Facebook\Handlers\Connection::OPTION_ACCESS_TOKEN, '12345' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '12345' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER, 'yes' );
		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING, 'custom' );

		$I->amOnPluginsPage();

		$I->see( 'Heads up! If you\'ve customized your Facebook Messenger color or greeting settings, please update those settings again', '.notice' );
	}


}
