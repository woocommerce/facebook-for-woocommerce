<?php

/**
 * Demo class to test inheritance compatibility between PHP versions.
 */
class Base {

	/**
	 * PHP 7.4 must fail on a property declared with data type.
	 *
	 * @param string $a Some param.
	 * @return bool
	 */
	public function demo_method( string $a ): bool {
		return true;
	}
}
