<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class IntegrationSettingsCest {


	/** @var string prefix for the field selectors */
	const FIELD_PREFIX = '#woocommerce_facebookcommerce_';


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		$I->haveFacebookForWooCommerceSettingsInDatabase( [
			\WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID => '1234',
		] );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the Get Started button is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_get_started_button_present( AcceptanceTester $I ) {

		$I->wantTo( 'Test that the Get Started button is present' );

		$I->dontHaveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN );
		$I->dontHaveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID );

		$I->amOnIntegrationSettingsPage();

		$I->seeConnectButton( 'Get Started', 'a#cta_button' );
	}


	/**
	 * Test that the Connection section is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_connection_section_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Connection section is present' );

		$I->see( 'Connection', 'h3.wc-settings-sub-title' );
	}


	/**
	 * Test that the Messenger section is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_messenger_section_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Messenger sync section is present' );

		$I->see( 'Messenger', 'h3.wc-settings-sub-title' );
	}


	/**
	 * Test that the Connection fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_connection_fields_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Connection fields are present' );

		$I->seeConnectButton( 'Manage connection', 'a#woocommerce-facebook-settings-manage-connection' );

		// $I->see( 'Facebook page', 'th.titledesc' );
		// // TODO: mock fbgraph calls to get_page_name and get_page_url and verify the page link {DM 2020-01-30}

		$I->see( 'Pixel', 'th.titledesc' );

		$I->see( 'Use Advanced Matching', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING );

		$I->see( 'Create ad', 'a.button' );
	}


	/**
	 * Test that the Messenger fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_messenger_fields_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Messenger fields are present' );

		$I->see( 'Enable Messenger', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER );

		$I->see( 'Language', 'th.titledesc' );
		$I->seeElement( 'select' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE );

		$I->see( 'Greeting', 'th.titledesc' );
		$I->seeElement( 'textarea' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING );

		$I->see( 'Colors', 'th.titledesc' );
		$I->seeElement( 'input[type=text].colorpick.messenger-field' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX );
	}


	/**
	 * Test that the Debug fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_debug_fields_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Debug fields are present' );

		$I->see( 'Enable debug mode', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE );
	}


	/**
	 * Test that the Connection fields are saved correctly.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_connection_fields_saved( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Connection fields are saved correctly' );

		$form = [
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING => true,
		];

		$I->submitForm( '#mainform', $form, 'save' );
		$I->waitForText( 'Your settings have been saved.' );

		$I->seeInFormFields( '#mainform', $form );
	}


	/**
	 * Test that the Messenger fields are saved correctly.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_messenger_fields_saved( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Messenger fields are saved correctly' );

		$form = [
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_ENABLE_MESSENGER    => true,
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_MESSENGER_LOCALE    =>'ja_JP',
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_MESSENGER_GREETING  => 'Hello darkness my old friend',
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_MESSENGER_COLOR_HEX => '#000000',
		];
		$I->submitForm( '#mainform', $form, 'save' );
		$I->waitForText( 'Your settings have been saved.' );

		$I->seeInFormFields( '#mainform', $form );
	}


	/**
	 * Test that the Debug fields are saved correctly.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_debug_fields_saved( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Debug fields are saved correctly' );

		$form = [
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_ENABLE_DEBUG_MODE => true,
		];

		$I->submitForm( '#mainform', $form, 'save' );
		$I->waitForText( 'Your settings have been saved.' );

		$I->seeInFormFields( '#mainform', $form );
	}


}
