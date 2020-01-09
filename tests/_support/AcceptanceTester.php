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
	 * Go to the Products screen.
	 */
	public function amOnProductsPage() {

		$this->amOnAdminPage('edit.php?post_type=product' );
	}


}
