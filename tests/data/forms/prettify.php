<?php
/** Prettify all JSON payloads */
foreach ( glob( dirname( __FILE__ ) . '/*.json' ) as $json_file ) {
	$json = json_decode( file_get_contents( $json_file ) );
	file_put_contents( $json_file, json_encode( $json, JSON_PRETTY_PRINT ) );
}
