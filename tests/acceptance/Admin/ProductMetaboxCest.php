<?php

use SkyVerge\WooCommerce\Facebook\Handlers\Connection;

class ProductMetaboxCest {


	/** @var WC_Product|null product object created for the test */
	private $product;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 * @throws \Exception
	 */
    public function _before( AcceptanceTester $I ) {

		$this->product = $I->haveProductInDatabase();

		// always log in
		$I->loginAsAdmin();
    }


	/**
	 * Tests that the field is unchecked if sync is disabled in parent.
	 *
	 * @param AcceptanceTester $I
	 * @throws \Exception
	 */
	public function try_metabox_is_not_visibile_if_the_plugin_is_not_connected( AcceptanceTester $I ) {

		$I->wantTo( 'Test that product metabox is not shown when the plugin is connected' );

		$I->dontHaveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN );

		$I->amEditingPostWithId( $this->product->get_id() );
		$I->waitForElementVisible( 'input[type="submit"][value="Update"]', 5 );

		$I->dontSeeElement( [ 'css' => '#facebook_metabox' ] );
	}


	/**
	 * Tests that the field is unchecked if sync is disabled in parent.
	 *
	 * @param AcceptanceTester $I
	 * @throws \Exception
	 */
	public function try_metabox_is_visibile_if_the_plugin_is_connected( AcceptanceTester $I ) {

		$I->wantTo( 'Test that product metabox is shown when the plugin is connected' );

		$I->haveOptionInDatabase( Connection::OPTION_ACCESS_TOKEN, 'xyz' );
		$I->haveOptionInDatabase( WC_Facebookcommerce_Integration::OPTION_PRODUCT_CATALOG_ID, 'xyz' );

		$I->haveOptionInDatabase( \WC_Facebookcommerce_Integration::SETTING_FACEBOOK_PAGE_ID, '1234' );

		$I->amEditingPostWithId( $this->product->get_id() );
		$I->waitForElementVisible( 'input[type="submit"][value="Update"]', 5 );

		$I->seeElement( [ 'css' => '#facebook_metabox' ] );
	}


}
