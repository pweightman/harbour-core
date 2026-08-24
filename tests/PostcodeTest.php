<?php
use PHPUnit\Framework\TestCase;

final class PostcodeTest extends TestCase {

	/** @dataProvider normaliseCases */
	public function test_normalise( string $in, string $expected ): void {
		$this->assertSame( $expected, harbour_normalize_postcode( $in ) );
	}

	public static function normaliseCases(): array {
		return array(
			'lowercase no space' => array( 'le175nj', 'LE17 5NJ' ),
			'lowercase spaced'   => array( 'le17 5nj', 'LE17 5NJ' ),
			'extra spaces'       => array( '  cv13   0aa ', 'CV13 0AA' ),
			'short outward'      => array( 'b11ha', 'B1 1HA' ),
			'mixed case'         => array( 'Le17 5nJ', 'LE17 5NJ' ),
		);
	}

	/** @dataProvider validCases */
	public function test_valid( string $in, bool $expected ): void {
		$this->assertSame( $expected, harbour_is_valid_uk_postcode( $in ) );
	}

	public static function validCases(): array {
		return array(
			'valid LE17 no space' => array( 'le175nj', true ),
			'valid CV13'          => array( 'CV13 0AA', true ),
			'valid short'         => array( 'B1 1HA', true ),
			'valid London'        => array( 'EC1A 1BB', true ),
			'too short'           => array( 'ZZZZ', false ),
			'letters only'        => array( 'ABCDEF', false ),
			'empty'               => array( '', false ),
			'us zip'              => array( '90210', false ),
		);
	}
}
