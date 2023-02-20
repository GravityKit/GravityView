<?php
/**
 * @license GPL-2.0-or-later
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\Logger;

use GravityKit\GravityView\Monolog\Logger as MonologLogger;
use GravityKit\GravityView\Monolog\Handler\AbstractProcessingHandler;
use GFForms;
use GFLogging;
use GFAddOn;

/**
 * Handler for Gravity Forms logging.
 */
class GravityFormsHandler extends AbstractProcessingHandler {
	/**
	 * @since 1.0.0
	 *
	 * @var string Logger unique ID.
	 */
	protected $_logger_id;

	/**
	 * @since 1.0.0
	 *
	 * @var string Logger title.
	 */
	protected $_logger_title;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $logger_id    Logger unique ID ("slug" as used by GFAddOn).
	 * @param string     $logger_title Logger title ("title" as used by GFAddOn).
	 * @param int|string $level        The minimum logging level at which this handler will be triggered. Default: DEBUG (100).
	 * @param bool       $bubble       Whether the messages that are handled can bubble up the stack or not. Default: true.
	 *
	 * @return void
	 */
	public function __construct( $logger_id, $logger_title, $level = MonologLogger::DEBUG, $bubble = true ) {
		if ( ! class_exists( 'GFForms' ) || ! class_exists( 'GFLogging' ) ) {
			return;
		}

		$this->_logger_id    = $logger_id;
		$this->_logger_title = $logger_title;

		GFForms::include_addon_framework();

		GFLogging::include_logger();

		new MockGFAddon( $logger_id, $logger_title );

		parent::__construct( $level, $bubble );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function write( array $record ) {
		$monolog_to_klogger_log_level_map = [
			'DEBUG'     => \Klogger::DEBUG,
			'INFO'      => \Klogger::INFO,
			'NOTICE'    => \Klogger::INFO,
			'WARNING'   => \Klogger::WARN,
			'ERROR'     => \Klogger::ERROR,
			'CRITICAL'  => \Klogger::FATAL,
			'ALERT'     => \Klogger::WARN,
			'EMERGENCY' => \Klogger::WARN,
		];

		\GFLogging::log_message( $this->_logger_id, $record['formatted'], $monolog_to_klogger_log_level_map[ $record['level_name'] ] );
	}
}

class MockGFAddon extends GFAddOn {
	/**
	 * {@inheritdoc}
	 */
	protected $_slug;

	/**
	 * {@inheritdoc}
	 */
	protected $_title;

	/**
	 * {@inheritdoc}
	 */
	protected $_path = '';

	/**
	 * {@inheritdoc}
	 */
	protected $_full_path = __FILE__;

	/**
	 * {@inheritdoc}
	 */
	public function __construct( $logger_id, $logger_title ) {
		if ( ! class_exists( 'GFForms' ) || ! class_exists( 'GFLogging' ) ) {
			return;
		}

		$this->_slug  = $logger_id ?: $this->_slug;
		$this->_title = $logger_title ?: $this->_title;

		parent::__construct();
	}
}
