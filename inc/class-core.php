<?php

namespace ShipFlex;

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
		$wpdb->shipflex_rules_table = $wpdb->prefix . 'shipflex_rules';

		if (is_multisite()) {
			add_action('admin_init', array($this, 'activation'));
		}

		register_activation_hook(ShipFlex_FILE, array($this, 'activation_callback'));
	}

	/**
	 * Fire after plugin activation
	 * 
	 * @since 1.0.0
	 */
	public function activation_callback() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		maybe_create_table($wpdb->shipflex_rules_table, "CREATE TABLE $wpdb->shipflex_rules_table (
			`id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT, 
			`title` VARCHAR(200) NOT NULL, 
			`shipping_instances` JSON DEFAULT NULL,
			`active_features` JSON DEFAULT NULL,
			`feature_settings` JSON DEFAULT NULL,
			`meta_data` JSON DEFAULT NULL,
			`status` ENUM('active', 'disable', 'development') DEFAULT 'development',
			`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY idx_main (status)
		) {$wpdb->get_charset_collate()};");
	}
}


new Core();
