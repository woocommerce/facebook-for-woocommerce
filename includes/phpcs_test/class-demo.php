<?php

/**
 * Demo class to test PHP compatibility between versions.
 */
class Demo extends Base {

	/**
	 * Some number.
	 *
	 * @var int Some number.
	 */
	public int $number = 1;

	/**
	 * Demo constructor.
	 * PHP 7.4 must fail on a property declaration with type.
	 *
	 * @param int $number Some number.
	 */
	public function __construct( int $number ) {}

	/**
	 * Some number return method.
	 *
	 * @return int
	 */
	public function get_number(): int {
		return $this->number;
	}

	/**
	 * PHP 7.4 must fail on a property declaration with union type.
	 *
	 * @param string|int $a Union typed param.
	 * @return void
	 */
	public function union_type( string|int $a ) {}

	/**
	 * For PHP 8.0+ this one must be declared as returning type of :bool
	 * and the receiving param myst be of type string.
	 *
	 * @param string $name Property name.
	 * @return void
	 */
	public function __isset( $name ) {
	}

	/**
	 * Testing types inheritance.
	 *
	 * @param mixed $a Some parameter.
	 * @return void
	 */
	public function demo_method( $a ) {
	}
}
