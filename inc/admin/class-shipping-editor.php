<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

final class Shipping_Editor {

	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('woocommerce_init', array($this, 'add_shipping_notice_field'), 1000);
		add_action('wp_ajax_shipqora/get_attached_rule', array($this, 'get_attached_rule'));
		add_action('wp_ajax_shipqora/create_and_attach_rule', array($this, 'create_and_attach_rule'));
		add_filter('woocommerce_generate_shipqora_notice_html', array($this, 'output_setting_field'), 10);
	}

	/**
	 * Check if currently opened shipping editor screen
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_shipping_editor_screen() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset($_GET['page']) && 'wc-settings' == $_GET['page'] && isset($_GET['tab']) && 'shipping' == $_GET['tab'];
	}

	/**
	 * Create and attach rule with shipping method
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function create_and_attach_rule() {
		if (!isset($_POST['instance_id'])  || !isset($_POST['nonce'])) {
			wp_send_json_error(array('message' => esc_html__('Required data missing.', 'shipqora')));
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'shipqora/shipping-editor-nonce')) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipqora')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to create and attach ShipQora rule.', 'shipqora')));
		}

		$instance_id = sanitize_text_field(wp_unslash($_POST['instance_id']));
		$zone = \WC_Shipping_Zones::get_zone_by('instance_id', $instance_id);
		$zone_id = $zone->get_id();

		$shipping_method_title = !empty($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : null;

		$shipping_method = \WC_Shipping_Zones::get_shipping_method($instance_id);
		if (empty($shipping_method_title)) {
			$shipping_method_title = $shipping_method->get_title();
		}

		$shipping_method_title .= ' #' . $shipping_method->get_instance_id();

		$instance_slug = sprintf(
			'%s:%d-%d',
			$shipping_method->id,
			$zone->get_id(),
			$shipping_method->get_instance_id()
		);

		$rule = new SHIPQORA_Rule(array(
			'title' => $shipping_method_title,
			'shipping_methods' => array($instance_slug)
		));

		$rule->save();

		$edit_url = add_query_arg('id', $rule->get_id(), admin_url('admin.php?page=shipqora-edit'));
		wp_send_json_success(array(
			'id' => $rule->get_id(),
			'url' => $edit_url,
			'title' => $rule->title
		));
	}

	/**
	 * Create and attach rule with shipping method
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_attached_rule() {
		if (!isset($_POST['instance_id'])  || !isset($_POST['nonce'])) {
			wp_send_json_error(array('message' => esc_html__('Required data missing.', 'shipqora')));
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'shipqora/shipping-editor-nonce')) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipqora')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to create and attach ShipQora rule.', 'shipqora')));
		}

		$instance_id = sanitize_text_field(wp_unslash($_POST['instance_id']));
		$rules = SHIPQORA_Rule::get_by_instance_id($instance_id);

		$attached_rules = array();
		foreach ($rules as $rule) {
			$attached_rules[] = array(
				'title' => $rule->title,
				'status' => $rule->status,
				'url' => add_query_arg('id', $rule->get_id(), admin_url('admin.php?page=shipqora-edit'))
			);
		}

		wp_send_json_success($attached_rules);
	}

	/**
	 * Enqueue script of shipping editor
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function admin_enqueue_scripts() {
		if (!$this->is_shipping_editor_screen()) {
			return;
		}

		$instance_id = isset($_GET['instance_id']) ? sanitize_text_field(wp_unslash($_GET['instance_id'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_enqueue_script('shipqora-shipping-editor', SHIPQORA_URI . 'assets/shipping-editor.min.js', array('jquery', 'wp-i18n', 'shipqora-vue'), Utils::get_plugin_version(), true);
		wp_localize_script('shipqora-shipping-editor', 'shipqora_shipping_editor', array(
			'instance_id' => $instance_id,
			'nonce' => wp_create_nonce('shipqora/shipping-editor-nonce')
		));
	}

	/**
	 * Add filter for all available shipping methods
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_shipping_notice_field() {
		$methods = WC()->shipping->get_shipping_methods();
		foreach ($methods as $method) {
			add_filter('woocommerce_shipping_instance_form_fields_' . $method->id, array($this, 'add_setting_field'), 100000);
		}
	}

	/**
	 * Add setting field 
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_setting_field($settings) {
		$settings['shipqora_notice'] = array(
			'title' => esc_html__('ShipQora', 'shipqora'),
			'default' => '', //Don't remove this one. Otherwise system will show error
			'type' => 'shipqora_notice',
		);

		return $settings;
	}

	/**
	 * Output ShipQora settings field for shipping editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_setting_field() {
		ob_start(); ?>

		<tr class="shipqora-shipping-editor-notice-row" valign="top">
			<th scope="row">
				<label><?php esc_html_e('ShipQora', 'shipqora') ?></label>
			</th>
			<td class="forminp">
				<div id="shipqora" class="shipqora-shipping-editor">
					<div class="shipqora-content-loader" v-if="loading">
						<div class="loader-item loader-title"></div>
						<div class="loader-item loader-text"></div>
						<div class="loader-item loader-text short"></div>
					</div>
					<template v-if="!loading">
						<template v-if="created_rule == null && !attached_rules?.length">
							<h3>Want full control over this shipping method?</h3>
							<div class="description">
								<?php
								printf(
									/* translators: %s: for ShipQora Rule */
									esc_html__('Create a %s to automatically attach this method and unlock custom rate logic and dynamic conditions. Active rules will take precedence over default settings.', 'shipqora'),
									'<strong>ShipQora Rule</strong>',
								) ?>
							</div>
							<div class="gap-5"></div>
							<a @click.prevent="create_rule()" class="button button-primary" :class="{'in-progress': creating_rule}" href="#"><?php esc_html_e('+ Create & Attach ShipQora Rule', 'shipqora') ?></a>
						</template>

						<template v-if="created_rule !== null">
							<h3><?php esc_html_e('Successfully Created!', 'shipqora') ?></h3>
							<div class="description">
								<?php
								printf(
									/* translators: %1$s: ShipQora Rule text, %2$s: URL of created rule */
									esc_html__('A new %1$s %2$s has been created and linked to this shipping method. Configure your custom conditions and pricing logic to activate it.', 'shipqora'),
									'<strong>ShipQora Rule</strong>',
									'<a :href="created_rule.url" target="_blank" v-html="created_rule?.title"></a>'
								) ?>
							</div>
							<div class="gap-5"></div>
							<a class="button button-primary" :href="created_rule?.url" target="_blank"><?php esc_html_e('Configure Rule', 'shipqora') ?></a>
						</template>

						<template v-if="attached_rules && attached_rules?.length > 0">
							<h3><?php esc_html_e('Connected ShipQora Rules', 'shipqora') ?></h3>
							<div class="description">
								<?php
								printf(
									/* translators: %s: for ShipQora Rule, %s: URL of created rule */
									esc_html__('Below are the custom rules configured for this shipping method. You can enable, disable, or adjust rule priorities from your %s settings.', 'shipqora'),
									'<strong>ShipQora Rule</strong>',
								) ?>
								<ul class="attached-rules" v-if="attached_rules?.length">
									<li v-for="(rule, index) in attached_rules" :key="index">
										<a :href="rule?.url" v-html="rule?.title" target="_blank"></a> - <strong>{{get_status(rule?.status)}}</strong>
									</li>
								</ul>
						</template>
					</template>
				</div>
			</td>
		</tr>
<?php
		return ob_get_clean();
	}
}


new Shipping_Editor();
