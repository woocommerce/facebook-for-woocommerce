<?php
// phpcs:ignoreFile
/**
 * Facebook for WooCommerce.
 */

namespace WooCommerce\Facebook\Framework\Compatibility;

defined( 'ABSPATH' ) or exit;

/**
 * WooCommerce order compatibility class.
 *
 * @since 4.6.0
 */
class OrderCompatibility extends DataCompatibility {


	/**
	 * Gets an order's created date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_created( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_created()' );

		return self::get_date_prop( $order, 'created', $context );
	}


	/**
	 * Gets an order's last modified date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_modified( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_modified()' );

		return self::get_date_prop( $order, 'modified', $context );
	}


	/**
	 * Gets an order's paid date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_paid( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_paid()' );

		return self::get_date_prop( $order, 'paid', $context );
	}


	/**
	 * Gets an order's completed date.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_completed( \WC_Order $order, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_date_completed()' );

		return self::get_date_prop( $order, 'completed', $context );
	}


	/**
	 * Gets an order date.
	 *
	 * This should only be used to retrieve WC core date properties.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param string $type type of date to get
	 * @param string $context if 'view' then the value will be filtered
	 *
	 * @return \WC_DateTime|null
	 */
	public static function get_date_prop( \WC_Order $order, $type, $context = 'edit' ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order' );

		$prop = "date_{$type}";
		$date = is_callable( [ $order, "get_{$prop}" ] ) ? $order->{"get_{$prop}"}( $context ) : null;

		return $date;
	}


	/**
	 * Gets an order property.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $object the order object
	 * @param string $prop the property name
	 * @param string $context if 'view' then the value will be filtered
	 * @param array $compat_props compatibility arguments, unused since 5.5.0
	 * @return mixed
	 */
	public static function get_prop( $object, $prop, $context = 'edit', $compat_props = [] ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::get_prop()' );

		return parent::get_prop( $object, $prop, $context, self::$compat_props );
	}


	/**
	 * Sets an order's properties.
	 *
	 * Note that this does not save any data to the database.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $object the order object
	 * @param array $props the new properties as $key => $value
	 * @param array $compat_props compatibility arguments, unused since 5.5.0
	 * @return bool|\WP_Error
	 */
	public static function set_props( $object, $props, $compat_props = [] ) {

		return parent::set_props( $object, $props, self::$compat_props );
	}


	/**
	 * Adds a coupon to an order item.
	 *
	 * Order item CRUD compatibility method to add a coupon to an order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 * @param array $code the coupon code
	 * @param int $discount the discount amount.
	 * @param int $discount_tax the discount tax amount.
	 * @return int the order item ID
	 */
	public static function add_coupon( \WC_Order $order, $code = [], $discount = 0, $discount_tax = 0 ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Coupon();

		$item->set_props( [
			'code'         => $code,
			'discount'     => $discount,
			'discount_tax' => $discount_tax,
			'order_id'     => $order->get_id(),
		] );

		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Adds a fee to an order.
	 *
	 * Order item CRUD compatibility method to add a fee to an order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 * @param object $fee the fee to add
	 * @return int the order item ID
	 */
	public static function add_fee( \WC_Order $order, $fee ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Fee();

		$item->set_props( [
			'name'      => $fee->name,
			'tax_class' => $fee->taxable ? $fee->tax_class : 0,
			'total'     => $fee->amount,
			'total_tax' => $fee->tax,
			'taxes'     => [
				'total' => $fee->tax_data,
			],
			'order_id'  => $order->get_id(),
		] );

		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Adds shipping line to order.
	 *
	 * Order item CRUD compatibility method to add a shipping line to an order.
	 *
	 * @since 4.7.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param \WC_Shipping_Rate $shipping_rate shipping rate to add
	 * @return int the order item ID
	 */
	public static function add_shipping( \WC_Order $order, $shipping_rate ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Shipping();

		$item->set_props( [
			'method_title' => $shipping_rate->label,
			'method_id'    => $shipping_rate->id,
			'total'        => wc_format_decimal( $shipping_rate->cost ),
			'taxes'        => $shipping_rate->taxes,
			'order_id'     => $order->get_id(),
		] );

		foreach ( $shipping_rate->get_meta_data() as $key => $value ) {
			$item->add_meta_data( $key, $value, true );
			$item->save_meta_data();
		}

		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Adds tax line to an order.
	 *
	 * Order item CRUD compatibility method to add a tax line to an order.
	 *
	 * @since 4.7.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @param int $tax_rate_id tax rate ID
	 * @param int|float $tax_amount cart tax amount
	 * @param int|float $shipping_tax_amount shipping tax amount
	 * @return int order item ID
	 * @throws \WC_Data_Exception
	 *
	 */
	public static function add_tax( \WC_Order $order, $tax_rate_id, $tax_amount = 0, $shipping_tax_amount = 0 ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::add_item()' );

		$item = new \WC_Order_Item_Tax();

		$item->set_props( [
			'rate_id'            => $tax_rate_id,
			'tax_total'          => $tax_amount,
			'shipping_tax_total' => $shipping_tax_amount,
		] );

		$item->set_rate( $tax_rate_id );
		$item->set_order_id( $order->get_id() );
		$item->save();

		$order->add_item( $item );

		return $item->get_id();
	}


	/**
	 * Updates an order coupon.
	 *
	 * Order item CRUD compatibility method to update an order coupon.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 * @param int|\WC_Order_Item $item the order item ID
	 * @param array $args {
	 *     The coupon item args.
	 *
	 *     @type string $code         the coupon code
	 *     @type float  $discount     the coupon discount amount
	 *     @type float  $discount_tax the coupon discount tax amount
	 * }
	 * @return int|bool the order item ID or false on failure
	 * @throws \WC_Data_Exception
	 */
	public static function update_coupon( \WC_Order $order, $item, $args ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order_Item_Coupon' );

		if ( is_numeric( $item ) ) {
			$item = $order->get_item( $item );
		}

		if ( ! is_object( $item ) || ! $item->is_type( 'coupon' ) ) {
			return false;
		}

		if ( ! $order->get_id() ) {
			$order->save();
		}

		$item->set_order_id( $order->get_id() );
		$item->set_props( $args );
		$item->save();

		return $item->get_id();
	}


	/**
	 * Updates an order fee.
	 *
	 * Order item CRUD compatibility method to update an order fee.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 * @param int|\WC_Order_Item $item the order item ID
	 * @param array $args {
	 *     The fee item args.
	 *
	 *     @type string $name       the fee name
	 *     @type string $tax_class  the fee's tax class
	 *     @type float  $line_total the fee total amount
	 *     @type float  $line_tax   the fee tax amount
	 * }
	 * @return int|bool the order item ID or false on failure
	 * @throws \WC_Data_Exception
	 */
	public static function update_fee( \WC_Order $order, $item, $args ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order_Item_Fee' );

		if ( is_numeric( $item ) ) {
			$item = $order->get_item( $item );
		}

		if ( ! is_object( $item ) || ! $item->is_type( 'fee' ) ) {
			return false;
		}

		if ( ! $order->get_id() ) {
			$order->save();
		}

		$item->set_order_id( $order->get_id() );
		$item->set_props( $args );
		$item->save();

		return $item->get_id();
	}


	/**
	 * Reduces stock levels for products in order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 */
	public static function reduce_stock_levels( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_reduce_stock_levels()' );

		wc_reduce_stock_levels( $order->get_id() );
	}


	/**
	 * Updates total product sales count for a given order.
	 *
	 * @since 4.6.0
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order the order object
	 */
	public static function update_total_sales_counts( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'wc_update_total_sales_counts()' );

		wc_update_total_sales_counts( $order->get_id() );
	}


	/**
	 * Determines if an order has an available shipping address.
	 *
	 * @since 4.6.1
	 * @deprecated 5.5.0
	 *
	 * @param \WC_Order $order order object
	 * @return bool
	 */
	public static function has_shipping_address( \WC_Order $order ) {

		wc_deprecated_function( __METHOD__, '5.5.0', 'WC_Order::has_shipping_address()' );

		return $order->has_shipping_address();
	}


	/**
	 * Gets the formatted meta data for an order item.
	 *
	 * @since 4.6.5
	 *
	 * @param \WC_Order_Item $item order item object
	 * @param string $hide_prefix prefix for meta that is considered hidden
	 * @param bool $include_all whether to include all meta (attributes, etc...), or just custom fields
	 * @return array $item_meta {
	 *     @type string $label meta field label
	 *     @type mixed $value meta value
 	 * }
	 */
	public static function get_item_formatted_meta_data( $item, $hide_prefix = '_', $include_all = false ) {

		if ( $item instanceof \WC_Order_Item && SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.1' ) ) {

			$meta_data = $item->get_formatted_meta_data( $hide_prefix, $include_all );
			$item_meta = [];

			foreach ( $meta_data as $meta ) {

				$item_meta[] = array(
					'label' => $meta->display_key,
					'value' => $meta->value,
				);
			}

		} else {

			$item_meta = new \WC_Order_Item_Meta( $item );
			$item_meta = $item_meta->get_formatted( $hide_prefix );
		}

		return $item_meta;
	}


	/**
	 * Gets the admin Edit screen URL for an order.
	 *
	 * @since 5.0.1
	 *
	 * @param \WC_Order $order order object
	 * @return string
	 */
	public static function get_edit_order_url( \WC_Order $order ) {

		if ( SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.3' ) ) {
			$order_url = $order->get_edit_order_url();
		} else {
			$order_url = apply_filters( 'woocommerce_get_edit_order_url', get_admin_url( null, 'post.php?post=' . self::get_prop( $order, 'id' ) . '&action=edit' ), $order );
		}

		return $order_url;
	}


}
