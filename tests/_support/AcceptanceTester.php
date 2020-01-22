<?php

use SkyVerge\WooCommerce\Facebook;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor {

	use _generated\AcceptanceTesterActions;


	/**
	 * Updates the settings of the Facebook for WooCommerce plugin.
	 *
	 * @param array $args {
	 *     @type int[] $excluded_product_category_ids product categories IDs to exclude from facebook sync
	 *     @type int[] $excluded_product_tag_ids tags IDS to exclude from facebook sync
	 * }
	 */
	public function haveFacebookForWooCommerceSettingsInDatabase( array $args = [] ) {

		$this->haveOptionInDatabase( 'woocommerce_' . \WC_Facebookcommerce::INTEGRATION_ID . '_settings', $args );
	}


	/**
	 * Creates a product in the database.
	 *
	 * @param array $args {
	 *     @type float  $price        product price
	 *     @type string $description  product description
	 *     @type string $type         product type
	 *     @type bool   $sync_enabled whether the product should have sync enabled
	 * }
	 * @return \WC_Product
	 */
	public function haveProductInDatabase( array $args = [] ) {

		$args = wp_parse_args( $args, [
			'price'        => 1.00,
			'description'  => 'This is a test product',
			'type'         => 'simple',
			'sync_enabled' => false,
		] );

		switch ( $args['type'] ) {

			case 'variable': $class = \WC_Product_Variable::class; break;

			default: $class = \WC_Product_Simple::class; break;
		}

		/** @var \WC_Product $product */
		$product = new $class();
		$product->set_price( (float) $args['price'] );
		$product->set_description( (string) $args['description'] );

		$product->save();

		if ( ! empty( $args['sync_enabled'] ) ) {
			Facebook\Products::enable_sync_for_products( [ $product ] );
		}

		return $product;
	}


	/**
	 * Creates a variable product in the database.
	 *
	 * Use the keys in $args['variations'] to easily retrieve the product variation objects from the returned array.
	 *
	 * @see AcceptanceTester::haveProductInDatabase() for additional keys supported in $args
	 *
	 * @param array $args {
	 *     @type array $attributes key value pairs of attribute names and attribute options
	 *     @type array $variations key value pairs of variation identifiers and variation attributes
	 * }
	 * @return array
	 */
	public function haveVariableProductInDatabase( array $args = [] ) {

		$args = wp_parse_args( $args, [
			'type'       => 'variable',
			'attributes' => [
				'size' => [ 's' ]
			],
			'variations' => [
				'product_variation' => [ 'size' => 's' ],
			],
		] );

		$variable_product = $this->haveProductInDatabase( $args );

		$attributes = [];
		$variations = [];

		foreach ( $args['attributes'] as $name => $options ) {

			$attribute = new WC_Product_Attribute();

			$attribute->set_name( $name );
			$attribute->set_options( $options );
			$attribute->set_visible( true );
			$attribute->set_variation( true );

			$attributes[] = $attribute;
		}

		$variable_product->set_attributes( $attributes );
		$variable_product->save();

		$variations_ids = [];

		foreach ( $args['variations'] as $key => $attributes ) {

			$product_variation = new WC_Product_Variation();

			$product_variation->set_parent_id( $variable_product->get_id() );
			$product_variation->set_attributes( $attributes );

			$variations_ids[] = $product_variation->save();

			$variations[ $key ] = $product_variation;
		}

		$variable_product->set_children( $variations_ids );
		$variable_product->save();

		return [
			'product'    => $variable_product,
			'variations' => $variations,
		];
	}


	/**
	 * Go to the Products screen.
	 */
	public function amOnProductsPage() {

		$this->amOnAdminPage('edit.php?post_type=product' );
	}


	/**
	 * Go to the Edit Product screen and expand the settings fields for the given product variation.
	 *
	 * Returns the index of the variation in the list of variations.
	 *
	 * @see AcceptanceTester::openVariationMetabox()
	 *
	 * @param \WC_Product_Variation $variation the product variation
	 * @return int
	 */
	public function amEditingProductVariation( \WC_Product_Variation $variation ) {

		$this->amEditingPostWithId( $variation->get_parent_id() );

		$this->click( 'Variations', '.variations_tab' );

		return $this->openVariationMetabox( $variation );
	}


	/**
	 * Open the metabox that contains the edit fields for the given variation.
	 *
	 * Returns the index of the variation in the list of variations.
	 *
	 * @param \WC_Product_Variation $variation
	 * @return void
	 */
	public function openVariationMetabox( \WC_Product_Variation $variation ) {

		// matches a hidden input field with value equal to the ID of the variation
		$variaton_id_xpath = "input[starts-with(@name, 'variable_post_id') and @value = {$variation->get_id()}]";

		// matches each variation metabox container
		$variation_container_xpath  = "//div[contains(concat(' ', normalize-space(@class), ' '), ' woocommerce_variation wc-metabox closed ')]";
		// matches elements that contain a hidden input field with value equal to the ID of the variation
		$variation_container_xpath .= "[descendant::{$variaton_id_xpath}]";

		$this->waitForElementVisible( $variation_container_xpath, 5 );
		$this->waitForElementNotVisible( '.blockOverlay', 5 );
		$this->scrollTo( $variation_container_xpath, 0, -200 );

		$this->click( $variation_container_xpath );

		// return the index of the variation
		return (int) trim( str_replace( 'variable_post_id', '', $this->grabAttributeFrom( "//{$variaton_id_xpath}", 'name' ) ), '[]' );
	}


}
