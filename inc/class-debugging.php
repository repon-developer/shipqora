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
	private $is_debugging_mode_enabled = true;

	/**
	 * Hold if debugging mode collapsed
	 * 
	 * @since 1.0.0
	 * @var boolean
	 */
	private $is_collapse = false;

	/**
	 * Hold position of floating debug box
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $position = 'right';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$debugging_settings = wp_parse_args(get_option('shipflex_debugging'), array('enabled' => true, 'collapse' => false, 'position' => 'right'));
		$this->is_collapse = $debugging_settings['collapse'];
		$this->is_debugging_mode_enabled = $debugging_settings['enabled'] !== false;

		if ('left' === $debugging_settings['position']) {
			$this->position = 'left';
		}

		add_action('wp_ajax_shipflex/update_debugging_mode', array($this, 'update_debugging_mode'));
		add_action('wp_footer', array($this, 'output_debugging_section'));
	}

	/**
	 * Get nonce value
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_nonce_value() {
		return wp_create_nonce(self::NONCE);
	}

	/**
	 * Check if debugging mode enabled
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_debugging_mode_enabled() {
		return $this->is_debugging_mode_enabled;
	}

	/**
	 * Check debugging is running or not
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_debugging() {
		return $this->is_debugging_mode_enabled && current_user_can('manage_woocommerce');
	}

	/**
	 * Get float box position
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_position() {
		return $this->position;
	}

	/**
	 * Ajax function to update settings for debugging mode
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function update_debugging_mode() {
		if (!isset($_POST['nonce'])) {
			wp_send_json_error(array('message' => esc_html__('Required data missing.', 'shipflex')));
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), self::NONCE)) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipflex')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to save data.', 'shipflex')));
		}

		if (isset($_POST['enable_debugging'])) {
			$this->is_debugging_mode_enabled = filter_var(sanitize_text_field(wp_unslash($_POST['enable_debugging'])), FILTER_VALIDATE_BOOLEAN);
		}

		if (isset($_POST['collapse'])) {
			$this->is_collapse = filter_var(sanitize_text_field(wp_unslash($_POST['collapse'])), FILTER_VALIDATE_BOOLEAN);
		}

		if (!empty($_POST['position'])) {
			$this->position = sanitize_text_field(wp_unslash($_POST['position']));
		}
		
		update_option('shipflex_debugging', array(
			'position' => $this->position,
			'collapse' => $this->is_collapse,
			'enabled' => $this->is_debugging_mode_enabled,
		));

		wp_send_json_success();
	}

	/**
	 * Output debugging content on the frontend
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_debugging_section() {
		if (!$this->is_debugging()) {
			return;
		}

		$classes = array($this->get_position());
		if (true === $this->is_collapse) {
			$classes[] = 'collapse';
		} ?>
		<div id="shipflex-debugging-box" class="<?php echo esc_attr(implode(' ', $classes)) ?>">
			<div class="title-bar">
				<?php esc_html_e('Shipflex Debugging', 'shipflex') ?>
			</div>

			<div class="shipflex-box-body">
				<div class="shipflex-content">
					Lorem ipsum dolor sit amet consectetur adipisicing elit. Accusantium maiores nobis non atque suscipit perferendis vel odit veniam illo maxime, fuga corrupti recusandae quod consectetur repellendus ipsum dolorum voluptatum iusto!
				</div>
				<div class="shipflex-footer">
					<a class="shipflex-button shipflex-position-button" href="#">
						<span class="left"><?php esc_html_e('Move on Left', 'shipflex') ?></span>
						<span class="right"><?php esc_html_e('Move on Right', 'shipflex') ?></span>
					</a>
					<a class="shipflex-button shipflex-disable-button" href="#"><?php esc_html_e('Disable Debugging', 'shipflex') ?></a>
				</div>
			</div>
		</div>
<?php
	}
}

Debugging::get_instance();
