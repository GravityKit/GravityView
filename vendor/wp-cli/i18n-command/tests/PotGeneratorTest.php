<?php

namespace WP_CLI\I18n\Tests;

use Gettext\Translation;
use WP_CLI\I18n\PotGenerator;
use Gettext\Translations;
use WP_CLI\Tests\TestCase;

class PotGeneratorTest extends TestCase {
	public function test_adds_correct_amount_of_plural_strings() {
		$translations = new Translations();

		$translation = new Translation( '', '%d cat', '%d cats' );

		$translations[] = $translation;

		$result = PotGenerator::toString( $translations );

		$this->assertStringContainsString( 'msgid "%d cat"', $result );
		$this->assertStringContainsString( 'msgid_plural "%d cats"', $result );
		$this->assertStringContainsString( 'msgstr[0] ""', $result );
		$this->assertStringContainsString( 'msgstr[1] ""', $result );
	}
}
