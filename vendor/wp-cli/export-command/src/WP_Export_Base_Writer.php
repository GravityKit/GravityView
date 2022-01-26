<?php

abstract class WP_Export_Base_Writer {
	protected $formatter;

	public function __construct( $formatter ) {
		$this->formatter = $formatter;
	}

	public function export() {
		$this->write( $this->formatter->before_posts() );
		foreach ( $this->formatter->posts() as $post_in_wxr ) {
			$this->write( $post_in_wxr );
		}
		$this->write( $this->formatter->after_posts() );
	}

	abstract protected function write( $xml );
}
