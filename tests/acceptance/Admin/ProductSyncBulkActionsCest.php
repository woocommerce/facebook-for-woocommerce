<?php

class ProductSyncBulkActionsCest {


	// product objects created for the tests */
	/** @var \WC_Product */
	private $product;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		// save a generic product
		$this->product = $I->haveProductInDatabase();

		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the Include in Facebook sync enables sync for a standard product.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_standard( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->see( 'Disabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for a standard product' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action]', 'Include in Facebook sync' );
		$I->click( '#doaction' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Enabled', 'table.wp-list-table td' );
	}


	/**
	 * Test that the Include in Facebook sync enables sync when using the secondary dropdown at the bottom of the list table.
	 *
	 * @param AcceptanceTester $I tester instance
	 *
	 * @throws Exception
	 */
	public function try_include_bulk_action_secondary_dropdown( AcceptanceTester $I ) {

		// disable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->see( 'Disabled', 'table.wp-list-table td' );

		$I->wantTo( 'Test that the Include in Facebook sync enables sync for a standard product' );

		$I->click( "#cb-select-{$this->product->get_id()}" );
		$I->selectOption( '[name=action2]', 'Include in Facebook sync' );
		$I->click( '#doaction2' );
		$I->waitForElement( "#cb-select-{$this->product->get_id()}:not(:checked)" );

		$I->see( 'Enabled', 'table.wp-list-table td' );
	}


}
