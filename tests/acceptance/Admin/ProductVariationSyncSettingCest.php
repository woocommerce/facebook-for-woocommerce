<?php

class ProductVariationSyncSettingCest {


	/** @var WC_Product|null product object created for the test */
	private $variable_product;

	/** @var WC_Product_Variation|null product variation object created for the test */
	private $product_variation;


	/**
	 * Runs before each test.
	 *
	 * @param AcceptanceTester $I tester instance
	 */
    public function _before( AcceptanceTester $I ) {

		$product_objects = $I->haveVariableProductInDatabase();

		$this->variable_product  = $product_objects['product'];
		$this->product_variation = $product_objects['variations']['product_variation'];

		// always log in
		$I->loginAsAdmin();
    }


	/**
	 * Tests that the field is unchecked if sync is disabled in parent.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_field_is_unchecked_if_sync_disabled_in_parent( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->variable_product ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that the field is unchecked if sync is disabled in parent' );

		$I->dontSeeCheckboxIsChecked( "#variable_fb_sync_enabled{$index}" );
	}


	/**
	 * Tests that the field is checked if sync is enabled in parent.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_field_is_checked_if_sync_enabled_in_parent( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->variable_product ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that the field is checked if sync is enabled in parent' );

		$I->seeCheckboxIsChecked( "#variable_fb_sync_enabled{$index}" );
	}


}
