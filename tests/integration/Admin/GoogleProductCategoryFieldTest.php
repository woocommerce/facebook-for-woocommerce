<?php

use SkyVerge\WooCommerce\Facebook\Admin;

/**
 * Tests the Admin\Google_Product_Category_Field class.
 */
class GoogleProductCategoryFieldTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;


	/**
	 * Runs before each test.
	 */
	protected function _before() {

		require_once 'includes/Admin/Google_Product_Category_Field.php';
	}


	/**
	 * Runs after each test.
	 */
	protected function _after() {

	}


	/** Test methods **************************************************************************************************/


	// TODO: add test for render()


	// TODO: add test for get_categories()


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\Admin\Google_Product_Category_Field::parse_categories_response()
	 *
	 * @param array $response test response
	 * @param array $expected expected categories
	 *
	 * @dataProvider provider_parse_categories_response
	 */
	public function test_parse_categories_response( $response, $expected ) {

		$field = new Admin\Google_Product_Category_Field();

		$this->assertNotEmpty( $field->get_categories() );
	}


	/** @see test_parse_categories_response */
	public function provider_parse_categories_response() {

		return [
			'error response'           => [ new WP_Error(), [] ],
			'response without body'    => [ [], [] ],
			'response with empty body' => [ [ 'body' => '' ], [] ],
			'response with valid body' => [
				[
					'body' => '# Google_Product_Taxonomy_Version: 2019-07-10
1 - Animals & Pet Supplies
3237 - Animals & Pet Supplies > Live Animals
2 - Animals & Pet Supplies > Pet Supplies
3 - Animals & Pet Supplies > Pet Supplies > Bird Supplies
7385 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories
499954 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories > Bird Cage Bird Baths
7386 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cage Accessories > Bird Cage Food & Water Dishes
4989 - Animals & Pet Supplies > Pet Supplies > Bird Supplies > Bird Cages & Stands',
				],
				$this->get_test_category_list(),
			],
		];
	}


	/**
	 * @see \SkyVerge\WooCommerce\Facebook\Admin\Google_Product_Category_Field::get_category_options()
	 *
	 * @param array $categories full category list
	 * @param string $category_id category ID
	 * @param array $expected expected options
	 *
	 * @dataProvider provider_get_category_options
	 */
	public function test_get_category_options( $categories, $category_id, $expected ) {

		$field = new Admin\Google_Product_Category_Field();

		$this->assertNotEmpty( $field->get_categories() );
	}


	/** @see test_get_category_options */
	public function provider_get_category_options() {

		return [

			'top level category' => [
				$this->get_test_category_list(),
				'1', [
					'3237'   => [
						'label'  => 'Live Animals',
						'parent' => '1',
					],
					'2'      => [
						'label'  => 'Pet Supplies',
						'parent' => '1',
					],
				],
			],
			'2nd level category' => [
				$this->get_test_category_list(),
				'2', [
					'3'      => [
						'label'  => 'Bird Supplies',
						'parent' => '2',
					],
				],
			],
			'3rd level category' => [
				$this->get_test_category_list(),
				'3', [
					'7385'   => [
						'label'  => 'Bird Cage Accessories',
						'parent' => '3',
					],
					'4989'   => [
						'label'  => 'Bird Cages & Stands',
						'parent' => '3',
					],
				],
			],
			'4th level category' => [
				$this->get_test_category_list(),
				'7385', [
					'499954' => [
						'label'  => 'Bird Cage Bird Baths',
						'parent' => '7385',
					],
					'7386'   => [
						'label'  => 'Bird Cage Food & Water Dishes',
						'parent' => '7385',
					],
				],
			],
			'5th level category' => [
				$this->get_test_category_list(),
				'499954', [],
			],
		];
	}



	/** Helper methods **************************************************************************************************/


	/**
	 * Gets a test category list.
	 *
	 * @return array
	 */
	private function get_test_category_list() {

		return [
			'1'      => [
				'label'  => 'Animals & Pet Supplies',
				'parent' => '',
			],
			'3237'   => [
				'label'  => 'Live Animals',
				'parent' => '1',
			],
			'2'      => [
				'label'  => 'Pet Supplies',
				'parent' => '1',
			],
			'3'      => [
				'label'  => 'Bird Supplies',
				'parent' => '2',
			],
			'7385'   => [
				'label'  => 'Bird Cage Accessories',
				'parent' => '3',
			],
			'499954' => [
				'label'  => 'Bird Cage Bird Baths',
				'parent' => '7385',
			],
			'7386'   => [
				'label'  => 'Bird Cage Food & Water Dishes',
				'parent' => '7385',
			],
			'4989'   => [
				'label'  => 'Bird Cages & Stands',
				'parent' => '3',
			],
		];
	}


}
