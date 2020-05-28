<?php


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
class IntegrationTester extends \Codeception\Actor {

	use _generated\IntegrationTesterActions;


	/**
	 * Gets a new product object.
	 *
	 * @return \WC_Product
	 */
	public function get_product() {

		$product = new \WC_Product();
		$product->save();

		return $product;
	}

	public function create_product_category(){
		$result = wp_insert_term(
			'New Category', // the term
			'product_cat', // the taxonomy
			array(
				'description'=> 'Category description',
				'slug' => 'new-category'
			)
		);
		return $result['term_id'];
	}

	/**
	 * Gets a new order object
	 *
	 * @return \WC_Order
	 */
	public function get_order() {

		$order = new \WC_Order();
		$order->save();

		return $order;
	}

	/**
	 * Associates product and order
	 *
	 */
	public function associate_order_and_product( $order, $product, $num_items = 1 ) {
		$order_item_product = new \WC_Order_Item_Product();
		$order_item_product->set_product_id( $product->get_id() );
		$order_item_product->set_quantity($num_items);
		$order_item_product->set_total( $num_items*$product->get_price() );
		$order_item_product->set_subtotal( $num_items*$product->get_price() );
		$order_item_product->save();
		$order->add_item( $order_item_product );
		$order->set_total( $order_item_product->get_total() );
		$order->save();
	}

	/**
	 * Gets a new variable product object, with variations.
	 *
	 * @param int|int[] $children array of variation IDs, if unspecified will generate the amount passed (default 3)
	 * @return \WC_Product_Variable
	 */
	public function get_variable_product( $children = [] ) {

		$product = new \WC_Product_Variable();

		$product->save();

		$variations = [];

		if ( empty( $children ) || is_numeric( $children ) ) {

			$default_variations = 3;
			$total_variations   = 0 !== $children && empty( $children ) ? $default_variations : max( 0, (int) $children );

			for ( $i = 0; $i < $total_variations; $i++ ) {

				$variation = new \WC_Product_Variation();
				$variation->set_parent_id( $product->get_id() );
				$variation->save();

				$variations[] = $variation->get_id();
			}
		}

		$product->set_children( $variations );
		$product->save();

		return $product;
	}


}
