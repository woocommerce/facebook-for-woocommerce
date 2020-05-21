<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class ProductSyncColumnCest {


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

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the column is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_column_present( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the column is present' );

		$I->see( 'FB Sync Enabled', 'table.wp-list-table th' );
	}


	/**
	 * Test that the column is not present.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @param \Codeception\Example $example test data
	 *
	 * @dataProvider provider_column_not_present
	 */
	public function try_column_not_present( AcceptanceTester $I, \Codeception\Example $example ) {

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, $example['access_token'] );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, $example['catalog_id'] );

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the column is not present if the plugin is not connected or ready' );

		$I->dontSee( 'FB Sync Enabled', 'table.wp-list-table th' );
	}


	/** @see try_column_not_present() */
	protected function provider_column_not_present() {

		return [
			[ 'access_token' => '',     'catalog_id' => '' ],
			[ 'access_token' => '1234', 'catalog_id' => '' ],
			[ 'access_token' => '',     'catalog_id' => '1234'],
		];
	}


	/**
	 * Test that the column shows the correct value for a product that has sync enabled.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_column_displays_sync_enabled( AcceptanceTester $I ) {

		// enable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the column displays the correct value for a sync-enabled product' );

		$this->seeColumnHasValue( $I, 'Enabled' );
	}


	/**
	 * Test that the column shows the correct value for a product that has sync disabled.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_column_displays_sync_disabled( AcceptanceTester $I ) {

		// enable sync for the product before viewing the Products page
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product ] );

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the column displays the correct value for a sync-disabled product' );

		$this->seeColumnHasValue( $I, 'Disabled' );
	}


	/**
	 * Test that the column defaults to "Enabled"
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_column_defaults_to_enabled( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the column displays the correct value for a product with no sync setting set' );

		$this->seeColumnHasValue( $I, 'Enabled' );
	}


	/**
	 * See that the column has a specific value.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @param string $value value to check
	 */
	private function seeColumnHasValue( AcceptanceTester $I, string $value ) {

		$I->see( $value, 'table.wp-list-table td' );
	}


}
