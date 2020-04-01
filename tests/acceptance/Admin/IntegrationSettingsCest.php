<?php

class IntegrationSettingsCest {


	/** @var string prefix for the field selectors */
	const FIELD_PREFIX = '#woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_';


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PAGE_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		$I->haveFacebookForWooCommerceSettingsInDatabase( [
			\WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID => '1234',
		] );

		// always log in
		$I->loginAsAdmin();
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
	 * Test that the Product sync section is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_product_sync_section_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Product sync section is present' );

		$I->see( 'Product sync', 'h3.wc-settings-sub-title' );
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

		$I->see( 'Manage connection', 'a.button' );

		// $I->see( 'Facebook page', 'th.titledesc' );
		// // TODO: mock fbgraph calls to get_page_name and get_page_url and verify the page link {DM 2020-01-30}

		$I->see( 'Pixel', 'th.titledesc' );

		$I->see( 'Use Advanced Matching', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_ENABLE_ADVANCED_MATCHING );

		$I->see( 'Create ad', 'a.button' );
	}


	/**
	 * Test that the Product sync fields are present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_product_sync_fields_present( AcceptanceTester $I ) {

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Product sync fields are present' );

		//$I->see( 'Sync products', 'a.button' );

		$I->see( 'Enable product sync', 'th.titledesc' );
		$I->seeElement( 'input[type=checkbox]' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC );

		$I->see( 'Exclude categories from sync', 'th.titledesc' );
		$I->seeElement( 'select.wc-enhanced-select' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS );

		$I->see( 'Exclude tags from sync', 'th.titledesc' );
		$I->seeElement( 'select.wc-enhanced-select' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS );

		$I->see( 'Product description sync', 'th.titledesc' );
		$I->seeElement( 'select' . self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE );

		//$I->see( 'Force daily resync at', 'th.titledesc' );
		//$I->seeElement( 'input[type=checkbox]' . self::FIELD_PREFIX . 'scheduled_resync_enabled' );
		//$I->seeElement( 'input[type=number]' . self::FIELD_PREFIX . 'scheduled_resync_hours' );
		//$I->seeElement( 'input[type=number]' . self::FIELD_PREFIX . 'scheduled_resync_minutes' );
		//$I->seeElement( 'select' . self::FIELD_PREFIX . 'scheduled_resync_meridiem' );
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
	 * Test that the Product sync fields are saved correctly.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws Exception
	 */
	public function try_product_sync_fields_saved( AcceptanceTester $I ) {

		// save a product category and a product tag to exclude from facebook sync
		list( $excluded_category_id, $excluded_category_taxonomy_id ) = $I->haveTermInDatabase( 'Excluded Category', 'product_cat' );
		list( $excluded_tag_id, $excluded_tag_taxonomy_id )           = $I->haveTermInDatabase( 'Excluded Tag', 'product_tag' );

		$I->amOnIntegrationSettingsPage();

		$I->wantTo( 'Test that the Product sync fields are saved correctly' );

		// select excluded categories/tags because submitForm can't set hidden elements
		$I->selectOption( self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, $excluded_category_taxonomy_id );
		$I->selectOption( self::FIELD_PREFIX . WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS, $excluded_tag_taxonomy_id );

		$form = [
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_ENABLE_PRODUCT_SYNC                  => true,
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS . '[]' => [ (string) $excluded_category_taxonomy_id ],
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS . '[]'      => [ (string) $excluded_tag_taxonomy_id ],
			'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_' . WC_Facebookcommerce_Integration::SETTING_PRODUCT_DESCRIPTION_MODE             => WC_Facebookcommerce_Integration::PRODUCT_DESCRIPTION_MODE_SHORT,

			//'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_scheduled_resync_enabled'  => true,
			//'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_scheduled_resync_hours'    => '10',
			//'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_scheduled_resync_minutes'  => '30',
			//'woocommerce_' . WC_Facebookcommerce::INTEGRATION_ID . '_scheduled_resync_meridiem' => 'pm',
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
