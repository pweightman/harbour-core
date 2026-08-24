<?php
use PHPUnit\Framework\TestCase;

final class OrderTotalTest extends TestCase {

	/** @dataProvider priceCases */
	public function test_parse_price( string $in, ?float $expected ): void {
		$this->assertSame( $expected, harbour_parse_price( $in ) );
	}

	public static function priceCases(): array {
		return array(
			'pounds'          => array( '£90', 90.0 ),
			'decimal'         => array( '£90.50', 90.5 ),
			'thousands'       => array( '£1,200', 1200.0 ),
			'plain number'    => array( '75', 75.0 ),
			'non-numeric'     => array( 'Price on request', null ),
			'empty'           => array( '', null ),
		);
	}

	public function test_total_multiplies_by_quantity(): void {
		$r = harbour_order_total( 90.0, 3, 1 );
		$this->assertTrue( $r['valid'] );
		$this->assertSame( 270.0, $r['total'] );
	}

	public function test_total_enforces_minimum(): void {
		$r = harbour_order_total( 90.0, 1, 2 );
		$this->assertFalse( $r['valid'] );
		$this->assertSame( 'min_order', $r['reason'] );
	}

	public function test_zero_quantity_invalid(): void {
		$r = harbour_order_total( 90.0, 0, 1 );
		$this->assertFalse( $r['valid'] );
		$this->assertSame( 'quantity', $r['reason'] );
	}

	public function test_null_price_gives_null_total_but_valid(): void {
		$r = harbour_order_total( null, 2, 1 );
		$this->assertTrue( $r['valid'] );
		$this->assertNull( $r['total'] );
	}
}
