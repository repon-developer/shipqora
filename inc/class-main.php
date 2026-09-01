<?php

namespace ShipQora;

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
		add_filter('plugin_action_links', array($this, 'add_plugin_links'), 10, 2);
	}

	/**
	 * Load files
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function load_files() {
		require_once SHIPQORA_PATH . 'inc/class-core.php';
		require_once SHIPQORA_PATH . 'inc/class-feature.php';
		require_once SHIPQORA_PATH . 'inc/class-debugging.php';
		require_once SHIPQORA_PATH . 'inc/class-cart-total.php';
		require_once SHIPQORA_PATH . 'inc/class-form-control.php';
		require_once SHIPQORA_PATH . 'inc/class-shipping-cost.php';
		require_once SHIPQORA_PATH . 'inc/class-shipqora-rule.php';
		require_once SHIPQORA_PATH . 'inc/class-settings-fields.php';
		require_once SHIPQORA_PATH . 'inc/conditions/class-main.php';
		require_once SHIPQORA_PATH . 'inc/features/class-general.php';
		require_once SHIPQORA_PATH . 'inc/components/trait-component.php';

		/* Load components */
		require_once SHIPQORA_PATH . 'inc/components/class-select2.php';
		require_once SHIPQORA_PATH . 'inc/components/class-table-rate.php';
		require_once SHIPQORA_PATH . 'inc/components/class-cart-option.php';


		if (is_admin()) {
			require_once SHIPQORA_PATH . 'inc/class-admin.php';
		}
	}

	/**
	 * Add plugin links
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_plugin_links($actions, $plugin_file) {
		if (SHIPQORA_BASENAME == $plugin_file) {
			$new_links['shipqora_rules'] = sprintf('<a href="%s">%s</a>', menu_page_url('shipqora', false), esc_html__('ShipQora Rules', 'shipqora'));
			$new_links['shipqora_lite'] = sprintf('<a target="_blank" href="%s">%s</a>', 'https://shipqora.com/', __('Get Pro', 'shipqora'));
			$actions = array_merge($new_links, $actions);
		}

		return $actions;
	}
}

Main::get_instance();
