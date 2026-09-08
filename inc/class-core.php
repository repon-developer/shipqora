<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Core
 */
class Core {

	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$wpdb->shipqora_rules_table = $wpdb->prefix . 'shipqora_rules';
		register_activation_hook(SHIPQORA_FILE, array($this, 'activation_callback'));
		add_action('upgrader_process_complete', array($this, 'upgrade_callback'), 10, 2);
	}

	/**
	 * Fire after plugin activation
	 * 
	 * @since 1.0.0
	 */
	public function activation_callback() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		maybe_create_table($wpdb->shipqora_rules_table, "CREATE TABLE $wpdb->shipqora_rules_table (
			`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, 
			`title` VARCHAR(200) NOT NULL, 
			`shipping_methods` JSON DEFAULT NULL,
			`active_features` JSON DEFAULT NULL,
			`feature_settings` JSON DEFAULT NULL,
			`meta_data` JSON DEFAULT NULL,
			`status` ENUM('active', 'disabled', 'development') DEFAULT 'development',
			`updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			`created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY idx_main (status)
		) {$wpdb->get_charset_collate()};");

		$this->handle_activation_or_upgrade();
	}


	/**
	 * Cacth upgration hook
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function upgrade_callback($upgrader_object, $options) {
		if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
			return;
		}

		if (!in_array(SHIPQORA_BASENAME, $options['plugins'], true)) {
			return;
		}

		$this->handle_activation_or_upgrade();
	}

	/**
	 * Manage after upgrade or activation
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_activation_or_upgrade() {
		global $wpdb;
		$old_version = get_option('shipqora_version');

		if (version_compare($old_version, '1.0.2', '<')) {
			$rules = $wpdb->get_results($wpdb->prepare("Select * FROM %i", $wpdb->shipqora_rules_table), ARRAY_A);

			array_walk($rules, function ($rule_data) {
				$rule = new ShipQora_Rule($rule_data);

				$shipping_methods = array_map(function ($shipping_method) {
					list($method_id, $zone_id, $instance_id) = array_pad(preg_split('/[:\-]/', $shipping_method), 3, null);
					if (is_numeric($method_id)) {
						return $shipping_method;
					}

					if ('pickup_location' == $method_id) {
						$instance_id = $zone_id;
						$zone_id = 'pickup_location';
					}

					if (empty($method_id) || is_null($zone_id) || ($zone_id && strlen($zone_id) == 0)) {
						return false;
					}

					if (empty($instance_id)) {
						$instance_id = 0;
					}

					return $zone_id . ':' . $instance_id;
				}, $rule->shipping_methods);

				$rule->shipping_methods = array_values(array_filter($shipping_methods));
				$rule->save();
			});
		}

		update_option('shipqora_version', Utils::get_plugin_version());
	}
}


new Core();
