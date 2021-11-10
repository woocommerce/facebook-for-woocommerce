<?php

/**
 * The edit functionality is provided by edit-tags.php?taxonomy=fb_product_set
 * this file extends the settings.
 */

namespace SkyVerge\WooCommerce\Facebook\ProductSets;

class SetsEdit {
	public function __construct() {
		$this->add_hooks();
	}

	private function add_hooks() {
		add_filter( 'manage_edit-fb_product_set_columns', array( $this, 'add_column_header' ), 20, 1 );
		add_action( 'manage_fb_product_set_custom_column', array( $this, 'add_column_values' ), 10, 3 );
		add_filter( 'pre_insert_term', array( $this, 'validate_term_before_insert' ), 10, 2 );
	}

	public function add_column_header( $headers ) {
		// Remove count column.
		unset( $headers['posts'] );
		// Add FB id column.
		$headers['fb_set_id'] = __( 'FB set id', 'facebook-for-woocommerce' );
		return $headers;
	}

	/**
	 * Set values for custom columns in user taxonomies
	 */
	public function add_column_values( $display, $column, $term_id ) {
		$fb_product_set_id = get_term_meta( $term_id, \WC_Facebookcommerce_Integration::FB_PRODUCT_SET_ID, true );
		echo $fb_product_set_id;

		// if ( 'users' === $column && isset( $_GET['taxonomy'] ) ) {
		// 	$term = get_term( $term_id, $_GET['taxonomy'] );
		// 	echo '<a href="'.admin_url( 'users.php?user_tag='.$term->slug ).'">'.$term->count.'</a>';
		// } elseif ( 'export' === $column ) {
		// 	$url = wp_nonce_url( add_query_arg( [
		// 		'eut_export_csv' => '1',
		// 		'user_tag' => $term_id
		// 	] ), 'eut_export_csv' );

		// 	echo '<a href="'.$url.'" class="button">'.__( 'Export to CSV', 'automatewoo' ).'</a>';
		// } else {
		// 	echo '-';
		// }
	}

	public function validate_term_before_insert( $term, $taxonomy ) {
		if ( 'fb_product_set' !== $taxonomy ) {
			return $term;
		}
		$wc_product_cats = empty( $_POST[ \WC_Facebookcommerce::PRODUCT_SET_META ] ) ? '' : $_POST[ \WC_Facebookcommerce::PRODUCT_SET_META ]; //phpcs:ignore
		if ( empty( $wc_product_cats ) ) {
			return new \WP_Error( 'missing_set_categories', __( 'Creating a product set without categories is not allowed. Please add categories to create a FB product set.', 'facebook-for-woocommerce' ) );
		}

		$product_cat_ids = array_map( 'intval', $wc_product_cats );

		/*
		 * Check if we have products for a given set of categories.
		 */
		$products_args = array(
			'fields'         => 'ids',
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1, // We just need to know if we have anything, just one post is great.
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_taxonomy_id',
					'terms'    => $product_cat_ids,
					'operator' => 'IN',
				),
			),
		);

		$product_ids = get_posts( $products_args );

		if ( empty( $product_ids ) ) {
			return new \WP_Error( 'empty_products_set', __( 'Creating a product set with categories that do not have any products is not allowed. Please select different categories combination', 'facebook-for-woocommerce' ) );
		}

		return $term;
	}
}