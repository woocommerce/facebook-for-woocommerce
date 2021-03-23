<?php

/**
 * The main product feed handler.
 *
 * This will eventually replace \WC_Facebook_Product_Feed as we refactor and move its functionality here.
 *
 * @since 1.11.0
 */
class GenerateCategories {
	const CATEGORIES_FILE_NAME = 'GoogleProductTaxonomy.php';

	/**
	 * Prepare categories file. It can be later required in a script.
	 *
	 * @param string $file Path to file.
	 */
	public function prepare_categories( $file ) {
		$categories_data = $this->load_categories( $file );
		$categories      = $this->parse_categories( $categories_data );

		// File content START.
		$export_string =
		'<?php' . PHP_EOL .
		'namespace SkyVerge\WooCommerce\Facebook\Products;' . PHP_EOL .
		'// This file was generated using GenerateCategories.php, do not modify it manually' . PHP_EOL .
		'// php GenerateCategories.php taxonomy-with-ids.en-US.txt' . PHP_EOL .
		'class GoogleProductTaxonomy {' . PHP_EOL .
		'	public const TAXONOMY = %s' . PHP_EOL .
		';}' . PHP_EOL;
		$export        = sprintf( $export_string, var_export( $categories, true ) );
		// File content END.

		file_put_contents( $this::CATEGORIES_FILE_NAME, $export );
	}

	/**
	 * Load categories from a file.
	 *
	 * @param string $file Path to file.
	 */
	protected function load_categories( $file ) {
		$category_file_contents = @file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$category_file_lines    = explode( "\n", $category_file_contents );
		$raw_categories         = array();
		foreach ( $category_file_lines as $category_line ) {

			if ( strpos( $category_line, ' - ' ) === false ) {
				// Not a category, skip it.
				continue;
			}

			list( $category_id, $category_name ) = explode( ' - ', $category_line );

			$raw_categories[ (string) trim( $category_id ) ] = trim( $category_name );
		}
		return $raw_categories;
	}

	/**
	 * Parse categories file lines and combine them to create an array of relations between categories.
	 *
	 * @param array $raw_categories Path to file.
	 */
	protected function parse_categories( $raw_categories ) {
		$categories = array();
		foreach ( $raw_categories as $category_id => $category_tree ) {

			$category_tree  = explode( ' > ', $category_tree );
			$category_label = end( $category_tree );
			$category       = array(
				'label'   => $category_label,
				'options' => array(),
			);

			if ( $category_label === $category_tree[0] ) {
				// This is a top-level category.
				$category['parent'] = '';
			} else {
				$parent_label = $category_tree[ count( $category_tree ) - 2 ];

				$parent_category = array_search(
					$parent_label,
					array_map(
						function ( $item ) {
							return $item['label'];
						},
						$categories
					)
				);

				$category['parent'] = (string) $parent_category;

				// Add category label to the parent's list of options.
				$categories[ $parent_category ]['options'][ $category_id ] = $category_label;
			}

			$categories[ (string) $category_id ] = $category;
		}

		return $categories;
	}

}

if ( ! is_file( $argv[1] ) ) {
	echo 'Not a file!';
	exit;
}

$generator = new GenerateCategories();
$generator->prepare_categories( $argv[1] );
