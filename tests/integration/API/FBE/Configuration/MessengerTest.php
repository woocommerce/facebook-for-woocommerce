<?php

namespace SkyVerge\WooCommerce\Facebook\Tests\API\FBE\Configuration;

use SkyVerge\WooCommerce\Facebook\API\FBE\Configuration\Messenger;

/**
 * Tests the API\FBE\Installation\Read\Response class.
 */
class MessengerTest extends \Codeception\TestCase\WPTestCase {


	/** @var \IntegrationTester */
	protected $tester;

	protected $data = [
		'enabled' => true,
		'domains' => [
			'https://facebook.com/',
			'https://wc-tests.test/',
		],
		'default_locale' => 'en_US',
	];


	public function _before() {

		parent::_before();

		// the API cannot be instantiated if an access token is not defined
		facebook_for_woocommerce()->get_connection_handler()->update_access_token( 'access_token' );

		// create an instance of the API and load all the request and response classes
		facebook_for_woocommerce()->get_api();
	}


	/** Test methods **************************************************************************************************/


	/** @see Messenger::__construct() */
	public function test_constructor_defaults() {

		$configuration = new Messenger();

		$this->assertFalse( $configuration->is_enabled() );
		$this->assertSame( [], $configuration->get_domains() );
		$this->assertSame( '', $configuration->get_default_locale() );
	}


	/** @see Messenger::is_enabled() */
	public function test_is_enabled() {

		$configuration = new Messenger( $this->data );

		$this->assertTrue( $configuration->is_enabled() );
	}


	/** @see Messenger::get_domains() */
	public function test_get_domains() {

		$configuration = new Messenger( $this->data );

		$this->assertIsArray( $configuration->get_domains() );
		$this->assertContains( 'https://wc-tests.test/', $configuration->get_domains() );
	}


	/** @see Messenger::get_default_locale() */
	public function test_get_default_locale() {

		$configuration = new Messenger( $this->data );

		$this->assertSame( 'en_US', $configuration->get_default_locale() );
	}


	/** @see Messenger::set_enabled() */
	public function test_set_enabled() {

		$configuration = new Messenger();

		$configuration->set_enabled( true );

		$this->assertTrue( $configuration->is_enabled() );
	}


	/** @see Messenger::set_default_locale() */
	public function test_set_default_locale() {

		$configuration = new Messenger();

		$configuration->set_default_locale( 'hello' );

		$this->assertSame( 'hello', $configuration->get_default_locale() );
	}


	/**
	 * @see Messenger::set_domains()
	 *
	 * @dataProvider provider_set_domains
	 *
	 * @param array $value input value
	 * @param array $expected expected result
	 */
	public function test_set_domains( $value, $expected ) {

		$configuration = new Messenger();

		$configuration->set_domains( $value );

		$this->assertSame( $expected, $configuration->get_domains() );
	}


	/** @see test_set_domains */
	public function provider_set_domains() {

		return [
			'single valid domain'    => [ [ 'https://test.test/' ],                        [ 'https://test.test/' ] ],
			'multiple valid domains' => [ [ 'https://test.test/', 'https://test2.test/' ], [ 'https://test.test/', 'https://test2.test/' ] ],
			'mixed validity'         => [ [ 'https://test.test/', 'invalid' ],             [ 'https://test.test/' ] ],
			'single invalid'         => [ [ 'invalid' ],                                   [] ],
		];
	}


	/**
	 * @see Messenger::add_domain()
	 *
	 * @dataProvider provider_add_domain
	 *
	 * @param string $domain input value
	 * @param array $expected expected result
	 */
	public function test_add_domain( $domain, $expected ) {

		$configuration = new Messenger( [
			'domains' => [
				'https://existing.test/',
			],
		] );

		$configuration->add_domain( $domain );

		$this->assertSame( $expected, $configuration->get_domains() );
	}


	/** @see test_add_domain */
	public function provider_add_domain() {

		return [
			'new domain'      => [ 'https://test.test/',     [ 'https://existing.test/', 'https://test.test/' ] ],
			'existing domain' => [ 'https://existing.test/', [ 'https://existing.test/' ] ],
		];
	}


}
