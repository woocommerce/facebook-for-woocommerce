<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class ProductSyncEnabledFilterCest {


	// product objects created for the tests */
	/** @var \WC_Product */
	private $visible_included_product;
	/** @var \WC_Product */
	private $hidden_included_product;
	/** @var \WC_Product */
	private $sync_disabled_product;
	/** @var \WC_Product */
	private $product_in_excluded_category;
	/** @var \WC_Product */
	private $product_in_excluded_tag;
	/** @var \WC_Product */
	private $variable_product_with_visible_included_variations;
	/** @var \WC_Product */
	private $variable_product_with_only_hidden_included_variations;
	/** @var \WC_Product */
	private $variable_product_with_only_excluded_variations;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
	public function _before( AcceptanceTester $I ) {

		// save some generic products
		$this->visible_included_product     = $I->haveProductInDatabase();
		$this->hidden_included_product      = $I->haveProductInDatabase();
		$this->sync_disabled_product        = $I->haveProductInDatabase();

		// enable/disable sync and set visibility for the products
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->visible_included_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->hidden_included_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->sync_disabled_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::set_product_visibility( $this->hidden_included_product, false );

		// create generic products
		$this->product_in_excluded_category = $I->haveProductInDatabase();
		$this->product_in_excluded_tag      = $I->haveProductInDatabase();

		// save a product category and a product tag to exclude from facebook sync
		list( $excluded_category_id, $excluded_category_taxonomy_id ) = $I->haveTermInDatabase( 'Excluded Category', 'product_cat' );
		list( $excluded_tag_id, $excluded_tag_taxonomy_id )           = $I->haveTermInDatabase( 'Excluded Tag', 'product_tag' );

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, '1234' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, '1234' );

		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, [ $excluded_category_id ] );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS, [ $excluded_tag_id ] );

		// configure the category and tag as excluded from facebook sync
		$I->haveFacebookForWooCommerceSettingsInDatabase( [
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS => [ $excluded_category_id ],
			\WC_Facebookcommerce_Integration::SETTING_EXCLUDED_PRODUCT_TAG_IDS      => [ $excluded_tag_id ],
		] );

		// associate products with excluded terms
		$I->haveTermRelationshipInDatabase( $this->product_in_excluded_category->get_id(), $excluded_category_taxonomy_id );
		$I->haveTermRelationshipInDatabase( $this->product_in_excluded_tag->get_id(), $excluded_tag_taxonomy_id );

		// save a variable product with visible, hidden, and excluded variations
		$result = $I->haveVariableProductInDatabase( [
			'attributes' => [
				'color' => [ 'red', 'green', 'black' ]
			],
			'variations' => [
				'visible_included_variation' => [ 'color' => 'red' ],
				'hidden_included_variation'  => [ 'color' => 'green' ],
				'excluded_variation'         => [ 'color' => 'black' ],
			],
		] );

		$this->variable_product_with_visible_included_variations = $result['product'];

		$variations = $result['variations'];

		$visible_included_variation = $variations['visible_included_variation'];
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $visible_included_variation ] );

		$hidden_included_variation = $variations['hidden_included_variation'];
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $hidden_included_variation ] );
		\SkyVerge\WooCommerce\Facebook\Products::set_product_visibility( $hidden_included_variation, false );

		$excluded_variation = $variations['excluded_variation'];
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $excluded_variation ] );

		// save a variable product with only hidden variations
		$result = $I->haveVariableProductInDatabase( [
			'attributes' => [
				'color' => [ 'red', 'green', 'black' ]
			],
			'variations' => [
				'red'   => [ 'color' => 'red' ],
				'green' => [ 'color' => 'green' ],
				'black' => [ 'color' => 'black' ],
			],
		] );

		$this->variable_product_with_only_hidden_included_variations = $result['product'];

		foreach ( $result['variations'] as $variation ) {

			\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $variation ] );
			\SkyVerge\WooCommerce\Facebook\Products::set_product_visibility( $variation, false );
		}

		// save a variable product with only excluded variations
		$result = $I->haveVariableProductInDatabase( [
			'attributes' => [
				'color' => [ 'red', 'green', 'black' ]
			],
			'variations' => [
				'red'   => [ 'color' => 'red' ],
				'green' => [ 'color' => 'green' ],
				'black' => [ 'color' => 'black' ],
			],
		] );

		$this->variable_product_with_only_excluded_variations = $result['product'];

		foreach ( $result['variations'] as $variation ) {

			\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $variation ] );
		}

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

		$I->wantTo( 'Test that the column displays included (hidden and visible) and excluded products' );

		$this->seeColumnHasValue( $I, 'Sync and show' );
		$this->seeColumnHasValue( $I, 'Sync and hide' );
		$this->seeColumnHasValue( $I, 'Do not sync' );

		$this->seeProductRow( $I, $this->visible_included_product->get_id() );
		$this->seeProductRow( $I, $this->hidden_included_product->get_id() );
		$this->seeProductRow( $I, $this->sync_disabled_product->get_id() );

		$this->seeProductRow( $I, $this->product_in_excluded_category->get_id() );
		$this->seeProductRow( $I, $this->product_in_excluded_tag->get_id() );

		$this->seeProductRow( $I, $this->variable_product_with_visible_included_variations->get_id() );
		$this->seeProductRow( $I, $this->variable_product_with_only_hidden_included_variations->get_id() );
		$this->seeProductRow( $I, $this->variable_product_with_only_excluded_variations->get_id() );
	}


	/**
	 * Test that the filter shows only visible included products when filtering for that.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_filter_sync_and_show( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the filter shows only visible included products when filtering for that' );

		$this->selectFilterOption( $I, 'Sync and show' );

		$this->seeColumnHasValue( $I, 'Sync and show' );
		$this->seeColumnDoesNotHaveValue( $I, 'Sync and hide' );
		$this->seeColumnDoesNotHaveValue( $I, 'Disabled' );

		$this->seeProductRow( $I, $this->visible_included_product->get_id() );
		$this->dontSeeProductRow( $I, $this->hidden_included_product->get_id() );
		$this->dontSeeProductRow( $I, $this->sync_disabled_product->get_id() );

		$this->dontSeeProductRow( $I, $this->product_in_excluded_category->get_id() );
		$this->dontSeeProductRow( $I, $this->product_in_excluded_tag->get_id() );

		$this->seeProductRow( $I, $this->variable_product_with_visible_included_variations->get_id() );
		$this->dontSeeProductRow( $I, $this->variable_product_with_only_hidden_included_variations->get_id() );
		$this->dontSeeProductRow( $I, $this->variable_product_with_only_excluded_variations->get_id() );
	}


	/**
	 * Test that the filter shows only hidden included products when filtering for that.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_filter_sync_and_hide( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the filter shows only hidden included products when filtering for that' );

		$this->selectFilterOption( $I, 'Sync and hide' );

		$this->seeColumnHasValue( $I, 'Sync and hide' );
		$this->seeColumnDoesNotHaveValue( $I, 'Sync and show' );
		$this->seeColumnDoesNotHaveValue( $I, 'Disabled' );

		$this->seeProductRow( $I, $this->hidden_included_product->get_id() );
		$this->dontSeeProductRow( $I, $this->visible_included_product->get_id() );
		$this->dontSeeProductRow( $I, $this->sync_disabled_product->get_id() );

		$this->dontSeeProductRow( $I, $this->product_in_excluded_category->get_id() );
		$this->dontSeeProductRow( $I, $this->product_in_excluded_tag->get_id() );

		$this->seeProductRow( $I, $this->variable_product_with_only_hidden_included_variations->get_id() );
		$this->dontSeeProductRow( $I, $this->variable_product_with_visible_included_variations->get_id() );
		$this->dontSeeProductRow( $I, $this->variable_product_with_only_excluded_variations->get_id() );
	}


	/**
	 * Test that the filter shows only excluded products when filtering for that.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
	public function try_filter_do_not_sync( AcceptanceTester $I ) {

		$I->amOnProductsPage();

		$I->wantTo( 'Test that the filter shows only excluded products when filtering for that' );

		$this->selectFilterOption( $I, 'Do not sync' );

		$this->seeColumnHasValue( $I, 'Do not sync' );
		$this->seeColumnDoesNotHaveValue( $I, 'Sync and show' );
		$this->seeColumnDoesNotHaveValue( $I, 'Sync and hide' );

		$this->seeProductRow( $I, $this->sync_disabled_product->get_id() );
		$this->dontSeeProductRow( $I, $this->visible_included_product->get_id() );
		$this->dontSeeProductRow( $I, $this->hidden_included_product->get_id() );

		$this->seeProductRow( $I, $this->product_in_excluded_category->get_id() );
		$this->seeProductRow( $I, $this->product_in_excluded_tag->get_id() );

		$this->seeProductRow( $I, $this->variable_product_with_only_excluded_variations->get_id() );
		$this->dontSeeProductRow( $I, $this->variable_product_with_visible_included_variations->get_id() );
		$this->dontSeeProductRow( $I, $this->variable_product_with_only_hidden_included_variations->get_id() );
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

		$I->waitForJqueryAjax();
	}


}
