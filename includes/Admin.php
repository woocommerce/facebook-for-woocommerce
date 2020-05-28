<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

namespace SkyVerge\WooCommerce\Facebook;

use SkyVerge\WooCommerce\PluginFramework\v5_5_4\SV_WC_Helper;

defined( 'ABSPATH' ) or exit;

/**
 * Admin handler.
 *
 * @since 1.10.0
 */
class Admin {


	/** @var string the "sync and show" sync mode slug */
	const SYNC_MODE_SYNC_AND_SHOW = 'sync_and_show';

	/** @var string the "sync and show" sync mode slug */
	const SYNC_MODE_SYNC_AND_HIDE = 'sync_and_hide';

	/** @var string the "sync disabled" sync mode slug */
	const SYNC_MODE_SYNC_DISABLED = 'sync_disabled';


	/**
	 * Admin constructor.
	 *
	 * @since 1.10.0
	 */
	public function __construct() {

		// enqueue admin scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		$plugin = facebook_for_woocommerce();

		// only alter the admin UI if the plugin is connected to Facebook and ready to sync products
		if ( ! $plugin->get_connection_handler()->is_connected() || ! $plugin->get_integration()->get_product_catalog_id() ) {
			return;
		}

		// add a modal in admin product pages
		add_action( 'admin_footer', [ $this, 'render_modal_template' ] );
		// may trigger the modal to open to warn the merchant about a conflict with the current product terms
		add_action( 'admin_footer', [ $this, 'validate_product_excluded_terms' ] );

		// add admin notification in case of site URL change
		add_action( 'admin_notices', [ $this, 'validate_cart_url' ] );

		// add admin notice to inform that disabled products may need to be deleted manually
		add_action( 'admin_notices', [ $this, 'maybe_show_product_disabled_sync_notice' ] );

		// add admin notice if the user attempted to enable sync for virtual products using the bulk action
		add_action( 'admin_notices', [ $this, 'add_enabling_virtual_products_sync_notice' ] );
		// add admin notice to inform sync has been automatically disabled for virtual products
		add_action( 'admin_notices', [ $this, 'add_disabled_virtual_products_sync_notice' ] );

		// add columns for displaying Facebook sync enabled/disabled and catalog visibility status
		add_filter( 'manage_product_posts_columns',       [ $this, 'add_product_list_table_columns' ] );
		add_action( 'manage_product_posts_custom_column', [ $this, 'add_product_list_table_columns_content' ] );

		// add input to filter products by Facebook sync enabled
		add_action( 'restrict_manage_posts', [ $this, 'add_products_by_sync_enabled_input_filter' ], 40 );
		add_filter( 'request',               [ $this, 'filter_products_by_sync_enabled' ] );

		// add bulk actions to manage products sync
		add_filter( 'bulk_actions-edit-product',        [ $this, 'add_products_sync_bulk_actions' ], 40 );
		add_action( 'handle_bulk_actions-edit-product', [ $this, 'handle_products_sync_bulk_actions' ] );

		// add Product data tab
		add_filter( 'woocommerce_product_data_tabs',   [ $this, 'add_product_settings_tab' ] );
		add_action( 'woocommerce_product_data_panels', [ $this, 'add_product_settings_tab_content' ] );

		// add Variation edit fields
		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'add_product_variation_edit_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_product_variation_edit_fields' ], 10, 2 );
	}


	/**
	 * Enqueues admin scripts.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function enqueue_scripts() {
		global $current_screen;

		$modal_screens = [
			'product',
			'edit-product',
		];

		if ( isset( $current_screen->id ) ) {

			if ( in_array( $current_screen->id, $modal_screens, true ) || facebook_for_woocommerce()->is_plugin_settings() ) {

				// enqueue modal functions
				wp_enqueue_script( 'facebook-for-woocommerce-modal', plugins_url( '/facebook-for-woocommerce/assets/js/facebook-for-woocommerce-modal.min.js' ), [ 'jquery', 'wc-backbone-modal', 'jquery-blockui' ], \WC_Facebookcommerce::PLUGIN_VERSION );
			}

			if ( 'product' === $current_screen->id || 'edit-product' === $current_screen->id ) {

				wp_enqueue_style( 'facebook-for-woocommerce-products-admin', plugins_url( '/facebook-for-woocommerce/assets/css/admin/facebook-for-woocommerce-products-admin.css' ), [], \WC_Facebookcommerce::PLUGIN_VERSION );

				wp_enqueue_script( 'facebook-for-woocommerce-products-admin', plugins_url( '/facebook-for-woocommerce/assets/js/admin/facebook-for-woocommerce-products-admin.min.js' ), [ 'jquery', 'wc-backbone-modal', 'jquery-blockui', 'facebook-for-woocommerce-modal' ], \WC_Facebookcommerce::PLUGIN_VERSION );

				wp_localize_script( 'facebook-for-woocommerce-products-admin', 'facebook_for_woocommerce_products_admin', [
					'ajax_url'                                  => admin_url( 'admin-ajax.php' ),
					'set_product_visibility_nonce'              => wp_create_nonce( 'set-products-visibility' ),
					'set_product_sync_prompt_nonce'             => wp_create_nonce( 'set-product-sync-prompt' ),
					'set_product_sync_bulk_action_prompt_nonce' => wp_create_nonce( 'set-product-sync-bulk-action-prompt' ),
				] );
			}

			if ( facebook_for_woocommerce()->is_plugin_settings() ) {

				wp_enqueue_style( 'woocommerce_admin_styles' );
				wp_enqueue_script( 'wc-enhanced-select' );
			}
		}
	}


	/**
	 * Adds Facebook-related columns in the products edit screen.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param array $columns array of keys and labels
	 * @return array
	 */
	public function add_product_list_table_columns( $columns ) {

		$columns['facebook_sync'] = __( 'Facebook sync', 'facebook-for-woocommerce' );

		return $columns;
	}


	/**
	 * Outputs sync information for products in the edit screen.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param string $column the current column in the posts table
	 */
	public function add_product_list_table_columns_content( $column ) {
		global $post;

		if ( 'facebook_sync' !== $column ) {
			return;
		}

		$product = wc_get_product( $post );

		if ( $product && Products::product_should_be_synced( $product ) ) {

			if ( Products::is_product_visible( $product ) ) {
				esc_html_e( 'Sync and show', 'facebook-for-woocommerce' );
			} else {
				esc_html_e( 'Sync and hide', 'facebook-for-woocommerce' );
			}

		} else {

			esc_html_e( 'Do not sync', 'facebook-for-woocommerce' );
		}
	}


	/**
	 * Adds a dropdown input to let shop managers filter products by sync setting.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function add_products_by_sync_enabled_input_filter() {
		global $typenow;

		if ( 'product' !== $typenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$choice = isset( $_GET['fb_sync_enabled'] ) ? (string) sanitize_text_field( wp_unslash( $_GET['fb_sync_enabled'] ) ) : '';

		?>
		<select name="fb_sync_enabled">
			<option value="" <?php selected( $choice, '' ); ?>><?php esc_html_e( 'Filter by Facebook sync setting', 'facebook-for-woocommerce' ); ?></option>
			<option value="<?php echo self::SYNC_MODE_SYNC_AND_SHOW; ?>" <?php selected( $choice, self::SYNC_MODE_SYNC_AND_SHOW ); ?>><?php esc_html_e( 'Sync and show', 'facebook-for-woocommerce' ); ?></option>
			<option value="<?php echo self::SYNC_MODE_SYNC_AND_HIDE; ?>" <?php selected( $choice, self::SYNC_MODE_SYNC_AND_HIDE ); ?>><?php esc_html_e( 'Sync and hide', 'facebook-for-woocommerce' ); ?></option>
			<option value="<?php echo self::SYNC_MODE_SYNC_DISABLED; ?>" <?php selected( $choice, self::SYNC_MODE_SYNC_DISABLED ); ?>><?php esc_html_e( 'Do not sync', 'facebook-for-woocommerce' ); ?></option>
		</select>
		<?php
	}


	/**
	 * Filters products by Facebook sync setting.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param array $query_vars product query vars for the edit screen
	 * @return array
	 */
	public function filter_products_by_sync_enabled( $query_vars ) {

		$valid_values = [
			self::SYNC_MODE_SYNC_AND_SHOW,
			self::SYNC_MODE_SYNC_AND_HIDE,
			self::SYNC_MODE_SYNC_DISABLED,
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['fb_sync_enabled'] ) && in_array( $_REQUEST['fb_sync_enabled'], $valid_values, true ) ) {

			// store original meta query
			$original_meta_query = ! empty( $query_vars['meta_query'] ) ? $query_vars['meta_query'] : [];

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filter_value = $_REQUEST['fb_sync_enabled'];

			// by default use an "AND" clause if multiple conditions exist for a meta query
			if ( ! empty( $query_vars['meta_query'] ) ) {
				$query_vars['meta_query']['relation'] = 'AND';
			} else {
				$query_vars['meta_query'] = [];
			}

			if ( self::SYNC_MODE_SYNC_AND_SHOW === $filter_value ) {

				// when checking for products with sync enabled we need to check both "yes" and meta not set, this requires adding an "OR" clause
				$query_vars = $this->add_query_vars_to_find_products_with_sync_enabled( $query_vars );

				// only get visible products (both "yes" and meta not set)
				$query_vars = $this->add_query_vars_to_find_visible_products( $query_vars );

				// since we record enabled status and visibility on child variations, we need to query variable products found for their children to exclude them from query results
				$exclude_products = [];
				$found_ids        = get_posts( array_merge( $query_vars, [ 'fields' => 'ids' ] ) );
				$found_products   = empty( $found_ids ) ? [] : wc_get_products( [
					'limit'   => -1,
					'type'    => 'variable',
					'include' => $found_ids,
				] );

				/** @var \WC_Product[] $found_products */
				foreach ( $found_products as $product ) {

					if ( ! Products::is_sync_enabled_for_product( $product )
					     || ! Products::is_product_visible( $product ) ) {

						$exclude_products[] = $product->get_id();
					}
				}

				if ( ! empty( $exclude_products ) ) {
					if ( ! empty( $query_vars['post__not_in'] ) ) {
						$query_vars['post__not_in'] = array_merge( $query_vars['post__not_in'], $exclude_products );
					} else {
						$query_vars['post__not_in'] = $exclude_products;
					}
				}

			} elseif ( self::SYNC_MODE_SYNC_AND_HIDE === $filter_value ) {

				// when checking for products with sync enabled we need to check both "yes" and meta not set, this requires adding an "OR" clause
				$query_vars = $this->add_query_vars_to_find_products_with_sync_enabled( $query_vars );

				// only get hidden products
				$query_vars = $this->add_query_vars_to_find_hidden_products( $query_vars );

				// since we record enabled status and visibility on child variations, we need to query variable products found for their children to exclude them from query results
				$exclude_products = [];
				$found_ids        = get_posts( array_merge( $query_vars, [ 'fields' => 'ids' ] ) );
				$found_products   = empty( $found_ids ) ? [] : wc_get_products( [
					'limit'   => -1,
					'type'    => 'variable',
					'include' => $found_ids,
				] );

				/** @var \WC_Product[] $found_products */
				foreach ( $found_products as $product ) {

					if ( ! Products::is_sync_enabled_for_product( $product )
					     || Products::is_product_visible( $product ) ) {

						$exclude_products[] = $product->get_id();
					}
				}

				if ( ! empty( $exclude_products ) ) {
					if ( ! empty( $query_vars['post__not_in'] ) ) {
						$query_vars['post__not_in'] = array_merge( $query_vars['post__not_in'], $exclude_products );
					} else {
						$query_vars['post__not_in'] = $exclude_products;
					}
				}

				// for the same reason, we also need to include variable products with hidden children
				$include_products  = [];
				$hidden_variations = get_posts( [
					'limit'      => -1,
					'post_type'  => 'product_variation',
					'meta_query' => [
						'key'   => Products::VISIBILITY_META_KEY,
						'value' => 'no',
					]
				] );

				/** @var \WP_Post[] $hidden_variations */
				foreach ( $hidden_variations as $variation_post ) {

					$variable_product = wc_get_product( $variation_post->post_parent );

					// we need this check because we only want products with ALL variations hidden
					if ( Products::is_sync_enabled_for_product( $variable_product )
					     && ! Products::is_product_visible( $variable_product ) ) {

						$include_products[] = $variable_product->get_id();
					}
				}

			} else {

				// self::SYNC_MODE_SYNC_DISABLED

				$integration             = facebook_for_woocommerce()->get_integration();
				$excluded_categories_ids = $integration ? $integration->get_excluded_product_category_ids() : [];
				$excluded_tags_ids       = $integration ? $integration->get_excluded_product_tag_ids() : [];

				// instead of handling the categories/tags, we will exclude products that have sync enabled
				if ( $excluded_categories_ids || $excluded_tags_ids ) {

					// find the IDs of products that have sync enabled
					$products_query_vars = [
						'post_type'              => 'product',
						'post_status'            => ! empty( $query_vars['post_status'] ) ? $query_vars['post_status'] : 'any',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'fields'                 => 'ids',
						'nopaging'               => true,
					];

					$products_query_vars = $this->add_query_vars_to_find_products_with_sync_enabled( $products_query_vars );

					// exclude products that have sync enabled from the current query
					$query_vars['post__not_in'] = get_posts( $products_query_vars );

					$found_ids = [];

				} else {

					$query_vars['meta_query'][] = [
						'key'   => Products::SYNC_ENABLED_META_KEY,
						'value' => 'no',
					];

					$found_ids = get_posts( array_merge( $query_vars, [ 'fields' => 'ids' ] ) );
				}

				// since we record enabled status and visibility on child variations,
				// we need to include variable products with excluded children
				$excluded_variations = get_posts( [
					'limit'      => - 1,
					'post_type'  => 'product_variation',
					'meta_query' => [
						'key'   => Products::SYNC_ENABLED_META_KEY,
						'value' => 'no',
					]
				] );

				/** @var \WP_Post[] $excluded_variations */
				foreach ( $excluded_variations as $variation_post ) {

					$variable_product = wc_get_product( $variation_post->post_parent );

					// we need this check because we only want products with ALL variations excluded
					if ( ! Products::is_sync_enabled_for_product( $variable_product ) ) {

						$include_products[] = $variable_product->get_id();
					}
				}
			}

			if ( ! empty( $include_products ) ) {

				// we are going to query by ID, so we want to include all the IDs from before
				$include_products = array_unique( array_merge( $found_ids, $include_products ) );

				if ( ! empty( $query_vars['post__in'] ) ) {
					$query_vars['post__in'] = array_merge( $query_vars['post__in'], $include_products );
				} else {
					$query_vars['post__in'] = $include_products;
				}

				// remove sync enabled and visibility meta queries
				if ( ! empty( $original_meta_query ) ) {
					$query_vars['meta_query'] = $original_meta_query;
				} else {
					unset( $query_vars['meta_query'] );
				}
			}
		}

		return $query_vars;
	}


	/**
	 * Adds query vars to limit the results to products that have sync enabled.
	 *
	 * @since 1.10.0
	 *
	 * @param array $query_vars
	 * @return array
	 */
	private function add_query_vars_to_find_products_with_sync_enabled( array $query_vars ) {

		$meta_query = [
			'relation' => 'OR',
			[
				'key'   => Products::SYNC_ENABLED_META_KEY,
				'value' => 'yes',
			],
			[
				'key'     => Products::SYNC_ENABLED_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];

		if ( empty( $query_vars['meta_query'] ) ) {

			$query_vars['meta_query'] = $meta_query;

		} elseif ( is_array( $query_vars['meta_query'] ) ) {

			$original_meta_query      = $query_vars['meta_query'];
			$query_vars['meta_query'] = [
				'relation' => 'AND',
				$original_meta_query,
				$meta_query,
			];
		}

		// check whether the product belongs to an excluded product category or tag
		$query_vars = $this->maybe_add_tax_query_for_excluded_taxonomies( $query_vars );

		return $query_vars;
	}


	/**
	 * Adds a tax query to filter out products in excluded product categories and product tags.
	 *
	 * @since 1.10.0
	 *
	 * @param array $query_vars product query vars for the edit screen
	 * @return array
	 */
	private function maybe_add_tax_query_for_excluded_taxonomies( $query_vars ) {

		$integration = facebook_for_woocommerce()->get_integration();

		if ( $integration ) {

			$tax_query               = [];
			$excluded_categories_ids = $integration->get_excluded_product_category_ids();

			if ( $excluded_categories_ids ) {
				$tax_query[] = [
					'taxonomy' => 'product_cat',
					'terms'    => $excluded_categories_ids,
					'field'    => 'term_id',
					'operator' => 'NOT IN',
				];
			}

			$excluded_tags_ids = $integration->get_excluded_product_tag_ids();

			if ( $excluded_tags_ids ) {
				$tax_query[] = [
					'taxonomy' => 'product_tag',
					'terms'    => $excluded_tags_ids,
					'field'    => 'term_id',
					'operator' => 'NOT IN',
				];
			}

			if ( $tax_query && empty( $query_vars['tax_query'] ) ) {
				$query_vars['tax_query'] = $tax_query;
			} elseif ( $tax_query && is_array( $query_vars ) ) {
				$query_vars['tax_query'][] = $tax_query;
			}
		}

		return $query_vars;
	}


	/**
	 * Adds query vars to limit the results to visible products.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $query_vars
	 * @return array
	 */
	private function add_query_vars_to_find_visible_products( array $query_vars ) {

		$visibility_meta_query = [
			'relation' => 'OR',
			[
				'key'   => Products::VISIBILITY_META_KEY,
				'value' => 'yes',
			],
			[
				'key'     => Products::VISIBILITY_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];

		if ( empty( $query_vars['meta_query'] ) ) {

			$query_vars['meta_query'] = $visibility_meta_query;

		} elseif ( is_array( $query_vars['meta_query'] ) ) {

			$enabled_meta_query = $query_vars['meta_query'];
			$query_vars['meta_query'] = [
				'relation' => 'AND',
				$enabled_meta_query,
				$visibility_meta_query,
			];
		}

		return $query_vars;
	}


	/**
	 * Adds query vars to limit the results to hidden products.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param array $query_vars
	 * @return array
	 */
	private function add_query_vars_to_find_hidden_products( array $query_vars ) {

		$visibility_meta_query = [
			'key'   => Products::VISIBILITY_META_KEY,
			'value' => 'no',
		];

		if ( empty( $query_vars['meta_query'] ) ) {

			$query_vars['meta_query'] = $visibility_meta_query;

		} elseif ( is_array( $query_vars['meta_query'] ) ) {

			$enabled_meta_query = $query_vars['meta_query'];
			$query_vars['meta_query'] = [
				'relation' => 'AND',
				$enabled_meta_query,
				$visibility_meta_query,
			];
		}

		return $query_vars;
	}


	/**
	 * Adds bulk actions in the products edit screen.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param array $bulk_actions array of bulk action keys and labels
	 * @return array
	 */
	public function add_products_sync_bulk_actions( $bulk_actions ) {

		$bulk_actions['facebook_include'] = __( 'Include in Facebook sync', 'facebook-for-woocommerce' );
		$bulk_actions['facebook_exclude'] = __( 'Exclude from Facebook sync', 'facebook-for-woocommerce' );

		return $bulk_actions;
	}


	/**
	 * Handles a Facebook product sync bulk action.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param string $redirect admin URL used by WordPress to redirect after performing the bulk action
	 * @return string
	 */
	public function handle_products_sync_bulk_actions( $redirect ) {

		// primary dropdown at the top of the list table
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_REQUEST['action'] ) && -1 !== (int) $_REQUEST['action'] ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : null;

		// secondary dropdown at the bottom of the list table
		if ( ! $action ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = isset( $_REQUEST['action2'] ) && -1 !== (int) $_REQUEST['action2'] ? sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) ) : null;
		}

		if ( $action && in_array( $action, [ 'facebook_include', 'facebook_exclude' ], true ) ) {

			$products = [];

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$product_ids = isset( $_REQUEST['post'] ) && is_array( $_REQUEST['post'] ) ? array_map( 'absint', $_REQUEST['post'] ) : [];

			if ( ! empty( $product_ids ) ) {

				$is_enabling_sync_virtual_products   = false;
				$is_enabling_sync_virtual_variations = false;

				foreach ( $product_ids as $product_id ) {

					if ( $product = wc_get_product( $product_id ) ) {

						if ( 'facebook_include' === $action ) {

							if ( $product->is_virtual() ) {

								$is_enabling_sync_virtual_products = true;

							} else {

								// products with virtual variations are also added to the list,
								// because they may have non-virtual variations as well
								$products[] = $product;

								if ( $product->is_type( 'variable' ) ) {

									// check if the product has virtual variations, to display notice
									foreach ( $product->get_children() as $variation_id ) {

										$variation = wc_get_product( $variation_id );

										if ( $variation && $variation->is_virtual() ) {

											$is_enabling_sync_virtual_variations = true;
											break;
										}
									}
								}
							}
						} else {

							// add the product to the list of products to disable sync from
							$products[] = $product;
						}
					}
				}

				// display notice if enabling sync for virtual products or variations
				if ( $is_enabling_sync_virtual_products || $is_enabling_sync_virtual_variations ) {

					$redirect = add_query_arg( [ 'enabling_virtual_products_sync' => 1 ], $redirect );
				}

				if ( 'facebook_include' === $action ) {

					Products::enable_sync_for_products( $products );

					$this->resync_products( $products );

				} elseif ( 'facebook_exclude' === $action ) {

					Products::disable_sync_for_products( $products );

					self::add_product_disabled_sync_notice( count( $products ) );
				}
			}
		}

		return $redirect;
	}


	/**
	 * Re-syncs the given products.
	 *
	 * @since 2.0.0
	 *
	 * @param \WC_Product $products
	 */
	private function resync_products( array $products ) {

		$integration = facebook_for_woocommerce()->get_integration();

		// re-sync each product
		foreach ( $products as $product ) {

			if ( $product->is_type( 'variable' ) ) {

				// create product group and schedule product variations to be synced in the background
				$integration->on_product_publish( $product->get_id() );

			} elseif ( $integration->product_should_be_synced( $product ) ) {

				// schedule simple products to be updated or deleted from the catalog in the background
				if ( Products::product_should_be_deleted( $product ) ) {
					facebook_for_woocommerce()->get_products_sync_handler()->delete_products( [ $product->get_id() ] );
				} else {
					facebook_for_woocommerce()->get_products_sync_handler()->create_or_update_products( [ $product->get_id() ] );
				}
			}
		}
	}


	/**
	 * Prints a notice on products page in case the current cart URL is not the original sync URL.
	 *
	 * @internal
	 *
	 * TODO: update this method to use the notice handler once we framework the plugin {CW 2020-01-09}
	 *
	 * @since 1.10.0
	 */
	public function validate_cart_url() {
		global $current_screen;

		if ( isset( $current_screen->id ) && in_array( $current_screen->id, [ 'edit-product', 'product' ], true ) ) :

			$cart_url = get_option( \WC_Facebookcommerce_Integration::FB_CART_URL, '' );

			if ( ! empty( $cart_url ) && $cart_url !== wc_get_cart_url() ) :

				?>
				<div class="notice notice-warning">
					<?php printf(
						/* translators: Placeholders: %1$s - Facebook for Woocommerce, %2$s - opening HTML <a> link tag, %3$s - closing HTML </a> link tag */
						'<p>' . esc_html__( '%1$s: One or more of your products is using a checkout URL that may be different than your shop checkout URL. %2$sRe-sync your products to update checkout URLs on Facebook%3$s.', 'facebook-for-woocommerce' ) . '</p>',
						'<strong>' . esc_html__( 'Facebook for WooCommerce', 'facebook-for-woocommerce' ) . '</strong>',
						'<a href="' . esc_url( facebook_for_woocommerce()->get_settings_url() ) . '">',
						'</a>'
					); ?>
				</div>
				<?php

			endif;

		endif;
	}


	/**
	 * Adds a transient so an informational notice is displayed on the next page load.
	 *
	 * @since 2.0.0-dev.1
	 *
	 * @param int $count number of products
	 */
	public static function add_product_disabled_sync_notice( $count = 1 ) {

		if ( ! facebook_for_woocommerce()->get_admin_notice_handler()->is_notice_dismissed( 'wc-' . facebook_for_woocommerce()->get_id_dasherized() . '-product-disabled-sync' ) ) {
			set_transient( 'wc_' . facebook_for_woocommerce()->get_id() . '_show_product_disabled_sync_notice_' . get_current_user_id(), $count, MINUTE_IN_SECONDS );
		}
	}


	/**
	 * Adds a message for after a product or set of products get excluded from sync.
	 *
	 * @since 2.0.0-dev.1
	 */
	public function maybe_show_product_disabled_sync_notice() {

		$transient_name = 'wc_' . facebook_for_woocommerce()->get_id() . '_show_product_disabled_sync_notice_' . get_current_user_id();
		$message_id     = 'wc-' . facebook_for_woocommerce()->get_id_dasherized() . '-product-disabled-sync';

		if ( ( $count = get_transient( $transient_name ) ) && ( SV_WC_Helper::is_current_screen( 'edit-product' ) || SV_WC_Helper::is_current_screen( 'product' ) ) ) {

			$message = sprintf(
				_n( '%1$sHeads up!%2$s If this product was previously visible in Facebook, you may need to %3$sdelete it from the Facebook catalog%4$s to completely hide it from customer view.', '%1$sHeads up!%2$s If these products were previously visible in Facebook, you may need to %3$sdelete them from the Facebook catalog%4$s to completely hide them from customer view.', $count, 'facebook-for-woocommerce' ),
				'<strong>', '</strong>',
				'<a href="https://www.facebook.com/business/help/428079314773256" target="_blank">', '</a>'
			);

			$message .= '<a class="button js-wc-plugin-framework-notice-dismiss">' . esc_html__( "Don't show this notice again", 'facebook-for-woocommerce' ) . '</a>';

			facebook_for_woocommerce()->get_admin_notice_handler()->add_admin_notice(
				$message,
				$message_id,
				[
					'dismissible'  => false, // we add our own dismiss button
					'notice_class' => 'notice-info',
				]
			);

			delete_transient( $transient_name );
		}
	}


	/**
	 * Prints a notice on products page to inform users that the virtual products selected for the Include bulk action will NOT have sync enabled.
	 *
	 * @internal
	 *
	 * @since 1.11.3-dev.2
	 */
	public function add_enabling_virtual_products_sync_notice() {
		global $current_screen;

		if ( isset( $_GET['enabling_virtual_products_sync'] ) && isset( $current_screen->id ) && 'edit-product' === $current_screen->id ) {

			facebook_for_woocommerce()->get_admin_notice_handler()->add_admin_notice(
				sprintf(
					/* translators: Placeholders: %1$s - opening HTML <strong> tag, %2$s - closing HTML </strong> tag, %3$s - opening HTML <a> tag, %4$s - closing HTML </a> tag */
					esc_html__( '%1$sHeads up!%2$s Facebook does not support selling virtual products, so we can\'t include virtual products in your catalog sync. %3$sClick here to read more about Facebook\'s policy%4$s.', 'facebook-for-woocommerce' ),
					'<strong>',
					'</strong>',
					'<a href="https://www.facebook.com/help/130910837313345" target="_blank">',
					'</a>'
				),
				'wc-' . facebook_for_woocommerce()->get_id_dasherized() . '-enabling-virtual-products-sync',
				[ 'notice_class' => 'notice-info' ]
			);
		}
	}


	/**
	 * Prints a notice to inform sync has been automatically disabled for virtual products.
	 *
	 * @internal
	 *
	 * @since 1.11.3-dev.2
	 */
	public function add_disabled_virtual_products_sync_notice() {

		if ( 'yes' === get_option( 'wc_facebook_sync_virtual_products_disabled', 'no' ) &&
		     'yes' !== get_option( 'wc_facebook_sync_virtual_products_disabled_skipped', 'no' ) ) {

			facebook_for_woocommerce()->get_admin_notice_handler()->add_admin_notice(
				sprintf(
					/* translators: Placeholders: %1$s - opening HTML <strong> tag, %2$s - closing HTML </strong> tag, %3$s - opening HTML <a> tag, %4$s - closing HTML </a> tag */
					esc_html__( '%1$sHeads up!%2$s Facebook does not support selling virtual products, so we have removed any previously synced virtual products from the catalog sync going forward. %3$sClick here to read more about Facebook\'s policy%4$s.', 'facebook-for-woocommerce' ),
					'<strong>',
					'</strong>',
					'<a href="https://www.facebook.com/help/130910837313345" target="_blank">',
					'</a>'
				),
				'wc-' . facebook_for_woocommerce()->get_id_dasherized() . '-disabled-virtual-products-sync',
				[ 'notice_class' => 'notice-info' ]
			);
		}
	}


	/**
	 * Adds a new tab to the Product edit page.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param array $tabs product tabs
	 * @return array
	 */
	public function add_product_settings_tab( $tabs ) {

		$tabs['fb_commerce_tab'] = [
			'label'  => __( 'Facebook', 'facebook-for-woocommerce' ),
			'target' => 'facebook_options',
			'class'  => [ 'show_if_simple', 'hide_if_virtual' ],
		];

		return $tabs;
	}


	/**
	 * Adds content to the new Facebook tab on the Product edit page.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function add_product_settings_tab_content() {
		global $post;

		// all products have sync enabled unless explicitly disabled
		$sync_enabled = 'no' !== get_post_meta( $post->ID, Products::SYNC_ENABLED_META_KEY, true );
		$is_visible = ( $visibility = get_post_meta( $post->ID, Products::VISIBILITY_META_KEY, true ) ) ? wc_string_to_bool( $visibility ) : true;

		$description  = get_post_meta( $post->ID, \WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, true );
		$price        = get_post_meta( $post->ID, \WC_Facebook_Product::FB_PRODUCT_PRICE, true );
		$image_source = get_post_meta( $post->ID, Products::PRODUCT_IMAGE_SOURCE_META_KEY, true );
		$image        = get_post_meta( $post->ID, \WC_Facebook_Product::FB_PRODUCT_IMAGE, true );

		if ( $sync_enabled ) {
			$sync_mode = $is_visible ? self::SYNC_MODE_SYNC_AND_SHOW : self::SYNC_MODE_SYNC_AND_HIDE;
		} else {
			$sync_mode = self::SYNC_MODE_SYNC_DISABLED;
		}

		// 'id' attribute needs to match the 'target' parameter set above
		?>
		<div id='facebook_options' class='panel woocommerce_options_panel'>
			<div class='options_group'>
				<?php

				woocommerce_wp_select( [
					'id'      => 'wc_facebook_sync_mode',
					'label'   => __( 'Facebook sync', 'facebook-for-woocommerce' ),
					'options' => [
						self::SYNC_MODE_SYNC_AND_SHOW => __( 'Sync and show in catalog', 'facebook-for-woocommerce' ),
						self::SYNC_MODE_SYNC_AND_HIDE => __( 'Sync and hide in catalog', 'facebook-for-woocommerce' ),
						self::SYNC_MODE_SYNC_DISABLED => __( 'Do not sync', 'facebook-for-woocommerce' ),
					],
					'value' => $sync_mode,
				] );

				woocommerce_wp_textarea_input( [
					'id'          => \WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION,
					'label'       => __( 'Facebook Description', 'facebook-for-woocommerce' ),
					'desc_tip'    => true,
					'description' => __( 'Custom (plain-text only) description for product on Facebook. If blank, product description will be used. If product description is blank, shortname will be used.', 'facebook-for-woocommerce' ),
					'cols'        => 40,
					'rows'        => 20,
					'value'       => $description,
					'class'       => 'enable-if-sync-enabled',
				] );

				woocommerce_wp_radio( [
					'id'            => 'fb_product_image_source',
					'label'         => __( 'Facebook Product Image', 'facebook-for-woocommerce' ),
					'desc_tip'      => true,
					'description'   => __( 'Choose the product image that should be synced to the Facebook catalog for this product. If using a custom image, please enter an absolute URL (e.g. https://domain.com/image.jpg).', 'facebook-for-woocommerce' ),
					'options'       => [
						Products::PRODUCT_IMAGE_SOURCE_PRODUCT => __( 'Use WooCommerce image', 'facebook-for-woocommerce' ),
						Products::PRODUCT_IMAGE_SOURCE_CUSTOM  => __( 'Use custom image', 'facebook-for-woocommerce' ),
					],
					'value'         => $image_source ?: Products::PRODUCT_IMAGE_SOURCE_PRODUCT,
					'class'         => 'short enable-if-sync-enabled js-fb-product-image-source',
					'wrapper_class' => 'fb-product-image-source-field',
				] );

				woocommerce_wp_text_input( [
					'id'          => \WC_Facebook_Product::FB_PRODUCT_IMAGE,
					'label'       => __( 'Custom Image URL', 'facebook-for-woocommerce' ),
					'value'       => $image,
					'class'       => sprintf( 'enable-if-sync-enabled product-image-source-field show-if-product-image-source-%s', Products::PRODUCT_IMAGE_SOURCE_CUSTOM ),
				] );

				woocommerce_wp_text_input( [
					'id'          => \WC_Facebook_Product::FB_PRODUCT_PRICE,
					'label'       => sprintf(
						/* translators: Placeholders %1$s - WC currency symbol */
						__( 'Facebook Price (%1$s)', 'facebook-for-woocommerce' ),
						get_woocommerce_currency_symbol()
					),
					'desc_tip'    => true,
					'description' => __( 'Custom price for product on Facebook. Please enter in monetary decimal (.) format without thousand separators and currency symbols. If blank, product price will be used.', 'facebook-for-woocommerce' ),
					'cols'        => 40,
					'rows'        => 60,
					'value'       => $price,
					'class'       => 'enable-if-sync-enabled',
				] );

				?>
			</div>
		</div>
		<?php
	}


	/**
	 * Outputs the Facebook settings fields for a single variation.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param int $index the index of the current variation
	 * @param array $variation_data unused
	 * @param \WC_Post $post the post type for the current variation
	 */
	public function add_product_variation_edit_fields( $index, $variation_data, $post ) {

		$variation = wc_get_product( $post );

		if ( ! $variation instanceof \WC_Product_Variation ) {
			return;
		}

		$parent = wc_get_product( $variation->get_parent_id() );

		if ( ! $parent instanceof \WC_Product ) {
			return;
		}

		$sync_enabled = 'no' !== $this->get_product_variation_meta( $variation, Products::SYNC_ENABLED_META_KEY, $parent );
		$is_visible = ( $visibility = $this->get_product_variation_meta( $variation, Products::VISIBILITY_META_KEY, $parent ) ) ? wc_string_to_bool( $visibility ) : true;

		$description  = $this->get_product_variation_meta( $variation, \WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $parent );
		$price        = $this->get_product_variation_meta( $variation, \WC_Facebook_Product::FB_PRODUCT_PRICE, $parent );
		$image_url    = $this->get_product_variation_meta( $variation, \WC_Facebook_Product::FB_PRODUCT_IMAGE, $parent );
		$image_source = $variation->get_meta( Products::PRODUCT_IMAGE_SOURCE_META_KEY );

		if ( $sync_enabled ) {
			$sync_mode = $is_visible ? self::SYNC_MODE_SYNC_AND_SHOW : self::SYNC_MODE_SYNC_AND_HIDE;
		} else {
			$sync_mode = self::SYNC_MODE_SYNC_DISABLED;
		}

		woocommerce_wp_select( [
			'id'      => "variable_facebook_sync_mode$index",
			'name'    => "variable_facebook_sync_mode[$index]",
			'label'   => __( 'Facebook sync', 'facebook-for-woocommerce' ),
			'options' => [
				self::SYNC_MODE_SYNC_AND_SHOW => __( 'Sync and show in catalog', 'facebook-for-woocommerce' ),
				self::SYNC_MODE_SYNC_AND_HIDE => __( 'Sync and hide in catalog', 'facebook-for-woocommerce' ),
				self::SYNC_MODE_SYNC_DISABLED => __( 'Do not sync', 'facebook-for-woocommerce' ),
			],
			'value'         => $sync_mode,
			'class'         => 'js-variable-fb-sync-toggle',
			'wrapper_class' => 'hide_if_variation_virtual form-row form-row-full',
		] );

		woocommerce_wp_textarea_input( [
			'id'            => sprintf( 'variable_%s%s', \WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $index ),
			'name'          => sprintf( "variable_%s[$index]", \WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION ),
			'label'         => __( 'Facebook Description', 'facebook-for-woocommerce' ),
			'desc_tip'      => true,
			'description'   => __( 'Custom (plain-text only) description for product on Facebook. If blank, product description will be used. If product description is blank, shortname will be used.', 'facebook-for-woocommerce' ),
			'cols'          => 40,
			'rows'          => 5,
			'value'         => $description,
			'class'         => 'enable-if-sync-enabled',
			'wrapper_class' => 'form-row form-row-full hide_if_variation_virtual',
		] );

		woocommerce_wp_radio( [
			'id'            => "variable_fb_product_image_source$index",
			'name'          => "variable_fb_product_image_source[$index]",
			'label'         => __( 'Facebook Product Image', 'facebook-for-woocommerce' ),
			'desc_tip'      => true,
			'description'   => __( 'Choose the product image that should be synced to the Facebook catalog for this product. If using a custom image, please enter an absolute URL (e.g. https://domain.com/image.jpg).', 'facebook-for-woocommerce' ),
			'options'       => [
				Products::PRODUCT_IMAGE_SOURCE_PRODUCT        => __( 'Use variation image', 'facebook-for-woocommerce' ),
				Products::PRODUCT_IMAGE_SOURCE_PARENT_PRODUCT => __( 'Use parent image', 'facebook-for-woocommerce' ),
				Products::PRODUCT_IMAGE_SOURCE_CUSTOM         => __( 'Use custom image', 'facebook-for-woocommerce' ),
			],
			'value'         => $image_source ?: Products::PRODUCT_IMAGE_SOURCE_PRODUCT,
			'class'         => 'enable-if-sync-enabled js-fb-product-image-source',
			'wrapper_class' => 'fb-product-image-source-field hide_if_variation_virtual',
		] );

		woocommerce_wp_text_input( [
			'id'            => sprintf( 'variable_%s%s', \WC_Facebook_Product::FB_PRODUCT_IMAGE, $index ),
			'name'          => sprintf( "variable_%s[$index]", \WC_Facebook_Product::FB_PRODUCT_IMAGE ),
			'label'         => __( 'Custom Image URL', 'facebook-for-woocommerce' ),
			'value'         => $image_url,
			'class'         => sprintf( 'enable-if-sync-enabled product-image-source-field show-if-product-image-source-%s', Products::PRODUCT_IMAGE_SOURCE_CUSTOM ),
			'wrapper_class' => 'form-row form-row-full hide_if_variation_virtual',
		] );

		woocommerce_wp_text_input( [
			'id'            => sprintf( 'variable_%s%s', \WC_Facebook_Product::FB_PRODUCT_PRICE, $index ),
			'name'          => sprintf( "variable_%s[$index]", \WC_Facebook_Product::FB_PRODUCT_PRICE ),
			'label'         => sprintf(
				/* translators: Placeholders %1$s - WC currency symbol */
				__( 'Facebook Price (%1$s)', 'facebook-for-woocommerce' ),
				get_woocommerce_currency_symbol()
			),
			'desc_tip'      => true,
			'description'   => __( 'Custom price for product on Facebook. Please enter in monetary decimal (.) format without thousand separators and currency symbols. If blank, product price will be used.', 'facebook-for-woocommerce' ),
			'value'         => wc_format_decimal( $price ),
			'class'         => 'enable-if-sync-enabled',
			'wrapper_class' => 'form-row form-full hide_if_variation_virtual',
		] );
	}


	/**
	 * Gets the stored value for the given meta of a product variation.
	 *
	 * If no value is found, we try to use the value stored in the parent product.
	 *
	 * @since 1.10.0
	 *
	 * @param \WC_Product_Variation $variation the product variation
	 * @param string $key the name of the meta to retrieve
	 * @param \WC_Product $parent the parent product
	 * @return mixed
	 */
	private function get_product_variation_meta( $variation, $key, $parent ) {

		$value = $variation->get_meta( $key );

		if ( '' === $value && $parent instanceof \WC_Product ) {
			$value = $parent->get_meta( $key );
		}

		return $value;
	}


	/**
	 * Saves the submitted Facebook settings for each variation.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 *
	 * @param int $variation_id the ID of the product variation being edited
	 * @param int $index the index of the current variation
	 */
	public function save_product_variation_edit_fields( $variation_id, $index ) {

		$variation = wc_get_product( $variation_id );

		if ( ! $variation instanceof \WC_Product_Variation ) {
			return;
		}

		$sync_mode    = isset( $_POST['variable_facebook_sync_mode'][ $index ] ) ? $_POST['variable_facebook_sync_mode'][ $index ] : self::SYNC_MODE_SYNC_DISABLED;
		$sync_enabled = self::SYNC_MODE_SYNC_DISABLED !== $sync_mode;

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( $sync_enabled && ! $variation->is_virtual() ) {

			Products::enable_sync_for_products( [ $variation ] );
			Products::set_product_visibility( $variation, self::SYNC_MODE_SYNC_AND_HIDE !== $sync_mode );

			$posted_param = 'variable_' . \WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION;
			$description  = isset( $_POST[ $posted_param ][ $index ] ) ? sanitize_text_field( wp_unslash( $_POST[ $posted_param ][ $index ] ) ) : null;

			$posted_param = 'variable_fb_product_image_source';
			$image_source = isset( $_POST[ $posted_param ][ $index ] ) ? sanitize_key( wp_unslash( $_POST[ $posted_param ][ $index ] ) ) : '';

			$posted_param = 'variable_' . \WC_Facebook_Product::FB_PRODUCT_IMAGE;
			$image_url    = isset( $_POST[ $posted_param ][ $index ] ) ? esc_url_raw( wp_unslash( $_POST[ $posted_param ][ $index ] ) ) : null;

			$posted_param = 'variable_' . \WC_Facebook_Product::FB_PRODUCT_PRICE;
			$price        = isset( $_POST[ $posted_param ][ $index ] ) ? wc_format_decimal( $_POST[ $posted_param ][ $index ] ) : '';

			$variation->update_meta_data( \WC_Facebookcommerce_Integration::FB_PRODUCT_DESCRIPTION, $description );
			$variation->update_meta_data( Products::PRODUCT_IMAGE_SOURCE_META_KEY, $image_source );
			$variation->update_meta_data( \WC_Facebook_Product::FB_PRODUCT_IMAGE, $image_url );
			$variation->update_meta_data( \WC_Facebook_Product::FB_PRODUCT_PRICE, $price );
			$variation->save_meta_data();

		} else {

			Products::disable_sync_for_products( [ $variation ] );

		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}


	/**
	 * Outputs a modal template in admin product pages.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function render_modal_template() {
		global $current_screen;

		// bail if not on the products, product edit, or settings screen
		if ( ! $current_screen || ! in_array( $current_screen->id, [ 'edit-product', 'product', 'woocommerce_page_wc-facebook' ], true ) ) {
			return;
		}

		?>
		<script type="text/template" id="tmpl-facebook-for-woocommerce-modal">
			<div class="wc-backbone-modal facebook-for-woocommerce-modal">
				<div class="wc-backbone-modal-content">
					<section class="wc-backbone-modal-main" role="main">
						<header class="wc-backbone-modal-header">
							<h1><?php esc_html_e( 'Facebook for WooCommerce', 'facebook-for-woocommerce' ); ?></h1>
							<button class="modal-close modal-close-link dashicons dashicons-no-alt">
								<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'facebook-for-woocommerce' ); ?></span>
							</button>
						</header>
						<article>{{{data.message}}}</article>
						<footer>
							<div class="inner">{{{data.buttons}}}</div>
						</footer>
					</section>
				</div>
			</div>
			<div class="wc-backbone-modal-backdrop modal-close"></div>
		</script>
		<?php
	}


	/**
	 * Maybe triggers the modal to open on the product edit screen on page load.
	 *
	 * If the product is set to be synced in Facebook, but belongs to a term that is set to be excluded, the modal prompts the merchant for action.
	 *
	 * @internal
	 *
	 * @since 1.10.0
	 */
	public function validate_product_excluded_terms() {
		global $current_screen, $post;

		if ( $post && $current_screen && $current_screen->id === 'product' ) :

			$product = wc_get_product( $post );

			if ( $product instanceof \WC_Product
			     && Products::is_sync_enabled_for_product( $product )
			     && Products::is_sync_excluded_for_product_terms( $product )
			) :

				?>
				<script type="text/javascript">
					jQuery( document ).ready( function( $ ) {

						var productID   = parseInt( $( 'input#post_ID' ).val(), 10 ),
							productTag  = $( 'textarea[name=\"tax_input[product_tag]\"]' ).val().split( ',' ),
							productCat  = [];

						$( '#taxonomy-product_cat input[name=\"tax_input[product_cat][]\"]:checked' ).each( function() {
							productCat.push( parseInt( $( this ).val(), 10 ) );
						} );

						$.post( facebook_for_woocommerce_products_admin.ajax_url, {
							action:      'facebook_for_woocommerce_set_product_sync_prompt',
							security:     facebook_for_woocommerce_products_admin.set_product_sync_prompt_nonce,
							sync_enabled: 'enabled',
							product:      productID,
							categories:   productCat,
							tags:         productTag
						}, function( response ) {

							if ( response && ! response.success ) {

								$( '#wc-backbone-modal-dialog .modal-close' ).trigger( 'click' );

								new $.WCBackboneModal.View( {
									target: 'facebook-for-woocommerce-modal',
									string: response.data
								} );
							}
						} );
					} );
				</script>
			<?php

			endif;

		endif;
	}


	/** Deprecated methods ********************************************************************************************/


	/**
	 * No-op: Prints a notice on products page to inform users about changes in product sync.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 * @deprecated 2.0.0-dev.1
	 */
	public function add_product_sync_delay_notice() {

		wc_deprecated_function( __METHOD__, '2.0.0-dev.1' );
	}


	/**
	 * No-op: Handles dismissed notices.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 * @deprecated 2.0.0-dev.1
	 *
	 * @param string $message_id the dismissed notice ID
	 * @param int $user_id the ID of the user the noticed was dismissed for
	 */
	public function handle_dismiss_notice( $message_id, $user_id = null ) {

		wc_deprecated_function( __METHOD__, '2.0.0-dev.1' );
	}


	/**
	 * No-op: Prints a notice on products page to inform users that catalog visibility settings were removed.
	 *
	 * @internal
	 *
	 * @since 1.11.0
	 * @deprecated 2.0.0-dev.1
	 */
	public function add_catalog_visibility_settings_removed_notice() {

		wc_deprecated_function( __METHOD__, '2.0.0-dev.1' );
	}


}
