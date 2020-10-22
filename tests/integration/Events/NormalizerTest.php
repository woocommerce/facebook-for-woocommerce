<?php

namespace SkyVerge\WooCommerce\Facebook\Events;

use InvalidArgumentException;
use Hoa\Stream\Test\Unit\IStream\In;
use IntegrationTester;

/**
 * Tests the Event class.
 */
class NormalizerTest extends \Codeception\TestCase\WPTestCase {

	/** Test methods **************************************************************************************************/

	/** @see Normalizer::normalize() */
	public function test_normalize_email(){
		$email = "    john(.doe)@exa//mple.com   ";
		$normalized_email = Normalizer::normalize('em', $email);
		$this->assertEquals("john.doe@example.com", $normalized_email);
		$this->expectException(InvalidArgumentException::class);
		$invalid_email = "  john(.doeexa//mplecom   ";
		Normalizer::normalize('em', $invalid_email);
	}

	/** @see Normalizer::normalize() */
	public function test_normalize_city(){
		$city = "Salt Lake City";
		$normalized_city = Normalizer::normalize('ct', $city);
		$this->assertEquals("saltlakecity", $normalized_city);
	}

	/** @see Normalizer::normalize() */
	public function test_normalize_state(){
		$state = "CA";
		$normalized_state = Normalizer::normalize('st', $state);
		$this->assertEquals("ca", $normalized_state);
	}

	/** @see Normalizer::normalize() */
	public function test_normalize_country_code(){
		$country = "U S ";
		$normalized_country = Normalizer::normalize('country', $country);
		$this->assertEquals("us", $normalized_country);
		$invalid_country = "United States";
		$this->expectException(InvalidArgumentException::class);
		Normalizer::normalize('country', $invalid_country);
	}

	/** @see Normalizer::normalize() */
	public function test_normalize_zip_code(){
		$zip_code = "   123 45   ";
		$normalized_zip_code = Normalizer::normalize('zp', $zip_code);
		$this->assertEquals("12345", $normalized_zip_code);
	}

	/** @see Normalizer::normalize() */
	public function test_normalize_phone(){
		$phone_number = "(123) 456 7890";
		$normalized_phone_number = Normalizer::normalize('ph', $phone_number);
		$this->assertEquals("1234567890", $normalized_phone_number);
	}

	/** @see Normalizer::normalize() */
	public function test_normalize_first_name(){
		$first_name = 'John';
		$normalized_first_name = Normalizer::normalize('fn', $first_name);
		$this->assertEquals('john', $normalized_first_name);
	}

	/** @see Normalizer::normalize() */
	public function test_normalize_last_name(){
		$last_name = 'Doe';
		$normalized_last_name = Normalizer::normalize('ln', $last_name);
		$this->assertEquals('doe', $normalized_last_name);
	}
}
