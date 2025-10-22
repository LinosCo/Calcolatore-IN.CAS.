<?php
/**
 * AICB_Core Class.
 *
 * The core plugin class responsible for initializing the plugin, loading dependencies,
 * and setting up admin and frontend components.
 *
 * @package    AI_Chatbot
 * @subpackage AI_Chatbot/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Core plugin class.
 *
 * @since 1.0.0
 */
class AICB_Core {

	/**
	 * Plugin version.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $version The current version of the plugin.
	 */
	private $version;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * Plugin directory path.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string $plugin_dir The absolute path to the plugin directory.
	 */
	private $plugin_dir;

	/**
	 * Initialize the core functionality.
	 *
	 * Sets up plugin version, name, directory and hooks the init method.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->version     = AICB_VERSION;
		$this->plugin_name = 'ai-chatbot';
		$this->plugin_dir  = AICB_PLUGIN_DIR;

		// Hook into WordPress initialization.
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Load required dependencies for the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {
		require_once $this->plugin_dir . 'includes/class-aicb-activator.php';
		require_once $this->plugin_dir . 'includes/class-aicb-embedding-manager.php';
	}

	/**
	 * Initialize the plugin.
	 *
	 * Loads the plugin text domain, dependencies, and initializes admin and frontend components.
	 * Called by the 'plugins_loaded' hook.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		$this->load_dependencies(); // Load dependencies first.

		// Load API and register REST routes
		require_once $this->plugin_dir . 'includes/class-aicb-api.php';
		add_action( 'rest_api_init', 'aicb_register_rest_routes' );

		// Initialize embedding hooks.
		AICB_Embedding_Manager::instance();

		// Initialize admin-specific functionality and updater.
		if ( is_admin() ) {
			require_once $this->plugin_dir . 'includes/class-aicb-admin.php';
			$admin = new AICB_Admin( $this->plugin_name, $this->version );
			$admin->init(); // Call the init method to set up admin hooks
		}

		// Always instantiate AICB_Frontend as it handles frontend display
		// and AJAX actions that can be initiated from the frontend.
		require_once $this->plugin_dir . 'includes/class-aicb-frontend.php';
		new AICB_Frontend( $this->version );
	}
} // End class AICB_Core.
