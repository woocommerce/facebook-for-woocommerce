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


	/**
	 * Tests that the field is unchecked when sync is disabled.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_field_is_unchecked( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->variable_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product_variation ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that the field is unchecked when sync is disabled' );

		$I->dontSeeCheckboxIsChecked( "#variable_fb_sync_enabled{$index}" );
	}


	/**
	 * Tests that the field is checked when sync is enabled.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_field_is_checked( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->variable_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->product_variation ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that the field is checked when sync is enabled' );

		$I->seeCheckboxIsChecked( "#variable_fb_sync_enabled{$index}" );
	}


	/**
	 * Tests that the settings fields are disabled if sync is disabled for this variation.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_settings_fields_are_disabled_if_sync_is_disabled( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->variable_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product_variation ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that the settings fields are disabled if sync is disabled for this variation' );

		$I->waitForElementVisible( sprintf( '#variable_%s%s', WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ), 5 );

		$I->seeElement( sprintf( '#variable_%s%s:disabled', WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ) );
		$I->seeElement( sprintf( '#variable_%s%s:disabled', WC_Facebook_Product::FB_PRODUCT_IMAGE, $index ) );
		$I->seeElement( sprintf( '#variable_%s%s:disabled', WC_Facebook_Product::FB_PRODUCT_PRICE, $index ) );
	}


	/**
	 * Tests that the settings fields are enabled if sync is enabled for this variation.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_settings_fields_are_enabled_if_sync_is_enabled( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->variable_product ] );
		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->product_variation ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that the settings fields are enabled if sync is enabled for this variation' );

		$I->waitForElementVisible( sprintf( '#variable_%s%s', WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ), 5 );

		$I->seeElement( sprintf( '#variable_%s%s:not(:disabled)', WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ) );
		$I->seeElement( sprintf( '#variable_%s%s:not(:disabled)', WC_Facebook_Product::FB_PRODUCT_IMAGE, $index ) );
		$I->seeElement( sprintf( '#variable_%s%s:not(:disabled)', WC_Facebook_Product::FB_PRODUCT_PRICE, $index ) );
	}


	/**
	 * Tests that the sync can be enabled for a variation.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_sync_can_be_enabled_for_a_variation( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::disable_sync_for_products( [ $this->product_variation ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that sync can be enabled for a variation' );

		$I->waitForElementVisible( "#variable_fb_sync_enabled{$index}", 5 );

		$I->click( "#variable_fb_sync_enabled{$index}" );
		$I->fillField( sprintf( '#variable_%s%s', WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ), 'Test description.' );
		$I->fillField( sprintf( '#variable_%s%s', WC_Facebook_Product::FB_PRODUCT_IMAGE, $index ), 'https://example.com/logo.png' );
		$I->fillField( sprintf( '#variable_%s%s', WC_Facebook_Product::FB_PRODUCT_PRICE, $index ), '12.34' );

		$I->click( [ 'css' => '.save-variation-changes' ] );

		$I->wait( 4 );

		$index = $I->openVariationMetabox( $this->product_variation );

		$I->waitForElementVisible( "#variable_fb_sync_enabled{$index}", 5 );

		$I->seeCheckboxIsChecked( "#variable_fb_sync_enabled{$index}" );
		$I->seeInField( sprintf( '#variable_%s%s', WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ), 'Test description.' );
		$I->seeInField( sprintf( '#variable_%s%s', WC_Facebook_Product::FB_PRODUCT_IMAGE, $index ), 'https://example.com/logo.png' );
		$I->seeInField( sprintf( '#variable_%s%s', WC_Facebook_Product::FB_PRODUCT_PRICE, $index ), '12.34',  );
	}


	/**
	 * Tests that the sync can be disabled for a variation.
	 *
	 * @param AcceptanceTester $I
	 */
	public function try_sync_can_be_disabled_for_a_variation( AcceptanceTester $I ) {

		\SkyVerge\WooCommerce\Facebook\Products::enable_sync_for_products( [ $this->product_variation ] );

		$index = $I->amEditingProductVariation( $this->product_variation );

		$I->wantTo( 'Test that sync can be disabled for a variation' );

		$I->waitForElementVisible( "#variable_fb_sync_enabled{$index}", 5 );

		$I->click( "#variable_fb_sync_enabled{$index}" );

		$I->click( [ 'css' => '.save-variation-changes' ] );

		$I->wait( 4 );

		$index = $I->openVariationMetabox( $this->product_variation );

		$I->waitForElementVisible( "#variable_fb_sync_enabled{$index}", 5 );

		$I->dontSeeCheckboxIsChecked( "#variable_fb_sync_enabled{$index}" );
		$I->seeElement( sprintf( '#variable_%s%s:disabled', WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ) );
		$I->seeElement( sprintf( '#variable_%s%s:disabled', WC_Facebook_Product::FB_PRODUCT_IMAGE, $index ) );
		$I->seeElement( sprintf( '#variable_%s%s:disabled', WC_Facebook_Product::FB_PRODUCT_PRICE, $index ) );
	}


}
