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
	 * Hold debugging information of shipping rate
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $debug_data = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('wp_ajax_shipflex/update_debugging_mode', array($this, 'update_debugging_mode'));
		add_action('shipflex/after_statuses_options', array($this, 'add_debugging_notice_settings'));

		$debugging_settings = wp_parse_args(get_option('shipflex_debugging'), array('enabled' => true, 'collapse' => false, 'position' => 'right'));
		$this->is_debugging_mode_enabled = $debugging_settings['enabled'] !== false;
		$this->is_collapse = $debugging_settings['collapse'];
		if ('left' === $debugging_settings['position']) {
			$this->position = 'left';
		}

		if (!$this->is_debugging()) {
			return;
		}

		add_action('wp_footer', array($this, 'output_debugging_section'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 1);
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
	 * Add debugging notice settings after statuses options
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_debugging_notice_settings() {
?>

		<div class="shipflex-notice-box shipflex-notice-box-left">
			<h3><?php esc_html_e('ℹ️ Not Seeing Your Rule Updates on the Front End?', 'shipflex') ?></h3>
			<div class="description">
				WooCommerce caches shipping rules during active checkout sessions. To force WooCommerce to re-evaluate and display your updated ShipFlex rules, make a quick adjustment to at least one of these fields on the front end:
				<ul>
					<li><strong>Cart Contents:</strong> Add or remove an item</li>
					<li><strong>Quantity:</strong> Change the quantity of an existing item</li>
					<li><strong>Billing or Shipping Address:</strong> Update the address details at checkout</li>
				</ul>
			</div>
			<div class="gap-10"></div>
			<a @click.prevent="enable_debugging_mode()" v-if="!is_debugging_enabled" class="button" :class="{'in-progress': enabling_debugging_mode}" href="#">Show This Notice on Front End</a>
			<a @click.prevent="enable_debugging_mode()" v-if="is_debugging_enabled" class="button" :class="{'in-progress': enabling_debugging_mode}" href="#">Hide This Notice on Front End</a>
		</div>

		<!-- <div class="shipflex-notice-box shipflex-notice-box-left" v-if="!is_debugging_enabled && 'development' == status">
			<h3><?php //esc_html_e('💡 Debugging Mode is Disabled', 'shipflex') 
				?></h3>
			<div class="description"><?php //esc_html_e('Debugging mode displays on-screen insights on the front end to help you see exactly how ShipFlex rules, fees, and calculations are being applied. Enable debugging mode to easily troubleshoot rule execution while testing on your site.', 'shipflex') 
										?></div>
			<div class="gap-10"></div>
			<a @click.prevent="enable_debugging_mode()" class="button" :class="{'in-progress': enabling_debugging_mode}" href="#"><?php esc_html_e('Enable Debugging Mode', 'shipflex') ?></a>
		</div> -->
	<?php
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
	 * Check if target page for output debugging scripts and content
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_target_pages() {
		if (is_cart() || is_checkout()) {
			return true;
		}

		global $post;
		if (! $post instanceof WP_Post) {
			return false;
		}

		if (
			has_shortcode($post->post_content, 'woocommerce_cart') ||
			has_shortcode($post->post_content, 'woocommerce_checkout')
		) {
			return true;
		}

		// WooCommerce Cart/Checkout blocks.
		if (
			has_block('woocommerce/cart', $post) ||
			has_block('woocommerce/checkout', $post)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue script on the frontend
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		if (!$this->is_debugging() || !$this->is_target_pages()) {
			return;
		}

		wp_enqueue_style('shipflex', ShipFlex_URI . 'assets/debugging.min.css', array(), Utils::get_plugin_version());
		wp_enqueue_script('shipflex', ShipFlex_URI . 'assets/debugging.min.js', array('jquery', 'wp-data', 'wc-blocks-checkout'), Utils::get_plugin_version(), true);
		wp_localize_script('shipflex', 'shipflex', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'debugging_nonce' => Debugging::get_instance()->get_nonce_value()
		));
	}

	/**
	 * Output debugging content on the frontend
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_debugging_section() {
		if (!$this->is_debugging() || !$this->is_target_pages()) {
			return;
		}

		$classes = array($this->get_position());
		if (true === $this->is_collapse) {
			$classes[] = 'collapse';
		} ?>
		<div id="shipflex-debugging-box" class="<?php echo esc_attr(implode(' ', $classes)) ?>">
			<div class="title-bar">
				<?php esc_html_e('Shipflex Test Guideline', 'shipflex') ?>
			</div>

			<div class="shipflex-box-body">
				<div class="shipflex-content">
					<div class="store-manager-notice">
						<h4><?php esc_html_e('Note for Store Managers:', 'shipflex') ?></h4>

						<ul class="list">
							<li><strong>Visible Only To:</strong> Logged-in administrators and store managers (when the Notice Box is enabled in backend settings).</li>
							<li><strong>Hidden From:</strong> All standard store visitors, guest users, and regular customers.</li>
						</ul>
					</div>

					<div class="store-manager-notice">
						<h4>ℹ️ Not Seeing Your Rule Updates on the Front End?</h4>
						<p><strong>WooCommerce caches</strong> shipping rules during active checkout sessions. To force WooCommerce to re-evaluate and display your updated <strong>ShipFlex</strong> rules, make a quick adjustment to at least one of these fields on the front end:</p>
						<ul class="list">
							<li><strong>Cart Contents:</strong> Add or remove an item</li>
							<li><strong>Quantity:</strong> Change the quantity of an existing item</li>
							<li><strong>Billing or Shipping Address:</strong> Update the address details at checkout</li>
						</ul>
					</div>
				</div>
				<div class="shipflex-footer">
					<a class="shipflex-button shipflex-position-button" href="#">
						<span class="left"><?php esc_html_e('Move to Left', 'shipflex') ?></span>
						<span class="right"><?php esc_html_e('Move to Right', 'shipflex') ?></span>
					</a>
					<a class="shipflex-button shipflex-disable-button" href="#"><?php esc_html_e('Hide This Guideline', 'shipflex') ?></a>
				</div>
			</div>
		</div>
<?php
	}
}

add_action('init', array(Debugging::class, 'get_instance'));
