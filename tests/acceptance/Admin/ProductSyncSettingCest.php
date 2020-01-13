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


}
