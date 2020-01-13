<?php

class ProductSyncSettingCest {


	/** @var \WC_Product|null product object created for the test */
	private $sync_enabled_product;
	/** @var \WC_Product|null product object created for the test */
	private $sync_disabled_product;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function _before( AcceptanceTester $I ) {

		// save two generic products
		$this->sync_enabled_product  = $I->haveProductInDatabase();
		$this->sync_disabled_product = $I->haveProductInDatabase();

		// enable/disable sync for the products
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->sync_enabled_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->sync_disabled_product ] );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the field is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_field_present( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the field is present' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->see( 'Include in Facebook sync', '.form-field' );
	}


	/**
	 * Test that the field has the correct default value for sync enabled products.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_field_default_enabled( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the field has the correct default value for sync enabled products' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->seeCheckboxIsChecked( '#fb_sync_enabled' );
	}


	/**
	 * Test that the field has the correct default value for sync disabled products.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_field_default_disabled( AcceptanceTester $I ) {

		$I->amEditingPostWithId( $this->sync_disabled_product->get_id() );

		$I->wantTo( 'Test that the field has the correct default value for sync disabled products' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->dontSeeCheckboxIsChecked( '#fb_sync_enabled' );
	}


	/**
	 * Test that the field value is saved correctly when enabling sync.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_field_enable( AcceptanceTester $I ) {

		/**
		 * Set these in the database so that the product processing hooks are properly set
		 * @see WC_Facebookcommerce_Integration::__construct
		 */
		$plugin_settings = [
			'fb_api_key'            => 'fake-key',
			'fb_product_catalog_id' => '1111',
		];
		$I->haveOptionInDatabase( 'woocommerce_facebookcommerce_settings', $plugin_settings );

		$I->amEditingPostWithId( $this->sync_disabled_product->get_id() );

		$I->wantTo( 'Test that the field value is saved correctly when enabling sync' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );
		// remove WP admin bar to fix "Element is not clickable" issue
		$I->executeJS( 'jQuery("#wpadminbar").remove();' );
		$I->checkOption( '#fb_sync_enabled' );
		$I->click( 'Update' );
		$I->waitForText( 'Product updated' );
		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->seeCheckboxIsChecked( '#fb_sync_enabled' );
	}


	/**
	 * Test that the field value is saved correctly when disabling sync.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_field_disable( AcceptanceTester $I ) {

		/**
		 * Set these in the database so that the product processing hooks are properly set
		 * @see WC_Facebookcommerce_Integration::__construct
		 */
		$plugin_settings = [
			'fb_api_key'            => 'fake-key',
			'fb_product_catalog_id' => '1111',
		];
		$I->haveOptionInDatabase( 'woocommerce_facebookcommerce_settings', $plugin_settings );

		$I->amEditingPostWithId( $this->sync_enabled_product->get_id() );

		$I->wantTo( 'Test that the field value is saved correctly when disabling sync' );

		$I->click( 'Facebook', '.fb_commerce_tab_options' );
		// remove WP admin bar to fix "Element is not clickable" issue
		$I->executeJS( 'jQuery("#wpadminbar").remove();' );
		$I->uncheckOption( '#fb_sync_enabled' );
		$I->click( 'Update' );
		$I->waitForText( 'Product updated' );
		$I->click( 'Facebook', '.fb_commerce_tab_options' );

		$I->dontSeeCheckboxIsChecked( '#fb_sync_enabled' );
	}


}
