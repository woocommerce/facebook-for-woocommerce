<?php
/**
 * A synthetic test which contains backward incompatible changes between PHP versions starting 7.4 till 8.2.
 * The test must fail for PHP7.4 and pass for PHP8.2.
 *
 * @package phpcs-changed/test
 */

// Testing alignment from the updates sniffs package.
$var_a  = 'Var A.';
$var_ba = 'Var B.';

/**
 * Function to test named arguments.
 *
 * @param int    $a The first argument.
 * @param string $b The second argument.
 * @param array  $c The third argument.
 * @return void
 */
function named_arguments( int $a, string $b, array $c ) {}

$arguments = array(
	'a' => 1,
	'b' => '2',
	'c' => array( 3 ),
);

named_arguments( ...$arguments );

named_arguments( b: '2', a: 1, c: array( 3 ) );

$variable = new class() {
	/**
	 * Demo method.
	 *
	 * @return void
	 */
	public function method() {}
};

/**
 * Pass by reference function.
 *
 * @param object $a Some object by reference.
 * @return void
 */
function pass_by_reference( &$a ) {}

pass_by_reference( $variable );
