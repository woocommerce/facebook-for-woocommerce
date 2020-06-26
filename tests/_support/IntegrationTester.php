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
	 * @param array $args {
	 *     Key value pairs where the key is the name of a product prop, plus:
	 *
	 *     @type string $class name of the class to instantiate. Uses WC_Product as default.
	 * }
	 * @return \WC_Product
	 */
	public function get_product( $args = [] ) {

		$class_name = isset( $args['class'] ) ? $args['class'] : \WC_Product::class;

		unset( $args['class'] );

		$product = new $class_name();

		foreach ( $args as $prop => $value ) {

			if ( $value && is_callable( [ $product, "set_{$prop}" ] ) ) {
				$product->{"set_{$prop}"}( $value );
			}
		}

		$product->save();

		return $product;
	}


	/**
	 * Gets a new variable product object, with variations.
	 *
	 * @param array $args {
	 *     Any of the parameters accepted by get_product(), plus:
	 *
	 *     @type int|int[] $children array of variation IDs, if unspecified will generate the amount passed (default 3)
	 * }
	 * @return \WC_Product_Variable
	 */
	public function get_variable_product( $args = [] ) {

		$children = isset( $args['children'] ) ? $args['children'] : [];

		unset( $args['children'] );

		/** @var \WC_Product_Variable */
		$product = $this->get_product( array_merge( $args, [ 'class' => \WC_Product_Variable::class ] ) );

		$variations = [];

		if ( empty( $children ) || is_numeric( $children ) ) {

			$default_variations = 3;
			$total_variations   = 0 !== $children && empty( $children ) ? $default_variations : max( 0, (int) $children );

			for ( $i = 0; $i < $total_variations; $i++ ) {

				$variation = $this->get_product( array_merge( $args, [
					'class'     => \WC_Product_Variation::class,
					'parent_id' => $product->get_id(),
				] ) );

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
	 * Uses reflection to make a method public so we can test it.
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


	/**
	 * Uses reflection to make a property public so we can test it.
	 *
	 * Copied from Jilt for WooCommerce.
	 *
	 * @param string $class_name class name
	 * @param string $property_name property name
	 * @return ReflectionProperty
	 * @throws ReflectionException
	 */
	public static function getProperty( $class_name, $property_name ) {

		$property = new ReflectionProperty( $class_name, $property_name );
		$property->setAccessible( true );

		return $property;
	}


	/**
	 * Uses reflection to get the value of a property.
	 *
	 * Copied from Jilt for WooCommerce.
	 *
	 * @param object $object object
	 * @param string $property_name property name
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function getPropertyValue( $object, $property_name, $class_name = null ) {

		return $this->getProperty( $class_name ?: get_class( $object ), $property_name )->getValue( $object );
	}


	/**
	 * Uses reflection to set the value of a property.
	 *
	 * @param object $object object
	 * @param string $property_name property name
	 * @param string $property_value property value
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function setPropertyValue( $object, $property_name, $value, $class_name = null ) {

		return $this->getProperty( $class_name ?: get_class( $object ), $property_name )->setValue( $object, $value );
	}


}
