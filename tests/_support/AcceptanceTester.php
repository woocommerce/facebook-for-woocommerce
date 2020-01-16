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
	 *     @type int[] $fb_sync_exclude_categories product categories IDs to exclude from facebook sync
	 *     @type int[] $fb_sync_exclude_tags product tags IDS to exclude from facebook sync
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

		foreach ( $args['variations'] as $key => $attributes ) {

			$product_variation = new WC_Product_Variation();

			$product_variation->set_parent_id( $variable_product->get_id() );
			$product_variation->set_attributes( [ 'size' => 'm' ] );
			$product_variation->save();

			$variations[ $key ] = $product_variation;
		}

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


}
