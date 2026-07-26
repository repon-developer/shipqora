<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main class plugin
 */
final class Main {

	/**
	 * Hold the current instance of plugin
	 * 
	 * @since 1.0.0
	 * @var Main
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Main
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_files();
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 1);
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
	}

	/**
	 * Load files
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function load_files() {
		require_once ShipFlex_PATH . 'inc/class-core.php';
		require_once ShipFlex_PATH . 'inc/class-feature.php';
		require_once ShipFlex_PATH . 'inc/class-debugging.php';
		require_once ShipFlex_PATH . 'inc/class-cart-total.php';
		require_once ShipFlex_PATH . 'inc/class-form-control.php';
		require_once ShipFlex_PATH . 'inc/class-shipping-cost.php';
		require_once ShipFlex_PATH . 'inc/class-shipflex-rule.php';
		require_once ShipFlex_PATH . 'inc/class-settings-fields.php';
		require_once ShipFlex_PATH . 'inc/conditions/class-main.php';

		/* Load components */
		require_once ShipFlex_PATH . 'inc/components/class-select2.php';
		require_once ShipFlex_PATH . 'inc/components/class-cart-option.php';
		require_once ShipFlex_PATH . 'inc/components/class-shipping-cost-range.php';

		// Load Features
		require_once ShipFlex_PATH . 'inc/features/class-general.php';
		require_once ShipFlex_PATH . 'inc/features/class-cart-based-shipping.php';
		require_once ShipFlex_PATH . 'inc/features/class-hide-shipping-methods.php';
		require_once ShipFlex_PATH . 'inc/features/class-product-based-shipping.php';
		require_once ShipFlex_PATH . 'inc/features/class-shipping-cost-adjustment.php';
		require_once ShipFlex_PATH . 'inc/features/class-hide-other-shipping-methods.php';


		if (is_admin()) {
			require_once ShipFlex_PATH . 'inc/class-admin.php';
		}
	}

	/**
	 * Add plugin links
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_plugin_links($actions, $plugin_file) {
		if (ShipFlex_BASENAME == $plugin_file) {
			$new_links['shipflex_rules'] = sprintf('<a href="%s">%s</a>', menu_page_url('shipflex', false), esc_html__('ShipFlex Rules', 'shipflex'));
			$new_links['shipflex_lite'] = sprintf('<a target="_blank" href="%s">%s</a>', 'https://shipflexpro.com/', __('Get Pro', 'shipflex'));
			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}

	/**
	 * Enqueue script on the frontend
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		if (!Debugging::get_instance()->is_debugging()) {
			return;
		}

		wp_enqueue_style('shipflex', ShipFlex_URI . 'assets/frontend.min.css', array(), Utils::get_plugin_version());
		wp_enqueue_script('shipflex', ShipFlex_URI . 'assets/frontend.min.js', array('jquery'), Utils::get_plugin_version(), true);
		wp_localize_script('shipflex', 'shipflex', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'debugging_nonce' => Debugging::get_instance()->get_nonce_value()
		));
	}
}

Main::get_instance();
