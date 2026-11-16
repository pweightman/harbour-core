<?php
/**
 * Duplicate-submission guard: harbour_is_fresh_submission().
 *
 * Uses a tiny in-memory transient store (see tests/bootstrap.php) so the guard
 * can be exercised without WordPress.
 */
use PHPUnit\Framework\TestCase;

final class DedupTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['harbour_test_transients'] = array();
	}

	public function test_first_submission_is_fresh(): void {
		$this->assertTrue( harbour_is_fresh_submission( 'enquiry', 'james|07828750924|a@b.c|LE10 0NE|Pruning|' ) );
	}

	public function test_identical_repeat_is_blocked(): void {
		$fp = 'james|07828750924|a@b.c|LE10 0NE|Pruning|';
		$this->assertTrue( harbour_is_fresh_submission( 'enquiry', $fp ) );
		$this->assertFalse( harbour_is_fresh_submission( 'enquiry', $fp ) );
		$this->assertFalse( harbour_is_fresh_submission( 'enquiry', $fp ) );
	}

	public function test_different_content_is_fresh(): void {
		$this->assertTrue( harbour_is_fresh_submission( 'enquiry', 'james|07828750924|a@b.c|LE10 0NE|Pruning|' ) );
		// A genuinely different enquiry (different person) is not deduped.
		$this->assertTrue( harbour_is_fresh_submission( 'enquiry', 'fiona|07788140975|f@b.c|CV22 6JP|Felling|' ) );
	}

	public function test_buckets_are_isolated(): void {
		$fp = 'same|content';
		$this->assertTrue( harbour_is_fresh_submission( 'enquiry', $fp ) );
		$this->assertTrue( harbour_is_fresh_submission( 'order', $fp ) );
	}
}
