<?php
use PHPUnit\Framework\TestCase;

final class HoneypotTest extends TestCase {

	public function test_clean_submission_passes(): void {
		$data = array( 'harbour_hp' => '', 'harbour_ts' => (string) ( time() - 10 ) );
		$this->assertTrue( harbour_passes_honeypot( $data ) );
	}

	public function test_filled_honeypot_fails(): void {
		$data = array( 'harbour_hp' => 'i am a bot', 'harbour_ts' => (string) ( time() - 10 ) );
		$this->assertFalse( harbour_passes_honeypot( $data ) );
	}

	public function test_instant_submission_fails(): void {
		$data = array( 'harbour_hp' => '', 'harbour_ts' => (string) time() );
		$this->assertFalse( harbour_passes_honeypot( $data ) );
	}

	public function test_missing_timestamp_fails(): void {
		$this->assertFalse( harbour_passes_honeypot( array( 'harbour_hp' => '' ) ) );
	}
}
