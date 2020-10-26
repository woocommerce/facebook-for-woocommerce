<?php

use SkyVerge\WooCommerce\Facebook\API;
use SkyVerge\WooCommerce\Facebook\Products\Sync;

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
	 * Gets a new product variation object.
	 *
	 * @param array $args {
	 *     Key value pairs where the key is the name of a product prop
	 * }
	 * @return \WC_Product_Variation
	 */
	public function get_product_variation( $args = [] ) {

		$parent_product = $this->get_variable_product( array_merge( $args, [ 'children' => 1 ] ) );

		return wc_get_product( current( $parent_product->get_children() ) );
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
	 * Gets an instance of an anonymous API\Request subclass that uses the API\Traits\Idempotent_Request trait.
	 *
	 * @param string $path the request path
	 * @return API\Request
	 */
	public function get_idempotent_request( $path ) {

		return new class( $path, 'POST' ) extends API\Request {

			use API\Traits\Idempotent_Request;
		};
	}


	/**
	 * Creates color attribute.
	 *
	 * @param string $name attribute name
	 * @param string[] $options possible values for the attribute
	 * @param bool $variation used for variations or not
	 * @return \WC_Product_Attribute
	 */
	public function create_color_attribute( $name = 'color', $options = [ 'pink', 'blue' ], $variation = false, $taxonomy = false ) {

		$color_attribute = new WC_Product_Attribute();
		$color_attribute->set_name( $name );
		$color_attribute->set_options( $options );
		$color_attribute->set_variation( $variation );

		if ( $taxonomy ) {

			// create the taxonomy attribute
			wc_create_attribute( [ $color_attribute->get_name() ] );

			foreach ( $options as $option ) {
				wp_insert_term( $option, $color_attribute->get_name() );
			}
		}

		return $color_attribute;
	}


	/**
	 * Creates size attribute.
	 *
	 * @param string $name attribute name
	 * @param string[] $options possible values for the attribute
	 * @param bool $variation used for variations or not
	 * @return \WC_Product_Attribute
	 */
	public function create_size_attribute( $name = 'size', $options = [ 'small', 'medium', 'large' ], $variation = false, $taxonomy = false ) {

		$size_attribute = new WC_Product_Attribute();
		$size_attribute->set_name( $name );
		$size_attribute->set_options( $options );
		$size_attribute->set_variation( $variation );

		if ( $taxonomy ) {

			// create the taxonomy attribute
			wc_create_attribute( [ $size_attribute->get_name() ] );

			foreach ( $options as $option ) {
				wp_insert_term( $option, $size_attribute->get_name() );
			}
		}

		return $size_attribute;
	}


	/**
	 * Creates pattern attribute.
	 *
	 * @param string $name attribute name
	 * @param string[] $options possible values for the attribute
	 * @param bool $variation used for variations or not
	 * @return \WC_Product_Attribute
	 */
	public function create_pattern_attribute( $name = 'pattern', $options = [ 'checked', 'floral', 'leopard' ], $variation = false, $taxonomy = false ) {

		$pattern_attribute = new WC_Product_Attribute();
		$pattern_attribute->set_name( $name );
		$pattern_attribute->set_options( $options );
		$pattern_attribute->set_variation( $variation );

		if ( $taxonomy ) {

			// create the taxonomy attribute
			wc_create_attribute( [ $pattern_attribute->get_name() ] );

			foreach ( $options as $option ) {
				wp_insert_term( $option, $pattern_attribute->get_name() );
			}
		}

		return $pattern_attribute;
	}


	/**
	 * Creates product attributes.
	 */
	public function create_product_attributes() {

		return [
			$this->create_color_attribute(),
			$this->create_size_attribute(),
			$this->create_pattern_attribute(),
		];
	}


	/** Sync methods **************************************************************************************************/


	/**
	 * Clears sync requests stored in the product sync handler instance.
	 */
	public function clearSyncRequests() {

		$this->setPropertyValue( facebook_for_woocommerce()->get_products_sync_handler(), 'requests', [] );
	}


	/**
	 * Asserts that the given sync request keys are included in the given list of sync requests.
	 *
	 * @param array $request_keys sync requests keys to search for
	 * @param array|null $requests array of stored sync requests (defaults to the requests from the product sync handler)
	 */
	public function assertSyncRequestsExist( $request_keys = [], $requests = null ) {

		if ( null === $requests ) {
			$requests = $this->getPropertyValue( facebook_for_woocommerce()->get_products_sync_handler(), 'requests' );
		}

		foreach ( $request_keys as $request_key ) {
			$this->assertArrayHasKey( $request_key, $requests );
		}
	}


	/**
	 * Asserts that the given sync request keys are not included in the given list of sync requests.
	 *
	 * @param array $request_keys sync requests keys that shouldn't be included
	 * @param array|null $requests array of stored sync requests (defaults to the requests from the product sync handler)
	 */
	public function assertSyncRequestsNotExist( $request_keys = [], $requests = null ) {

		if ( null === $requests ) {
			$requests = $this->getPropertyValue( facebook_for_woocommerce()->get_products_sync_handler(), 'requests' );
		}

		// make sure all given request keys are not in the sync requests array
		if ( $request_keys ) {

			foreach ( $request_keys as $request_key ) {
				$this->assertArrayNotHasKey( $request_key, $requests );
			}

		// otherwise ensure no requests were added to the sync requests array
		} else {

			$this->assertEmpty( $requests );
		}
	}


	/**
	 * Asserts that the given product IDs are scheduled for sync.
	 *
	 * @param array $product_ids product IDs
	 * @param array|null $requests array of stored sync requests (defaults to the requests from the product sync handler)
	 */
	public function assertProductsAreScheduledForSync( $product_ids = [], $requests = null ) {

		$this->assertSyncRequestsExist(
			array_map( static function( $product_id ) {
				return Sync::PRODUCT_INDEX_PREFIX . $product_id;
			}, $product_ids ),
			$requests
		);
	}


	/**
	 * Asserts that the given product IDs are not scheduled for sync.
	 *
	 * @param array $product_ids product IDs
	 * @param array|null $requests array of stored sync requests (defaults to the requests from the product sync handler)
	 */
	public function assertProductsAreNotScheduledForSync( $product_ids = [], $requests = null ) {

		$this->assertSyncRequestsNotExist(
			array_map( static function( $product_id ) {
				return Sync::PRODUCT_INDEX_PREFIX . $product_id;
			}, $product_ids ),
			$requests
		);
	}


	/**
	 * Asserts that the given product IDs are scheduled to be deleted.
	 *
	 * @param array $product_ids product IDs
	 * @param array|null $requests array of stored sync requests (defaults to the requests from the product sync handler)
	 */
	public function assertProductsAreScheduledForDelete( $product_ids = [], $requests = null ) {

		$this->assertSyncRequestsExist(
			array_map( static function( $product_id ) {
				return \WC_Facebookcommerce_Utils::get_fb_retailer_id( wc_get_product( $product_id ) );
			}, $product_ids ),
			$requests
		);
	}


	/**
	 * Asserts that the given product IDs are not scheduled to be deleted.
	 *
	 * @param array $product_ids product IDs
	 * @param array|null $requests array of stored sync requests (defaults to the requests from the product sync handler)
	 */
	public function assertProductsAreNotScheduledForDelete( $product_ids = [], $requests = null ) {

		$this->assertSyncRequestsNotExist(
			array_map( static function( $product_id ) {
				return \WC_Facebookcommerce_Utils::get_fb_retailer_id( wc_get_product( $product_id ) );
			}, $product_ids ),
			$requests
		);
	}


	/** Reflection methods **************************************************************************************************/


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
	 * Uses reflection to invoke a method on the given object.
	 *
	 * The method can be private, protected, or public.
	 *
	 * @param object $object the instance to invoke the method on
	 * @param string $method_name the name of the method
	 * @param array $parameters zero or more parameters for the method
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function invokeReflectionMethod( $object, $method_name, ...$parameters ) {

		return $this->getMethod( $object, $method_name )->invokeArgs( $object, $parameters );
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
