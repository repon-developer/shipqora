<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Rule_List class
 */
final class Rule_List {

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action('init', array($this, 'handle_delete'));
		add_action('init', array($this, 'process_bulk_delete_action'));
		add_filter('set-screen-option', array(__CLASS__, 'set_screen'), 20, 3);
	}

	/**
	 * Handle delete of rule
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_delete() {
		if (!isset($_GET['id']) || !isset($_GET['delete'])) {
			return;
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['delete'])), 'shipqora/rule_delete_nonce')) {
			return;
		}

		$rule_id = absint($_GET['id']);

		global $wpdb;
		$wpdb->delete($wpdb->shipqora_rules_table, array('id' => $rule_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		wp_safe_redirect(remove_query_arg(array('id', 'delete'))); //phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
		exit;
	}

	/**
	 * Set screen option $value.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function set_screen($status, $option, $value) {
		return $value;
	}

	/**
	 * Handle bulk delete action
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function process_bulk_delete_action() {
		if (wp_doing_ajax() || empty($_POST['rules']) || !is_array($_POST['rules'])) {
			return;
		}

		if (empty($_POST['_wpnonce']) || empty($_POST['action'])) {
			return;
		}

		$action = 'bulk-shipqora_rules_table';
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), $action)) {
			return;
		}

		if ('bulk-delete' !== sanitize_text_field(wp_unslash($_POST['action']))) {
			return;
		}

		global $wpdb;
		$rules = array_map('absint', $_POST['rules']);
		foreach ($rules as $rule_id) {
			$wpdb->delete($wpdb->shipqora_rules_table, array('id' => $rule_id)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}

	/**
	 * Add options for screen setting.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function screen_option() {
		add_screen_option('per_page', [
			'label' => __('Rules Per Page', 'shipqora'),
			'default' => 15,
			'option' => 'shipqora_rules_per_page'
		]);
	}
}
