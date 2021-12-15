<?php

class LicenseTestCest {

	public function _before( AcceptanceTester $I ) {

		$I->loginAsAdmin();
	}

	public function checkLicense( AcceptanceTester $I ) {

		$I->wantTo( 'Check for valid and invalid licenses' );

		$I->goToPluginPage( 'settings' );

		$I->waitForText( 'GravityView Settings' );

		$I->fillField( '#license_key', 'invalid license' );

		$I->performOn( '#edd-check', [ 'click' => 'Check License' ] );

		$I->waitForText( 'The license key entered is invalid' );

		$I->fillField( '#license_key', getenv( 'GRAVITYVIEW_KEY' ) );

		$I->performOn( '#edd-check', [ 'click' => 'Check License' ] );

		$I->waitForText( 'License level' );
	}

	public function activateDeactivateLicense( AcceptanceTester $I ) {

		$I->wantTo( 'Activate/deactivate valid and invalid licenses' );

		$I->goToPluginPage( 'settings' );

		$I->waitForText( 'GravityView Settings' );

		$I->fillField( '#license_key', 'invalid license' );

		$I->performOn( '#edd-activate', [ 'click' => 'Activate License' ] );

		$I->waitForText( 'The license key entered is invalid.' );

		$I->fillField( '#license_key', getenv( 'GRAVITYVIEW_KEY' ) );

		$I->performOn( '#edd-activate', [ 'click' => 'Activate License' ] );

		$I->waitForText( 'Licensed to:' );

		$I->see( 'License level:' );

		$I->see( 'Activations:' );

		$I->performOn( '#edd-deactivate', [ 'click' => 'Deactivate License' ] );

		$I->waitForText( 'The license has been deactivated.' );
	}

	public function saveLicense( AcceptanceTester $I ) {

		$I->wantTo( 'Save license' );

		$I->goToPluginPage( 'settings' );

		$I->waitForText( 'GravityView Settings' );

		$I->fillField( '#license_key', getenv( 'GRAVITYVIEW_KEY' ) );

		if ( gravityview()->plugin->is_GF_25() ) {
			$I->performOn( '#edd-activate', [ 'click' => 'Activate License' ] );

			$I->waitForText( 'Licensed to:' );

			$I->scrollTo( '#gform-settings-save' );

			$I->click( '#gform-settings-save' );

			$I->waitForText( 'Settings updated' );
		} else {
			$I->scrollTo( '#gform-settings-save' );

			$I->click( '#gform-settings-save' );

			$I->waitForText( 'The license key you entered has been saved, but not activated. Please activate the license.' );
		}

		$I->seeInField( '#license_key', getenv( 'GRAVITYVIEW_KEY' ) );
	}
}
