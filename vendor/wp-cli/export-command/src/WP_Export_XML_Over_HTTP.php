<?php


class WP_Export_XML_Over_HTTP extends WP_Export_Base_Writer {
	private $file_name;

	public function __construct( $formatter, $file_name ) {
		parent::__construct( $formatter );
		$this->file_name = $file_name;
	}

	public function export() {
		try {
			$export = $this->get_export();
			$this->send_headers();
			echo $export;
		} catch ( WP_Export_Exception $e ) {
			// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Possibly used by third party extension.
			$message = apply_filters( 'export_error_message', $e->getMessage() );
			wp_die( $message, __( 'Export Error' ), [ 'back_link' => true ] );
		} catch ( WP_Export_Term_Exception $e ) {
			do_action( 'export_term_orphaned', $this->formatter->export->missing_parents );
			$message = apply_filters( 'export_term_error_message', $e->getMessage() );
			// phpcs:enable
			wp_die( $message, __( 'Export Error' ), [ 'back_link' => true ] );
		}
	}

	protected function write( $xml ) {
		$this->result .= $xml;
	}

	protected function get_export() {
		$this->result = '';
		parent::export();
		return $this->result;
	}

	protected function send_headers() {
		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $this->file_name );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
	}
}
