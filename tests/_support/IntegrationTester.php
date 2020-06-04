<?php

use SkyVerge\WooCommerce\Facebook\API;

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


	/**
	 * Gets an instance of an anonymous API\Response subclass that uses the API\Traits\Paginated_Response trait.
	 *
	 * @return API\Response
	 */
	public function get_paginated_response( $response_data = [] ) {

		return new class( json_encode( $response_data ) ) extends API\Response {

			use API\Traits\Paginated_Response;
		};
	}


	/**
	 * Use reflection to make a method public so we can test it.
	 *
	 * @param string $class_name class name
	 * @param string $method_name method name
	 * @return ReflectionMethod
	 * @throws ReflectionException
	 */
	public static function getMethod( $class_name, $method_name ) {

		$class  = new ReflectionClass( $class_name );
		$method = $class->getMethod( $method_name );
		$method->setAccessible( true );

		return $method;
	}


}
