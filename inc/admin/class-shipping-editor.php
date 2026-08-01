<?php

namespace ShipFlex;

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
		add_action('admin_footer', array($this, 'output_vue_component'));
		add_action('init', array($this, 'add_shipping_notice_field'), 1000);
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('wp_ajax_shipflex/get_attached_rule', array($this, 'get_attached_rule'));
		add_action('wp_ajax_shipflex/create_and_attach_rule', array($this, 'create_and_attach_rule'));
		add_filter('woocommerce_generate_shipflex_notice_html', array($this, 'output_setting_field'), 10);
	}

	/**
	 * Check if currently opened shipping editor screen
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_shipping_editor_screen() {
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
			wp_send_json_error(array('message' => esc_html__('Required data missing.', 'shipflex')));
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'shipflex/shipping-editor-nonce')) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipflex')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to create and attach ShipFlex rule.', 'shipflex')));
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

		$rule = new ShipFlex_Rule(array(
			'title' => $shipping_method_title,
			'shipping_methods' => array($instance_slug)
		));

		$rule->save();

		$edit_url = add_query_arg('id', $rule->get_id(), admin_url('admin.php?page=shipflex-edit'));
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
			wp_send_json_error(array('message' => esc_html__('Required data missing.', 'shipflex')));
		}

		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'shipflex/shipping-editor-nonce')) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipflex')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to create and attach ShipFlex rule.', 'shipflex')));
		}

		$instance_id = sanitize_text_field(wp_unslash($_POST['instance_id']));
		$rules = ShipFlex_Rule::get_by_instance_id($instance_id);

		$attached_rules = array();
		foreach ($rules as $rule) {
			$attached_rules[] = array(
				'title' => $rule->title,
				'status' => $rule->status,
				'url' => add_query_arg('id', $rule->get_id(), admin_url('admin.php?page=shipflex-edit'))
			);
		}

		wp_send_json_success($attached_rules);
	}

	/**
	 * Add dependencies for rule edit form
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function admin_enqueue_scripts() {
		if (!$this->is_shipping_editor_screen()) {
			return;
		}

		$instance_id = isset($_GET['instance_id']) ? $_GET['instance_id'] : null;
		wp_enqueue_script('shipflex-shipping-editor', ShipFlex_URI . 'assets/shipping-editor.min.js', array('jquery', 'shipflex-vue'), Utils::get_plugin_version(), true);
		wp_localize_script('shipflex-shipping-editor', 'shipflex_shipping_editor', array(
			'instance_id' => $instance_id,
			'nonce' => wp_create_nonce('shipflex/shipping-editor-nonce')
		));
	}

	public function add_shipping_notice_field() {
		$methods = WC()->shipping->get_shipping_methods();
		foreach ($methods as $method) {
			add_filter('woocommerce_shipping_instance_form_fields_' . $method->id, array($this, 'add_setting_field'), 100000);
		}
	}

	public function add_setting_field($settings) {
		$settings['shipflex_notice'] = array(
			'title' => __('ShipFlex', 'woocommerce'),
			'default' => '', //Don't remove this one. Otherwise system will show error
			'type' => 'shipflex_notice',
		);

		return $settings;
	}

	public function output_setting_field() {
		ob_start(); ?>

		<tr class="shipflex-shipping-editor-notice-row" valign="top">
			<th scope="row">
				<label><?php esc_html_e('ShipFlex', 'shipflex') ?></label>
			</th>
			<td class="forminp">
				<div id="shipflex" class="shipflex-shipping-editor">
					<div class="shipflex-content-loader" v-if="loading">
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
									/* translators: %s: for ShipFlex Rule */
									esc_html__('Create a %s to automatically attach this method and unlock custom rate logic and dynamic conditions. Active rules will take precedence over default settings.', 'shipflex'),
									'<strong>ShipFlex Rule</strong>',
								) ?>
							</div>
							<div class="gap-5"></div>
							<a @click.prevent="create_rule()" class="button button-primary" :class="{'in-progress': creating_rule}" href="#"><?php esc_html_e('+ Create & Attach ShipFlex Rule', 'shipflex') ?></a>
						</template>

						<template v-if="created_rule !== null">
							<h3><?php esc_html_e('Successfully Created!', 'shipflex') ?></h3>
							<div class="description">
								<?php
								printf(
									/* translators: %s: for ShipFlex Rule, %s: URL of created rule */
									esc_html__('A new %s %s has been created and linked to this shipping method. Configure your custom conditions and pricing logic to activate it.', 'shipflex'),
									'<strong>ShipFlex Rule</strong>',
									'<a :href="created_rule.url" target="_blank" v-html="created_rule?.title"></a>'
								) ?>
							</div>
							<div class="gap-5"></div>
							<a class="button button-primary" :href="created_rule?.url" target="_blank"><?php esc_html_e('Configure Rule', 'shipflex') ?></a>
						</template>

						<template v-if="attached_rules && attached_rules?.length > 0">
							<h3><?php esc_html_e('Connected ShipFlex Rules', 'shipflex') ?></h3>
							<div class="description">
								<?php
								printf(
									/* translators: %s: for ShipFlex Rule, %s: URL of created rule */
									esc_html__('Below are the custom rules configured for this shipping method. You can enable, disable, or adjust rule priorities from your %s settings.', 'shipflex'),
									'<strong>ShipFlex Rule</strong>',
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

	/**
	 * Add vuejs component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_vue_component() {
	?>

		<template id="shipflex-product-input-component">
			<div class="shipflex-content-loader" v-if="loading">
				<div class="loader-item loader-title"></div>
				<div class="loader-item loader-text"></div>
				<div class="loader-item loader-text short"></div>
			</div>

			<span class="button-drag-item dashicons dashicons-menu-alt2" v-if="draggable && !loading"></span>

			<select2-dropdown
				:multiple="false"
				:type="select2_type"
				child-key="variations"
				:initial-value="product_id"
				@onloading="(value) => loading = value"
				@update="(value) => product_id = value"
				@update-childs="(variations) => product_variations = variations"
				placeholder="<?php esc_html_e('Choose a product', 'shipflex') ?>">
			</select2-dropdown>

			<select v-model="variation_id" v-if="product_variations.length > 0">
				<option v-if="!hideDefaultOption" value="0">{{defaultOptionLabel}}</option>
				<option v-for="variation in product_variations" :value="variation.id">{{ variation.name }}</option>
			</select>

			<template v-if="!loading">
				<slot></slot>

				<div class="tools" v-if="!hideTools">
					<a href="#" class="btn-delete-item dashicons dashicons-no-alt" @click.prevent="delete_item()"></a>
				</div>
			</template>
		</template>

		<template id="shipflex-shipping-methods-group-component">
			<ul class="shipflex-repeater" v-if="shipping_methods?.length" style="margin-bottom: 8px;">
				<li class="repeater-item" v-for="(shipping_method, index) in shipping_methods" :key="shipping_method">
					<shipping-method-input
						:shipping-method="shipping_method"
						@delete="delete_shipping_method(index)"
						:draggable="shipping_methods?.length > 1"
						@update="(value) => shipping_methods[index] = value">
					</shipping-method-input>
				</li>
			</ul>

			<a href="#" class="button" :class="button_class" @click.prevent="add_shipping_method()">
				<?php esc_html_e('Add Shipping Method', 'shipflex') ?>
			</a>
		</template>

		<template id="shipflex-shipping-method-input-component">
			<span class="button-drag-item dashicons dashicons-menu-alt2" v-if="!loading && draggable"></span>

			<select v-model="method_id">
				<option value=""><?php esc_html_e('Choose a shipping method', 'shipflex') ?></option>
				<option v-for="(method_label, method_id) in registered_shipping_methods" :value="method_id" :key="method_id">{{method_label}}</option>
			</select>

			<select2-dropdown
				:multiple="false"
				:is-loading="loading"
				type="shipping_instances"
				:initial-value="instance_id"
				:options="shipping_instances"
				v-if="loading || has_shipping_instance"
				@update="(value) => instance_id = value"
				placeholder="<?php esc_html_e('All shipping rates', 'shipflex') ?>">
			</select2-dropdown>

			<div class="tools" v-if="!loading">
				<a href="#" @click.prevent="delete_item()" class="btn-delete-item dashicons dashicons-no-alt"></a>
			</div>
		</template>
	<?php
	}

	/**
	 * Rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function screen_editor() {
		$registered_features = Feature::get_features();
		$settings_fields = Settings_Fields::get_instance('rule-editor') ?>
		<div id="shipflex" class="wrap shipflex-rule-editor">
			<div class="shipflex-loading-app" v-if="loading">
				<div class="shipflex-loading-spinner"></div>
				<div><?php esc_html_e('Loading...', 'shipflex') ?></div>
				<div class="loading-instruction">
					<?php
					printf(
						esc_html__('If it takes more than 30 seconds, please reload the page. If the issue persists, check the browser console for errors and %ssend email%s us.', 'shipflex'),
						'<a href="mailto:support@shipflexpro.com">',
						'</a>',
					) ?>
				</div>
			</div>

			<template v-if="!loading">
				<div class="shipflex-wp-heading">
					<h1 class="wp-heading-inline">
						<?php printf(esc_html__('Edit Rule%s', 'shipflex'), '<strong>{{rule_title}}</strong>') ?>
					</h1>
					<a class="button" href="<?php menu_page_url('shipflex-edit') ?>"><?php esc_html_e('Add a Rule', 'shipflex') ?></a>
				</div>
				<hr class="wp-header-end">

				<div class="shipflex-editor-container">
					<div class="rule-title" data-highlight-section="shipflex-rule-title">
						<input v-model="title" type="text" placeholder="<?php esc_attr_e('Enter rule title (e.g., Free Shipping Over $50)', 'shipflex') ?>">
					</div>

					<table class="table-shipflex-form">
						<thead>
							<tr>
								<td colspan="2"><?php esc_html_e('General Settings', 'shipflex') ?></td>
							</tr>
						</thead>

						<?php $settings_fields->output_fields('general'); ?>
					</table>

					<?php foreach ($registered_features as $feature_id => $feature_instance) : ?>
						<table class="table-shipflex-form" v-if="active_features?.includes('<?php echo esc_attr($feature_id) ?>')" <?php $feature_instance->output_wrapper_attributes() ?>>
							<thead>
								<tr>
									<td colspan="2">
										<?php echo esc_html($feature_instance->get_configuration_value('section_title')) ?>
									</td>
								</tr>
							</thead>

							<?php $settings_fields->output_fields($feature_instance->get_id()); ?>
						</table>
					<?php endforeach; ?>

					<footer class="form-footer">
						<button class="button button-primary button-large" :class="{'in-progress': saving}" @click.prevent="save_rule()">
							<?php esc_html_e('Save ShipFlex Rule', 'shipflex') ?>
						</button>

						<div class="review-request">
							If you enjoy this plugin, please <a href="https://wordpress.org/support/plugin/shipflex/reviews/#new-post" target="_blank">leave us</a> a 5-star review and help it grow! ⭐⭐⭐⭐⭐
						</div>
					</footer>
				</div>

				<div :class="{'shipflex-toast-box': true, shown: show_toast_message}" :data-type="toast_message_type" v-html="toast_message"></div>

				<div class="shipflex-modal" :class="{shown: current_modal !== null}">
					<div class="modal-content">
						<header class="modal-header">
							<h2 v-if="current_modal == 'cart-option-advanced'">🚀 Unlock Advanced Product Targeting</h2>
							<span class="btn-modal-close dashicons dashicons-no-alt" @click.prevent="current_modal = null"></span>
						</header>

						<div class="modal-body">
							<template v-if="current_modal == 'cart-option-advanced'">
								<p>Target specific products or individual variations to calculate shipping costs based only on matching items in the cart.</p>

								<h3 style="margin-block: 5px;">What you can do with Pro:</h3>
								<ul style="margin-top: 0;font-size: 15px">
									<li><strong>Products</strong>: Filter and calculate costs using only specific chosen products in the cart.</li>
									<li><strong>Product variations</strong>: Target specific product variations and attributes (e.g., T-Shirt — Large / Blue) so shipping rules apply strictly to variable product selections.</li>
								</ul>
								<p>Upgrade to <strong>ShipFlex Pro</strong> to unlock per-product filtering and advanced cart calculation rules.</p>
							</template>
						</div>
						<footer class="modal-footer">
							<a href="#" class="button btn-modal-close" @click.prevent="current_modal = null"><?php echo esc_html_e('Back', 'shipflex') ?></a>
							<?php Utils::get_lite_button() ?>
						</footer>
					</div>
				</div>
			</template>
		</div>
<?php
	}
}


new Shipping_Editor();
