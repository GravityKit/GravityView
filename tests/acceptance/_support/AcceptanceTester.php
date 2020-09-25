<?php

/**
 * Inherited Methods
 * @method void wantToTest( $text )
 * @method void wantTo( $text )
 * @method void execute( $callable )
 * @method void expectTo( $prediction )
 * @method void expect( $prediction )
 * @method void amGoingTo( $argumentation )
 * @method void am( $role )
 * @method void lookForwardTo( $achieveValue )
 * @method void comment( $description )
 * @method \Codeception\Lib\Friend haveFriend( $name, $actorClass = null )
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor {

	use _generated\AcceptanceTesterActions {
		cli as _cli;
		cliToArray as _cliToArray;
	}

	private $GV_pages = [
		'views'           => 'edit.php?post_type=gravityview',
		'new_view'        => 'post-new.php?post_type=gravityview',
		'settings'        => 'edit.php?post_type=gravityview&page=gravityview_settings',
		'getting_started' => 'edit.php?post_type=gravityview&page=gv-getting-started',
		'extensions'      => 'edit.php?post_type=gravityview&page=gv-admin-installer',
	];

	public function getAutomationId( $id ) {

		return sprintf( '[data-automation-id="%s"]', $id );
	}

	public function goToPluginPage( $page ) {

		return $this->openPluginPage( $page );
	}

	public function openPluginPage( $page = 'views' ) {

		$I = $this;

		$I->amOnPage( "/wp-admin/{$this->GV_pages[$page]}" );
	}

	public function checkIfAmOnPage( $page ) {

		$I = $this;

		$I->seeInCurrentUrl( $this->GV_pages[ $page ] );
	}

	public function goToView( $view_slug ) {

		$I = $this;

		$I->amOnPage( "/view/{$view_slug}" );
	}

	public function goToViewSingleEntry( $view_slug, $entry_id ) {

		$I = $this;

		$I->amOnPage( "/view/{$view_slug}/entry/{$entry_id}" );
	}

	public function importForm( $json, $title = null ) {

		$json = json_decode( file_get_contents( codecept_data_dir() . "forms/{$json}" ), true );

		unset( $json['version'] ); // GF fails to create a form with this property

		if ( ! is_null( $title ) ) {
			$json[0]['title'] = $title;
		}

		$result = \GFAPI::add_forms( $json );

		if ( is_wp_error( $result ) ) {
			$this->fail( "Can't create GF form: {$result->get_error_message()}" );
		}

		return (int) $result[0];
	}

	public function deleteForm( $form_id ) {

		$result = \GFAPI::delete_form( $form_id );

		if ( is_wp_error( $result ) ) {
			$this->fail( "Can't delete GF form: {$result->get_error_message()}" );
		}
	}

	public function GFCli( $command ) {

		return $this->cli( "gf {$command}" );
	}

	public function GFCliToArray( $command ) {

		return $this->cliToArray( "gf {$command}" );
	}

	public function cli( $command ) {

		return $this->_cli( "${command} --allow-root" );
	}

	public function cliToArray( $command ) {

		return $this->_cliToArray( "${command} --allow-root" );
	}
}
