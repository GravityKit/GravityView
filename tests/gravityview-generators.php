<?php

abstract class GV_UnitTest_Generator {

	static private $instances = array();

	public static function get_instance() {

		$class_name = get_called_class();

		if( ! isset( self::$instances[ $class_name ] ) ) {
			$function_args = func_get_args();
			self::$instances[ $class_name ] = new $class_name( $function_args );
		}

		return self::$instances[ $class_name ];
	}

	public static function get() {
		$function_args = func_get_args();

		/** @noinspection PhpUndefinedMethodInspection */
		return self::get_instance( $function_args )->next();
	}

	function __toString() {
		return $this->next();
	}

	abstract function next();

}

class GV_UnitTest_Generator_Number extends GV_UnitTest_Generator {

	var $number_format = true;
	var $decimals = 0;
	var $low = -10000000000;
	var $high = 10000000000;

	/**
	 * GV_UnitTest_Generator_Number constructor.
	 *
	 * @param bool $number_format
	 * @param int $decimals
	 * @param int $low
	 * @param int $high
	 */
	public function __construct( $number_format = true, $decimals = false, $low = -10000000000, $high = 10000000000 ) {
		$this->number_format = $number_format;

		if( is_bool( $decimals )) {
			$this->decimals = $decimals ? mt_rand( 0, 10 ) : '';
		} else if ( is_int( $decimals ) ) {
			$this->decimals = $decimals;
		}

		$this->low           = $low;
		$this->high          = $high;
	}

	function next() {
		$number = mt_rand( $this->low, $this->high );
		$generated = gravityview_number_format( $number, $this->decimals  );
		return $generated;
	}
}

class GV_UnitTest_Generator_IP extends GV_UnitTest_Generator {

	function next() {
		return long2ip( mt_rand() );
	}
}

abstract class GV_UnitTest_Generator_Array extends GV_UnitTest_Generator {

	var $possible_items = array();

	function next() {
		$key = mt_rand( 0, count( $this->possible_items ) - 1 );
		return $this->possible_items[ $key ];
	}
}

class GV_UnitTest_Generator_Float extends GV_UnitTest_Generator_Number {

	/** @see http://php.net/manual/en/function.mt-getrandmax.php */
	function next() {
		return $this->low + mt_rand() / mt_getrandmax() * ( $this->high - $this->low );
	}
}

class GF_UnitTest_Generator_Payment_Status extends GV_UnitTest_Generator_Array {
	var $possible_items = array(
		'Active',
		'Paid',
		'Processing',
		'Failed',
		'Cancelled',
	);
}

class GF_UnitTest_Generator_Status extends GV_UnitTest_Generator_Array {
	var $possible_items = array(

		'active',
		'active',
		'active', // Weight the "active" value

		'trash',
		'spam',
		'delete',
	);
}

class GF_UnitTest_Generator_User_Agent extends GV_UnitTest_Generator_Array {

	var $possible_items = array(
		'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36',
		'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:34.0) Gecko/20100101 Firefox/34.0',
		'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0',
		'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/5.0)',
		'Mozilla/5.0 (iPhone; CPU iPhone OS 7_0 like Mac OS X) AppleWebKit/537.51.1 (KHTML, like Gecko) Version/7.0 Mobile/11A465 Safari/9537.53 (compatible; bingbot/2.0; http://www.bing.com/bingbot.htm)',
		'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; Media Center PC',
		'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0',
		'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1',
		'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:30.0) Gecko/20100101 Firefox/30.0',
		'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
		'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; rv:11.0) like Gecko',
		'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
		'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0; Trident/5.0)',
		'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
	);
}

class GV_UnitTest_Generator_Date extends GV_UnitTest_Generator {

	var $format = 'Y-m-d H:i:s';

	function __construct( $format = 'Y-m-d H:i:s' ) {
		if( is_string( $format ) ) {
			$this->format = $format;
		}
	}

	function next() {
		$hour = mt_rand( 0, 24 );
		$minute = mt_rand( 0, 60 );
		$second = mt_rand( 0, 60 );
		$month = mt_rand( 1, 12 );
		$day = mt_rand( 1, 28 );
		$year = mt_rand( 2000, (int)date('Y') );
		$time = mktime( $hour, $minute, $second, $month, $day, $year);
		return date( $this->format, $time );
	}
}

class GF_UnitTest_Generator_Date_Created extends GV_UnitTest_Generator_Date {

	var $format = 'Y-m-d H:i:s';

}