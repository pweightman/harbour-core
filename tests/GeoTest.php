<?php
use PHPUnit\Framework\TestCase;

final class GeoTest extends TestCase {

	public function test_haversine_zero_distance(): void {
		$this->assertSame( 0.0, round( harbour_haversine_miles( 52.52, -1.17, 52.52, -1.17 ), 4 ) );
	}

	public function test_haversine_known_distance(): void {
		// Ashby Magna (yard) to central Leicester ~8 miles.
		$miles = harbour_haversine_miles( 52.5212, -1.1774, 52.6369, -1.1398 );
		$this->assertGreaterThan( 7.0, $miles );
		$this->assertLessThan( 9.5, $miles );
	}

	public function test_haversine_symmetric(): void {
		$a = harbour_haversine_miles( 52.5, -1.1, 53.4, -2.2 );
		$b = harbour_haversine_miles( 53.4, -2.2, 52.5, -1.1 );
		$this->assertSame( round( $a, 6 ), round( $b, 6 ) );
	}

	/** @dataProvider bandCases */
	public function test_classify( float $miles, string $expected ): void {
		$this->assertSame( $expected, harbour_classify_distance( $miles, 12.0, 20.0 ) );
	}

	public static function bandCases(): array {
		return array(
			'well inside'   => array( 5.0, 'inside' ),
			'on inner edge' => array( 12.0, 'inside' ),
			'just outer'    => array( 12.1, 'outer' ),
			'on outer edge' => array( 20.0, 'outer' ),
			'beyond outer'  => array( 20.1, 'outside' ),
			'far'           => array( 80.0, 'outside' ),
		);
	}
}
