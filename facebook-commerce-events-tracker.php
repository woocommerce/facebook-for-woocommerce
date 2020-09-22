<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use SkyVerge\WooCommerce\Facebook\Events\Event;
use SkyVerge\WooCommerce\PluginFramework\v5_5_4 as Framework;

if ( ! class_exists( 'WC_Facebookcommerce_EventsTracker' ) ) :

	if ( ! class_exists( 'WC_Facebookcommerce_Utils' ) ) {
		include_once 'includes/fbutils.php';
	}

	if ( ! class_exists( 'WC_Facebookcommerce_Pixel' ) ) {
		include_once 'facebook-commerce-pixel-event.php';
	}

	if ( ! class_exists( 'AAMSettings' ) ) {
		include_once 'includes/Events/AAMSettings.php';
	}

	class WC_Facebookcommerce_EventsTracker {
		private $pixel;
		private static $isEnabled = true;
		const FB_PRIORITY_HIGH    = 2;
		const FB_PRIORITY_LOW     = 11;

		/** @var Event search event instance */
		private $search_event;
		/** @var array with events tracked */
		private $tracked_events;
		/** @var AAMSettings aam settings instance, used to filter advanced matching fields*/
		private $aam_settings;

		public function __construct( $user_info, $aam_settings ) {
			$this->pixel = new WC_Facebookcommerce_Pixel( $user_info );
			$this->aam_settings = $aam_settings;
			$this->tracked_events = array();

			add_action( 'wp_head', array( $this, 'apply_filters' ) );

			// Pixel Tracking Hooks
			add_action(
				'wp_head',
				array( $this, 'inject_base_pixel' )
			);
			add_action(
				'wp_footer',
				array( $this, 'inject_base_pixel_noscript' )
			);

			// ViewContent for individual products
			add_action( 'woocommerce_after_single_product', [ $this, 'inject_view_content_event' ] );

			add_action(
				'woocommerce_after_shop_loop',
				array( $this, 'inject_view_category_event' )
			);
			add_action(
				'pre_get_posts',
				array( $this, 'inject_search_event' )
			);

			// AddToCart events
			add_action( 'woocommerce_add_to_cart', [ $this, 'inject_add_to_cart_event' ], 40, 4 );
			// AddToCart while AJAX is enabled
			add_action( 'woocommerce_ajax_added_to_cart', [ $this, 'add_filter_for_add_to_cart_fragments' ] );
			// AddToCart while using redirect to cart page
			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				add_filter( 'woocommerce_add_to_cart_redirect', [ $this, 'set_last_product_added_to_cart_upon_redirect' ], 10, 2 );
				add_action( 'woocommerce_ajax_added_to_cart',   [ $this, 'set_last_product_added_to_cart_upon_ajax_redirect' ] );
				add_action( 'woocommerce_after_cart',           [ $this, 'inject_add_to_cart_redirect_event' ], 10, 2 );
			}

			// InitiateCheckout events
			add_action( 'woocommerce_after_checkout_form', [ $this, 'inject_initiate_checkout_event' ] );
			// Purchase and Subscribe events
			add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'inject_purchase_event' ] );
			add_action( 'woocommerce_thankyou',                   [ $this, 'inject_purchase_event' ], 40 );

			// TODO move this in some 3rd party plugin integrations handler at some point {FN 2020-03-20}
			add_action( 'wpcf7_contact_form', [ $this, 'inject_lead_event_hook' ], self::FB_PRIORITY_LOW );
		}

		public function apply_filters() {
			self::$isEnabled = apply_filters(
				'facebook_for_woocommerce_integration_pixel_enabled',
				self::$isEnabled
			);
		}


		/**
		 * Prints the base JavaScript pixel code.
		 */
		public function inject_base_pixel() {

			if ( self::$isEnabled ) {
				// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				echo $this->pixel->pixel_base_code();
			}
		}


		/**
		 * Prints the base <noscript> pixel code.
		 *
		 * This is necessary to avoid W3 validation errors.
		 */
		public function inject_base_pixel_noscript() {

			if ( self::$isEnabled ) {
				// phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
				echo $this->pixel->pixel_base_code_noscript();
			}
		}


		/**
		 * Triggers ViewCategory for product category listings
		 */
		public function inject_view_category_event() {
			global $wp_query;

			if ( ! self::$isEnabled || ! is_product_category() ) {
				return;
			}

			$products = array_values(
				array_map(
					function( $item ) {
						return wc_get_product( $item->ID );
					},
					$wp_query->posts
				)
			);

			// if any product is a variant, fire the pixel with
			// content_type: product_group
			$content_type = 'product';
			$product_ids  = [];
			$contents     = [];

			foreach ( $products as $product ) {

				if ( ! $product ) {
					continue;
				}

				$contents[] = [
					'id'       => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ),
					'quantity' => 1, // consider category results a quantity of 1
				];

				$product_ids = array_merge(
					$product_ids,
					WC_Facebookcommerce_Utils::get_fb_content_ids( $product )
				);
				if ( WC_Facebookcommerce_Utils::is_variable_type( $product->get_type() ) ) {
					$content_type = 'product_group';
				}
			}

			$categories = WC_Facebookcommerce_Utils::get_product_categories( get_the_ID() );

			$event_name = 'ViewCategory';
			$event_data = [
				'event_name' => $event_name,
				'custom_data' => [
					'content_name'     => $categories['name'],
					'content_category' => $categories['categories'],
					'content_ids'      => json_encode( array_slice( $product_ids, 0, 10 ) ),
					'content_type'     => $content_type,
					'contents'         => $contents,
				],
				'user_data' => $this->pixel->get_user_info()
			];

			$event = new Event( $event_data );

			$this->send_api_event( $event );

			$event_data['event_id'] = $event->get_id();

			$this->pixel->inject_event( $event_name, $event_data, 'trackCustom' );
		}

		/**
		 * Triggers Search for result pages (deduped)
		 *
		 * @internal
		 */
		public function inject_search_event() {

			if ( ! self::$isEnabled ) {
				return;
			}

			if ( ! is_admin() && is_search() && '' !== get_search_query() && 'product' === get_query_var( 'post_type' ) ) {

				if ( $this->pixel->is_last_event( 'Search' ) ) {
					return;
				}

				// needs to run before wc_template_redirect, normally hooked with priority 10
				add_action( 'template_redirect',            [ $this, 'send_search_event' ], 5 );
				add_action( 'woocommerce_before_shop_loop', [ $this, 'actually_inject_search_event' ] );
			}
		}


		/**
		 * Sends a server-side Search event.
		 *
		 * @internal
		 *
		 * @since 2.0.0
		 */
		public function send_search_event() {

			$this->send_api_event( $this->get_search_event() );
		}


		/**
		 * Creates an Event instance to track a search request.
		 *
		 * The event instance is stored in memory to return a single instance per request.
		 *
		 * @since 2.0.0
		 *
		 * @return Event
		 */
		private function get_search_event() {
			global $wp_query;

			if ( null === $this->search_event ) {

				// if any product is a variant, fire the pixel with content_type: product_group
				$content_type = 'product';
				$product_ids  = [];
				$contents     = [];
				$total_value  = 0.00;

				foreach ( $wp_query->posts as $post ) {

					$product = wc_get_product( $post );

					if ( ! $product instanceof \WC_Product ) {
						continue;
					}

					$product_ids = array_merge( $product_ids, WC_Facebookcommerce_Utils::get_fb_content_ids( $product ) );

					$contents[] = [
						'id'       => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ),
						'quantity' => 1, // consider the search results a quantity of 1
					];

					$total_value += (float) $product->get_price();

					if ( WC_Facebookcommerce_Utils::is_variable_type( $product->get_type() ) ) {
						$content_type = 'product_group';
					}
				}

				$event_data = [
					'event_name'  => 'Search',
					'custom_data' => [
						'content_type'  => $content_type,
						'content_ids'   => json_encode( array_slice( $product_ids, 0, 10 ) ),
						'contents'      => $contents,
						'search_string' => get_search_query(),
						'value'         => Framework\SV_WC_Helper::number_format( $total_value ),
						'currency'      => get_woocommerce_currency(),
					],
					'user_data' => $this->pixel->get_user_info()
				];

				$this->search_event = new Event( $event_data );
			}

			return $this->search_event;
		}


		/**
		 * Injects a Search event on result pages.
		 *
		 * @internal
		 */
		public function actually_inject_search_event() {

			$event = $this->get_search_event();

			$this->pixel->inject_event( $event->get_name(), [
				'event_id'    => $event->get_id(),
				'event_name'  => $event->get_name(),
				'custom_data' => $event->get_custom_data(),
			] );
		}


		/**
		 * Triggers ViewContent event on product pages
		 *
		 * @internal
		 */
		public function inject_view_content_event() {
			global $post;

			if ( ! self::$isEnabled || ! isset( $post->ID ) ) {
				return;
			}

			$product = wc_get_product( $post->ID );

			if ( ! $product instanceof \WC_Product ) {
				return;
			}

			// if product is variable or grouped, fire the pixel with content_type: product_group
			if ( $product->is_type( [ 'variable', 'grouped' ] ) ) {
				$content_type = 'product_group';
			} else {
				$content_type = 'product';
			}

			$categories = \WC_Facebookcommerce_Utils::get_product_categories( $product->get_id() );

			$event_data = [
				'event_name'  => 'ViewContent',
				'custom_data' => [
					'content_name'     => $product->get_title(),
					'content_ids'      => wp_json_encode( \WC_Facebookcommerce_Utils::get_fb_content_ids( $product ) ),
					'content_type'     => $content_type,
					'contents'         => wp_json_encode(
						[
							[
								'id'       => \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product ),
								'quantity' => 1,
							]
						]
					),
					'content_category' => $categories['name'],
					'value'            => $product->get_price(),
					'currency'         => get_woocommerce_currency(),
				],
				'user_data' => $this->pixel->get_user_info(),
			];

			$event = new Event( $event_data );

			$this->send_api_event( $event );

			$event_data['event_id'] = $event->get_id();

			$this->pixel->inject_event( 'ViewContent', $event_data );
		}


		/**
		 * Triggers an AddToCart event when a product is added to cart.
		 *
		 * @internal
		 *
		 * @param string $cart_item_key the cart item key
		 * @param int $product_id the product identifier
		 * @param int $quantity the added product quantity
		 * @param int $variation_id the product variation identifier
		 */
		public function inject_add_to_cart_event( $cart_item_key, $product_id, $quantity, $variation_id ) {

			// bail if pixel tracking disabled or invalid variables
			if ( ! self::$isEnabled || ! $product_id || ! $quantity ) {
				return;
			}

			$product = wc_get_product( $variation_id ?: $product_id );

			// bail if invalid product or error
			if ( ! $product instanceof \WC_Product ) {
				return;
			}

			$event_data = [
				'event_name'  => 'AddToCart',
				'custom_data' => [
					'content_ids'  => $this->get_cart_content_ids(),
					'content_name' => $this->get_cart_content_names(),
					'content_type' => 'product',
					'contents'     => $this->get_cart_contents(),
					'value'        => $this->get_cart_total(),
					'currency'     => get_woocommerce_currency(),
				],
			];

			$event = new SkyVerge\WooCommerce\Facebook\Events\Event( $event_data );

			$this->send_api_event( $event );

			// send the event ID to prevent duplication
			$event_data['event_id'] = $event->get_id();

			// store the ID in the session to be sent in AJAX JS event tracking as well
			WC()->session->set( 'facebook_for_woocommerce_add_to_cart_event_id', $event->get_id() );

			$this->pixel->inject_event( 'AddToCart', $event_data );
		}


		/**
		 * Setups a filter to add an add to cart fragment whenever a product is added to the cart through Ajax.
		 *
		 * @see \WC_Facebookcommerce_EventsTracker::add_add_to_cart_event_fragment
		 *
		 * @internal
		 *
		 * @since 1.10.2
		 */
		public function add_filter_for_add_to_cart_fragments() {

			if ( 'no' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'add_add_to_cart_event_fragment' ] );
			}
		}


		/**
		 * Adds an add to cart fragment to trigger an AddToCart event.
		 *
		 * @internal
		 *
		 * @since 1.10.2
		 *
		 * @param array $fragments add to cart fragments
		 * @return array
		 */
		public function add_add_to_cart_event_fragment( $fragments ) {

			if ( self::$isEnabled ) {

				$params = [
					'content_ids'  => $this->get_cart_content_ids(),
					'content_name' => $this->get_cart_content_names(),
					'content_type' => 'product',
					'contents'     => $this->get_cart_contents(),
					'value'        => $this->get_cart_total(),
					'currency'     => get_woocommerce_currency(),
				];

				// send the event ID to prevent duplication
				if ( ! empty ( $event_id = WC()->session->get( 'facebook_for_woocommerce_add_to_cart_event_id' ) ) ) {

					$params['event_id'] = $event_id;
				}

				$script = $this->pixel->get_event_script( 'AddToCart', $params );

				$fragments['div.wc-facebook-pixel-event-placeholder'] = '<div class="wc-facebook-pixel-event-placeholder">' . $script . '</div>';
			}

			return $fragments;
		}


		/**
		 * Setups a filter to add an add to cart fragment to trigger an AddToCart event on added_to_cart JS event.
		 *
		 * This method is used by code snippets and should not be removed.
		 *
		 * @see \WC_Facebookcommerce_EventsTracker::add_conditional_add_to_cart_event_fragment
		 *
		 * @internal
		 *
		 * @since 1.10.2
		 */
		public function add_filter_for_conditional_add_to_cart_fragment() {

			if ( 'no' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'add_conditional_add_to_cart_event_fragment' ] );
			}
		}


		/**
		 * Adds an add to cart fragment to trigger an AddToCart event on added_to_cart JS event.
		 *
		 * @internal
		 *
		 * @since 1.10.2
		 *
		 * @param array $fragments add to cart fragments
		 * @return array
		 */
		public function add_conditional_add_to_cart_event_fragment( $fragments ) {

			if ( self::$isEnabled ) {

				$params = [
					'content_ids'  => $this->get_cart_content_ids(),
					'content_name' => $this->get_cart_content_names(),
					'content_type' => 'product',
					'contents'     => $this->get_cart_contents(),
					'value'        => $this->get_cart_total(),
					'currency'     => get_woocommerce_currency(),
				];

				// send the event ID to prevent duplication
				if ( ! empty ( $event_id = WC()->session->get( 'facebook_for_woocommerce_add_to_cart_event_id' ) ) ) {

					$params['event_id'] = $event_id;
				}

				$script = $this->pixel->get_conditional_one_time_event_script( 'AddToCart', $params, 'added_to_cart' );

				$fragments['div.wc-facebook-pixel-event-placeholder'] = '<div class="wc-facebook-pixel-event-placeholder">' . $script . '</div>';
			}

			return $fragments;
		}


		/**
		 * Sends a JSON response with the JavaScript code to track an AddToCart event.
		 *
		 * @internal
		 * @deprecated since 1.10.2
		 */
		public function inject_ajax_add_to_cart_event() {

			wc_deprecated_function( __METHOD__, '1.10.2' );
		}


		/**
		 * Sets last product added to cart to session when adding to cart a product and redirection to cart is enabled.
		 *
		 * @internal
		 *
		 * @since 1.10.2
		 *
		 * @param string $redirect URL redirecting to (usually cart)
		 * @param null|\WC_Product $product the product just added to the cart
		 * @return string
		 */
		public function set_last_product_added_to_cart_upon_redirect( $redirect, $product = null ) {

			if ( $product instanceof \WC_Product ) {
				WC()->session->set( 'facebook_for_woocommerce_last_product_added_to_cart', $product->get_id() );
			} elseif ( isset( $_GET['add-to-cart'] ) && is_numeric( $_GET['add-to-cart'] ) ) {
				WC()->session->set( 'facebook_for_woocommerce_last_product_added_to_cart', (int) $_GET['add-to-cart'] );
			}

			return $redirect;
		}


		/**
		 * Sets last product added to cart to session when adding a product to cart from an archive page and both AJAX adding and redirection to cart are enabled.
		 *
		 * @internal
		 *
		 * @since 1.10.2
		 *
		 * @param null|int $product_id the ID of the product just added to the cart
		 */
		public function set_last_product_added_to_cart_upon_ajax_redirect( $product_id = null ) {

			if ( ! $product_id ) {
				facebook_for_woocommerce()->log( 'Cannot record AddToCart event because the product cannot be determined. Backtrace: ' . print_r( wp_debug_backtrace_summary(), true ) );
				return;
			}

			$product = wc_get_product( $product_id );

			if ( $product instanceof \WC_Product ) {
				WC()->session->set( 'facebook_for_woocommerce_last_product_added_to_cart', $product->get_id() );
			}
		}


		/**
		 * Triggers an AddToCart event when redirecting to the cart page.
		 *
		 * @internal
		 */
		public function inject_add_to_cart_redirect_event() {

			if ( ! self::$isEnabled ) {
				return;
			}

			$last_product_id = WC()->session->get( 'facebook_for_woocommerce_last_product_added_to_cart', 0 );

			if ( $last_product_id > 0 ) {

				$this->inject_add_to_cart_event( '', $last_product_id, 1, 0 );

				WC()->session->set( 'facebook_for_woocommerce_last_product_added_to_cart', 0 );
			}
		}


		/**
		 * Triggers an InitiateCheckout event when customer reaches checkout page.
		 *
		 * @internal
		 */
		public function inject_initiate_checkout_event() {

			if ( ! self::$isEnabled || $this->pixel->is_last_event( 'InitiateCheckout' ) ) {
				return;
			}

			$event_name = 'InitiateCheckout';
			$event_data = [
				'event_name'  => $event_name,
				'custom_data' => [
					'num_items'    => $this->get_cart_num_items(),
					'content_ids'  => $this->get_cart_content_ids(),
					'content_name' => $this->get_cart_content_names(),
					'content_type' => 'product',
					'contents'     => $this->get_cart_contents(),
					'value'        => $this->get_cart_total(),
					'currency'     => get_woocommerce_currency(),
				],
				'user_data' => $this->pixel->get_user_info()
			];

			// if there is only one item in the cart, send its first category
			if ( ( $cart = WC()->cart ) && count( $cart->get_cart() ) === 1 ) {

				$item = current( $cart->get_cart() );

				if ( isset( $item['data'] ) && $item['data'] instanceof \WC_Product ) {

					$categories = \WC_Facebookcommerce_Utils::get_product_categories( $item['data']->get_id() );

					if ( ! empty( $categories['name'] ) ) {
						$event_data['custom_data']['content_category'] = $categories['name'];
					}
				}
			}

			$event = new Event( $event_data );

			$this->send_api_event( $event );

			$event_data['event_id'] = $event->get_id();

			$this->pixel->inject_event( $event_name, $event_data );
		}


		/**
		 * Triggers a Purchase event when checkout is completed.
		 *
		 * This may happen either when:
		 * - WooCommerce signals a payment transaction complete (most gateways)
		 * - Customer reaches Thank You page skipping payment (for gateways that do not require payment, e.g. Cheque, BACS, Cash on delivery...)
		 *
		 * The method checks if the event was not triggered already avoiding a duplicate.
		 * Finally, if the order contains subscriptions, it will also track an associated Subscription event.
		 *
		 * @internal
		 *
		 * @param int $order_id order identifier
		 */
		public function inject_purchase_event( $order_id ) {

			$event_name = 'Purchase';

			if ( ! self::$isEnabled || $this->pixel->is_last_event( $event_name ) ) {
				return;
			}

			$order = wc_get_order( $order_id );

			if ( ! $order ) {
				return;
			}

			// use an order meta to ensure an order is tracked with any payment method, also when the order is placed through AJAX
			$order_placed_meta = '_wc_' . facebook_for_woocommerce()->get_id() . '_order_placed';

			// use an order meta to ensure a Purchase event is not tracked multiple times
			$purchase_tracked_meta = '_wc_' . facebook_for_woocommerce()->get_id() . '_purchase_tracked';

			// when saving the order meta data: add a flag to mark the order tracked
			if ( 'woocommerce_checkout_update_order_meta' === current_action() ) {
				$order->update_meta_data( $order_placed_meta, 'yes' );
				$order->save_meta_data();
				return;
			}

			// bail if by the time we are on the thank you page the meta has not been set or we already tracked a Purchase event
			if ( 'yes' !== $order->get_meta( $order_placed_meta ) || 'yes' === $order->get_meta( $purchase_tracked_meta ) ) {
				return;
			}

			$content_type  = 'product';
			$contents      = [];
			$product_ids   = [ [] ];
			$product_names = [];

			foreach ( $order->get_items() as $item ) {

				if ( $product = isset( $item['product_id'] ) ? wc_get_product( $item['product_id'] ) : null ) {

					$product_ids[]   = \WC_Facebookcommerce_Utils::get_fb_content_ids( $product );
					$product_names[] = $product->get_name();

					if ( 'product_group' !== $content_type && $product->is_type( 'variable' ) ) {
						$content_type = 'product_group';
					}

					$quantity = $item->get_quantity();
					$content  = new \stdClass();

					$content->id       = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product );
					$content->quantity = $quantity;

					$contents[] = $content;
				}
			}
			// Advanced matching information is extracted from the order
			$event_data = [
				'event_name'  => $event_name,
				'custom_data' => [
					'content_ids'  => wp_json_encode( array_merge( ... $product_ids ) ),
					'content_name' => wp_json_encode( $product_names ),
					'contents'     => wp_json_encode( $contents ),
					'content_type' => $content_type,
					'value'        => $order->get_total(),
					'currency'     => get_woocommerce_currency(),
				],
				'user_data' => $this->get_user_data_from_billing_address($order)
			];

			$event = new Event( $event_data );

			$this->send_api_event( $event );

			$event_data['event_id'] = $event->get_id();

			$this->pixel->inject_event( $event_name, $event_data );

			$this->inject_subscribe_event( $order_id );

			// mark the order as tracked
			$order->update_meta_data( $purchase_tracked_meta, 'yes' );
			$order->save_meta_data();
		}


		/**
		 * Triggers a Subscribe event when a given order contains subscription products.
		 *
		 * @see \WC_Facebookcommerce_EventsTracker::inject_purchase_event()
		 *
		 * @internal
		 *
		 * @param int $order_id order identifier
		 */
		public function inject_subscribe_event( $order_id ) {

			if ( ! self::$isEnabled || ! function_exists( 'wcs_get_subscriptions_for_order' ) || $this->pixel->is_last_event( 'Subscribe' )  ) {
				return;
			}

			foreach ( wcs_get_subscriptions_for_order( $order_id ) as $subscription ) {

				// TODO consider 'StartTrial' event for free trial Subscriptions, which is the same as here (minus sign_up_fee) and tracks "when a person starts a free trial of a product or service" {FN 2020-03-20}
				$event_name = 'Subscribe';

				// TODO consider including (int|float) 'predicted_ltv': "Predicted lifetime value of a subscriber as defined by the advertiser and expressed as an exact value." {FN 2020-03-20}
				$event_data = [
					'event_name'  => $event_name,
					'custom_data' => [
						'sign_up_fee' => $subscription->get_sign_up_fee(),
						'value'       => $subscription->get_total(),
						'currency'    => get_woocommerce_currency(),
					],
					'user_data' => $this->pixel->get_user_info()
				];

				$event = new Event( $event_data );

				$this->send_api_event( $event );

				$event_data['event_id'] = $event->get_id();

				$this->pixel->inject_event( $event_name, $event_data );
			}
		}


		/**
		 * Triggers a Purchase event.
		 *
		 * Duplicate of {@see \WC_Facebookcommerce_EventsTracker::inject_purchase_event()}
		 *
		 * TODO remove this deprecated method by version 2.0.0 or by March 2020 {FN 2020-03-20}
		 *
		 * @internal
		 * @deprecated since 1.11.0
		 *
		 * @param int $order_id order identifier
		 */
		public function inject_gateway_purchase_event( $order_id ) {

			wc_deprecated_function( __METHOD__, '1.11.0', __CLASS__ . '::inject_purchase_event()' );

			$this->inject_purchase_event( $order_id );
		}


		/** Contact Form 7 Support **/
		public function inject_lead_event_hook() {
			add_action( 'wp_footer', array( $this, 'inject_lead_event' ), 11 );
		}

		public function inject_lead_event() {
			if ( ! is_admin() ) {
				$this->pixel->inject_conditional_event(
					'Lead',
					array(),
					'wpcf7submit',
					'{ em: event.detail.inputs.filter(ele => ele.name.includes("email"))[0].value }'
				);
			}
		}


		/**
		 * Sends an API event.
		 *
		 * @since 2.0.0
		 *
		 * @param Event $event event object
		 * @return bool
		 */
		protected function send_api_event( Event $event ) {
			$this->tracked_events[] = $event;

			try {

				facebook_for_woocommerce()->get_api()->send_pixel_events( facebook_for_woocommerce()->get_integration()->get_facebook_pixel_id(), [ $event ] );

				$success = true;

			} catch ( Framework\SV_WC_API_Exception $exception ) {

				$success = false;

				if ( facebook_for_woocommerce()->get_integration()->is_debug_mode_enabled() ) {
					facebook_for_woocommerce()->log( 'Could not send Pixel event: ' . $exception->getMessage() );
				}
			}

			return $success;
		}


		/**
		 * Gets the cart content items count.
		 *
		 * @since 1.10.2
		 *
		 * @return int
		 */
		private function get_cart_num_items() {

			return WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
		}


		/**
		 * Gets all content IDs from cart.
		 *
		 * @since 1.10.2
		 *
		 * @return string JSON data
		 */
		private function get_cart_content_ids() {

			$product_ids = [ [] ];

			if ( $cart = WC()->cart ) {

				foreach ( $cart->get_cart() as $item ) {

					if ( isset( $item['data'] ) && $item['data'] instanceof \WC_Product ) {

						$product_ids[] = \WC_Facebookcommerce_Utils::get_fb_content_ids( $item['data'] );
					}
				}
			}

			return wp_json_encode( array_unique( array_merge( ... $product_ids ) ) );
		}


		/**
		 * Gets all content names from cart.
		 *
		 * @since 2.0.0
		 *
		 * @return string JSON data
		 */
		private function get_cart_content_names() {

			$product_names = [];

			if ( $cart = WC()->cart ) {

				foreach ( $cart->get_cart() as $item ) {

					if ( isset( $item['data'] ) && $item['data'] instanceof \WC_Product ) {

						$product_names[] = $item['data']->get_name();
					}
				}
			}

			return wp_json_encode( array_unique( $product_names ) );
		}


		/**
		 * Gets the cart content data.
		 *
		 * @since 1.10.2
		 *
		 * @return string JSON data
		 */
		private function get_cart_contents() {

			$cart_contents = [];

			if ( $cart = WC()->cart ) {

				foreach ( $cart->get_cart() as $item ) {

					if ( ! isset( $item['data'], $item['quantity'] ) || ! $item['data'] instanceof \WC_Product ) {
						continue;
					}

					$content = new \stdClass();

					$content->id       = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $item['data'] );
					$content->quantity = $item['quantity'];

					$cart_contents[] = $content;
				}
			}

			return wp_json_encode( $cart_contents );
		}


		/**
		 * Gets the cart total.
		 *
		 * @return float|int
		 */
		private function get_cart_total() {

			return WC()->cart ? WC()->cart->total : 0;
		}

		/**
		 * Gets advanced matching information from a given order
		 *
		 * @return array
		 */
		private function get_user_data_from_billing_address($order) {
			if($this->aam_settings == null || !$this->aam_settings->get_enable_automatic_matching() ){
				return array();
			}
			$user_data= array();
			$user_data['fn'] = $order->get_billing_first_name();
			$user_data['ln'] = $order->get_billing_last_name();
			$user_data['em'] = $order->get_billing_email();
			$user_data['zp'] = $order->get_billing_postcode();
			$user_data['st'] = $order->get_billing_state();
			// We can use country as key because this information is for CAPI events only
			$user_data['country'] = $order->get_billing_country();
			$user_data['ct'] = $order->get_billing_city();
			$user_data['ph'] = $order->get_billing_phone();
			// The fields contain country, so we do not need to add a condition
			foreach ($user_data as $field => $value) {
				if( $value === null || $value === '' ||
					!in_array($field, $this->aam_settings->get_enabled_automatic_matching_fields())
				){
					unset($user_data[$field]);
				}
			}
			return $user_data;
		}

		/**
		 * Gets the events tracked by this object
		 *
		 * @return array
		 */
		public function get_tracked_events(){
			return $this->tracked_events;
		}

	}

endif;
