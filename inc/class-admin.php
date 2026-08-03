<?php

namespace ShipFlex;

use ShipFlex\Reward;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin class
 */
final class Admin {

	/**
	 * Hold the current instance of plugin
	 * 
	 * @since 1.0.0
	 * @var Admin
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Admin
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hold list class of rule
	 * 
	 * @var Rule_List
	 * @since 1.0.0
	 */
	public $rule_list = null;

	/**
	 * Hold instance of settings form
	 * 
	 * @since 1.0.0
	 * @var Settings_Form
	 */
	private $settings_form = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->load_files();
		$this->rule_list = new Rule_List();

		add_action('admin_menu', array($this, 'admin_menu'));

		add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 1);
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 5);

		add_filter('script_loader_tag', array($this, 'handle_script_loader_tag'), 100, 3);
		add_action('wp_ajax_shipflex/save_rule', array($this, 'save_rule'));
	}

	/**
	 * Load files
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function load_files() {
		require_once ShipFlex_PATH . 'inc/admin/class-rule-list.php';
		require_once ShipFlex_PATH . 'inc/admin/class-rule-editor.php';
		require_once ShipFlex_PATH . 'inc/admin/class-shipping-editor.php';
	}

	/**
	 * Add menu pages
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_menu() {
		add_menu_page(
			esc_html__('ShipFlex Rules', 'shipflex'),
			esc_html__('ShipFlex', 'shipflex'),
			'manage_woocommerce',
			'shipflex',
			array($this, 'rule_list_screen'),
			ShipFlex_URI . 'assets/menu-icon.png',
			56
		);

		$shipflex_menu = add_submenu_page(
			'shipflex',
			esc_html__('All ShipFlex Rules', 'shipflex'),
			esc_html__('All Rules', 'shipflex'),
			'manage_woocommerce',
			'shipflex',
			array($this, 'rule_list_screen'),
		);

		if (!isset($_GET['id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			add_action("load-$shipflex_menu", array($this->rule_list, 'screen_option'));
		}

		$add_rule_label = esc_html__('Add Rule', 'shipflex');
		if (!empty($_GET['id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$add_rule_label = esc_html__('Edit Rule', 'shipflex');
		}

		add_submenu_page(
			'shipflex',
			esc_html__('Edit Rule', 'shipflex'),
			$add_rule_label,
			'manage_woocommerce',
			'shipflex-edit',
			array(new Rule_Editor(), 'screen_editor')
		);
	}

	/**
	 * Save rule
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function save_rule() {
		if (!isset($_POST['id'])  || !isset($_POST['nonce']) || !isset($_POST['data'])) {
			wp_send_json_error(array('message' => esc_html__('Required data missing.', 'shipflex')));
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'shipflex/save_rule_nonce')) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipflex')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to save data.', 'shipflex')));
		}

		$rule_data = json_decode(sanitize_text_field(wp_unslash($_POST['data'])), true);

		if (!is_array($rule_data) || empty($rule_data)) {
			wp_send_json_error(array('message' => esc_html__('Invalid data.', 'shipflex')));
		}

		$rule = ShipFlex_Rule::get(sanitize_text_field(wp_unslash($_POST['id'])));
		$rule->set_data($rule_data);
		$rule->save();

		$new_edit_url = add_query_arg('id', $rule->get_id(), admin_url('admin.php?page=shipflex-edit'));
		wp_send_json_success(array(
			'id' => $rule->get_id(),
			'edit_url' => $new_edit_url,
			'is_new' => $rule->is_new(),
		));
	}

	/**
	 * Register scripts
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function register_scripts() {
		wp_register_style('shipflex-select2', ShipFlex_URI . 'assets/select2.min.css', array(), '4.1.0');
		wp_register_style('shipflex-global', ShipFlex_URI . 'assets/global.min.css', array(), Utils::get_plugin_version());

		$style_dependencies = apply_filters('shipflex/admin_enqueue_scripts', array(), 'styles');
		wp_register_style('shipflex-admin', ShipFlex_URI . 'assets/admin.min.css', $style_dependencies, Utils::get_plugin_version());

		wp_register_script('shipflex-vue', ShipFlex_URI . 'assets/vue.min.js', [], '3.5.22', true);
		wp_register_script('shipflex-sortable', ShipFlex_URI . 'assets/sortable.min.js', array(), '1.15.6', true);
		wp_register_script('shipflex-vue-sortable', ShipFlex_URI . 'assets/vue-sortable.min.js', array('shipflex-vue', 'shipflex-sortable'), '1.0.7', true);
		wp_register_script('shipflex-rule-editor', ShipFlex_URI . 'assets/rule-editor.min.js', array('jquery', 'wp-hooks', 'select2', 'wp-i18n', 'shipflex-vue-sortable'), Utils::get_plugin_version(), true);

		$scripts_dependencies = apply_filters('shipflex/admin_enqueue_scripts', array('jquery'), 'scripts');
		wp_register_script('shipflex-admin', ShipFlex_URI . 'assets/admin.min.js', $scripts_dependencies, Utils::get_plugin_version(), true);
	}

	/**
	 * Enqueue script on the supported page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style('shipflex-admin');
		wp_enqueue_script('shipflex-admin');
		wp_enqueue_style('shipflex-global');

		$localize_script_values = apply_filters('shipflex/admin_enqueue_scripts', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'statuses' => Utils::get_statuses()
		), 'localize');

		wp_localize_script('shipflex-admin', 'shipflex_admin', $localize_script_values);
	}

	/**
	 * Handle script loader tag to convert text/javascript to module
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_script_loader_tag($tag, $handle, $src) {
		if ('shipflex-rule-editor' === $handle) {
			$tag = str_replace('<script ', '<script type="module" ', $tag);
		}

		return $tag;
	}

	/**
	 * Admin screen of plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function rule_list_screen() {
		require_once ShipFlex_PATH . 'inc/admin/class-rules-table.php';

		$rule_list_table = new Rule_List_Table();
		$rule_list_table->prepare_items();

		echo '<div id="shipflex" class="wrap">';
		echo '<div class="shipflex-wp-heading">';
		echo '<h1 class="wp-heading-inline">' . esc_html__('ShipFlex Rules', 'shipflex') . '</h1>';
		echo '<a href="' . esc_url(menu_page_url('shipflex-edit', false)) . '" class="page-title-action">' . esc_html__('Add new rule', 'shipflex') . '</a>';
		echo '</div>';
		echo '<hr class="wp-header-end">';

		echo '<form method="post">';
		$rule_list_table->display();
		echo '</form>';
		echo '</div>';
	}
}


Admin::get_instance();
