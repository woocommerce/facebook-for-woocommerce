<?php

class ProductSyncEnabledFilterCest {


	/** @var \WC_Product|null product objects created for the test */
	private $sync_enabled_product;
	private $sync_disabled_product;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function _before( AcceptanceTester $I ) {

		// save four generic products
		$this->sync_enabled_product         = $I->haveProductInDatabase();
		$this->sync_disabled_product        = $I->haveProductInDatabase();
		$this->product_in_excluded_category = $I->haveProductInDatabase();
		$this->product_in_excluded_tag      = $I->haveProductInDatabase();

		// enable/disable sync for the products
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->sync_enabled_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->sync_disabled_product ] );

		// save a product category and a product tag to exclude from facebook sync
		list( $excluded_category_id, $excluded_category_taxonomy_id ) = $I->haveTermInDatabase( 'Excluded Category', 'product_cat' );
		list( $excluded_tag_id, $excluded_tag_taxonomy_id )           = $I->haveTermInDatabase( 'Excluded Tag', 'product_tag' );

		// configure the category and tag as excluded from facebook sync
		$I->haveFacebookForWooCommerceSettingsInDatabase( [
			'fb_sync_exclude_categories' => [ $excluded_category_id ],
			'fb_sync_exclude_tags'       => [ $excluded_tag_id ],
		] );

		// associate products with excluded terms
		$I->haveTermRelationshipInDatabase( $this->product_in_excluded_category->get_id(), $excluded_category_taxonomy_id );
		$I->haveTermRelationshipInDatabase( $this->product_in_excluded_tag->get_id(), $excluded_tag_taxonomy_id );

		// always log in
		$I->loginAsAdmin();
	}


	/**
	 * Test that the filter is present.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_filter_present( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the filter is present' );

		$I->see( 'Filter by Facebook sync setting', 'div.actions' );
	}


	/**
	 * Test that the filter shows all products by default.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_filter_default( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the column displays both sync enabled and sync disabled products' );

		$this->seeColumnHasValue( $I, 'Enabled' );
		$this->seeColumnHasValue( $I, 'Disabled' );
	}


	/**
	 * Test that the filter shows only the sync enabled product when filtering for that.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_filter_enabled( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the filter shows only the sync enabled product when filtering for that' );

		$this->selectFilterOption( $I, 'Facebook sync enabled' );

		$this->seeColumnHasValue( $I, 'Enabled' );
		$this->seeColumnDoesNotHaveValue( $I, 'Disabled' );
	}


	/**
	 * Test that the filter shows only the sync disabled product when filtering for that.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_filter_disabled( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the filter shows only the sync disabled product when filtering for that' );

		$this->selectFilterOption( $I, 'Facebook sync disabled' );

		$this->seeColumnHasValue( $I, 'Disabled' );
		$this->seeColumnDoesNotHaveValue( $I, 'Enabled' );

		$this->seeProductRow( $I, $this->product_in_excluded_category->get_id() );
		$this->seeProductRow( $I, $this->product_in_excluded_tag->get_id() );
		$this->dontSeeProductRow( $I, $this->sync_enabled_product->get_id() );
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


	/**
	 * See that the column does not have a specific value.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @param string $value value to check
	 */
	private function seeColumnDoesNotHaveValue( AcceptanceTester $I, string $value ) {

		$I->dontSee( $value, 'table.wp-list-table td' );
	}


	/**
	 * Sees that a product row with id equal to post-{id} exists on the page and is visible.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @param int $product_id the ID of the product
	 */
	private function seeProductRow( AcceptanceTester $I, int $product_id ) {

		$I->seeElement( 'tr', [ 'id' => "post-{$product_id}" ] );
	}


	/**
	 * Sees that a product row with id equal to post-{id} is not present or visible.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @param int $product_id the ID of the product
	 */
	private function dontSeeProductRow( AcceptanceTester $I, int $product_id ) {

		$I->dontSeeElement( 'tr', [ 'id' => "post-{$product_id}" ] );
	}


	/**
	 * Select an option on the filter.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @param string $option_label label of the option to select
	 */
	private function selectFilterOption( AcceptanceTester $I, string $option_label ) {

		$I->selectOption( 'form select[name=fb_sync_enabled]', $option_label );

		$I->click( 'Filter' );
	}


}
