<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Debugging class plugin
 */
final class Debugging {

	/**
	 * Hold nonce key of debugging
	 * 
	 * @since 1.0.0
	 */
	const NONCE = '_nonce_shipflex_debugging';

	/**
	 * Hold the current instance of plugin
	 * 
	 * @since 1.0.0
	 * @var Debugging
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Debugging
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold if debugging mode enabled or not
	 * 
	 * @since 1.0.0
	 * @var boolean
	 */
	private $is_debugging = true;

	/**
	 * Hold if debugging mode collapsed
	 * 
	 * @since 1.0.0
	 * @var boolean
	 */
	private $is_collapse = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$shipflex_debugging = wp_parse_args(get_option('shipflex_debugging'), array('enabled' => true, 'collapse' => false));
		$this->is_debugging = $shipflex_debugging['enabled'];
		$this->is_collapse = $shipflex_debugging['collapse'];

		add_action('wp_ajax_shipflex/update_debugging_mode', array($this, 'update_debugging_mode'));
	}

	/**
	 * Check if debugging mode enabled
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_debugging() {
		return $this->is_debugging;
	}

	/**
	 * Ajax function to update settings for debugging mode
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function update_debugging_mode() {
		if (!isset($_POST['nonce']) || !isset($_POST['enable_debugging'])) {
			wp_send_json_error(array('message' => esc_html__('Required data missing.', 'shipflex')));
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), self::NONCE)) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipflex')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to save data.', 'shipflex')));
		}

		update_option('shipflex_debugging', array('enabled' => true, 'collapse' => $this->is_collapse));

		wp_send_json_success();
	}
}

Debugging::get_instance();
