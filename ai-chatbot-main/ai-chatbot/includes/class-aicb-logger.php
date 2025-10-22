<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AICB_Logger {
	private $log_file;
	private $enabled;
	private $log_levels = array(
		'debug'   => 0,
		'info'    => 1,
		'warning' => 2,
		'error'   => 3,
	);
	private $min_level;

	public function __construct() {
		$this->enabled   = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$this->min_level = $this->log_levels['info']; // Default minimum level
		$this->init_log_file();
	}

	/**
	 * Initialize the log file
	 */
	private function init_log_file() {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/ai-chatbot-logs';

		// Create log directory if it doesn't exist
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );

			// Create .htaccess to protect logs
			$htaccess_content = "Order deny,allow\nDeny from all";
			file_put_contents( $log_dir . '/.htaccess', $htaccess_content );

			// Create index.php to prevent directory listing
			file_put_contents( $log_dir . '/index.php', '<?php // Silence is golden' );
		}

		$this->log_file = $log_dir . '/chatbot-' . gmdate( 'Y-m-d' ) . '.log';
	}

	/**
	 * Log a message
	 */
	public function log( $message, $level = 'info' ) {
		if ( ! $this->enabled ) {
			return;
		}

		// Check if the message level meets the minimum level requirement
		if ( $this->log_levels[ $level ] < $this->min_level ) {
			return;
		}

		$timestamp         = current_time( 'Y-m-d H:i:s' );
		$formatted_message = sprintf(
			"[%s] [%s] %s\n",
			$timestamp,
			strtoupper( $level ),
			$message
		);

		// Log to file
		if ( $this->log_file ) {
		}

		// Also log to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		}
	}

	/**
	 * Set minimum log level
	 */
	public function set_min_level( $level ) {
		if ( isset( $this->log_levels[ $level ] ) ) {
			$this->min_level = $this->log_levels[ $level ];
		}
	}

	/**
	 * Enable or disable logging
	 */
	public function set_enabled( $enabled ) {
		$this->enabled = (bool) $enabled;
	}

	/**
	 * Get log file path
	 */
	public function get_log_file() {
		return $this->log_file;
	}

	/**
	 * Clear old log files
	 */
	public function clear_old_logs( $days = 7 ) {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/ai-chatbot-logs';

		if ( ! is_dir( $log_dir ) ) {
			return;
		}

		$files = glob( $log_dir . '/chatbot-*.log' );
		$now   = time();

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				if ( $now - filemtime( $file ) >= 60 * 60 * 24 * $days ) {
					wp_delete_file( $file );
				}
			}
		}
	}
}
