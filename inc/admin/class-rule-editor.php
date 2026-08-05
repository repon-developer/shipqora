<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

final class Rule_Editor {

	/**
	 * Constructor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		add_action('admin_footer', array($this, 'output_vue_component'));
		add_filter('shipflex/admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 2);
	}

	/**
	 * Add dependencies for rule edit form
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function enqueue_scripts($values, $source) {
		if (!Utils::is_plugin_screen('rule-editor')) {
			return $values;
		}

		if ('scripts' == $source) {
			$values[] = 'shipflex-rule-editor';
		}

		if ('localize' == $source) {
			$main_condition = Condition\Main::get_instance();

			$values['condition_models'] = $main_condition->get_models();
			$values['save_rule_nonce'] = wp_create_nonce('shipflex/save_rule_nonce');

			$shipflex_rule = new ShipFlex_Rule();
			if (!empty($_GET['id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$shipflex_rule = ShipFlex_Rule::get(sanitize_text_field(wp_unslash($_GET['id']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			$values['rule_data'] = $shipflex_rule->get_models();

			$registered_features = Feature::get_features();
			foreach ($registered_features as $feature_id => $feature_instance) {
				$settings_fields = Settings_Fields::get_instance($feature_id);
				$values['features'][$feature_id] = $settings_fields->get_models();
			}

			$shipping_method_options = array();
			$registered_shipping_methods = WC()->shipping()->get_shipping_methods();
			foreach ($registered_shipping_methods as $shipping_id => $shipping_method) {
				$shipping_method_options[$shipping_id] = $shipping_method->get_method_title();
			}

			unset($shipping_method_options['local_pickup'], $shipping_method_options['pickup_location']);
			$values['shipping_methods'] = $shipping_method_options;

			$weight_label = get_option('woocommerce_weight_unit');
			if (empty($weight_label)) {
				$weight_label = 'weight';
			}

			$dimension_label = get_option('woocommerce_dimension_unit');
			if (empty($dimension_label)) {
				$dimension_label = 'unit';
			}

			$values['calculation_types'] = array(
				'subtotal' => esc_html__('Percentage', 'shipflex'),
				'quantity' => esc_html__('Cost per Item', 'shipflex'),
				'weight' => sprintf(
					/* translators: %s: weight unit */
					esc_html__('Cost per %s', 'shipflex'),
					$weight_label
				),

				'volume' => sprintf(
					/* translators: %s: weight unit */
					esc_html__('Cost per %s', 'shipflex'),
					$dimension_label
				)
			);

			$values['calculation_metrics'] = array(
				'subtotal' => array(
					'short_lower' => esc_html__('subtotal', 'shipflex'),
					'long_title' => esc_html__('Product Subtotal', 'shipflex'),
				),

				'quantity' => array(
					'short_lower' => esc_html__('quantity', 'shipflex'),
					'long_title' => esc_html__('Product Quantity', 'shipflex'),
				),

				'weight' => array(
					'short_lower' => esc_html__('weight', 'shipflex'),
					'long_title' => sprintf(
						/* translators: %s: weight unit */
						esc_html__('Product Weight (%s)', 'shipflex'),
						$weight_label
					)
				),

				'volume' => array(
					'short_lower' => esc_html__('volume', 'shipflex'),
					'long_title' => sprintf(
						/* translators: %s: dimension unit */
						esc_html__('Product Volume (%s)', 'shipflex'),
						$dimension_label
					),
				),
			);

			$values['debugging_nonce'] = Debugging::get_instance()->get_nonce_value();
			$values['is_debugging_enabled'] = Debugging::get_instance()->is_debugging_mode_enabled() ? 'yes' : 'no';
		}

		return $values;
	}

	/**
	 * Add vuejs component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_vue_component() {
		if (!Utils::is_plugin_screen('rule-editor')) {
			return;
		}

		$registered_features = Feature::get_features();
		foreach ($registered_features as $feature_id => $feature_instance) {
			if (method_exists($feature_instance, 'output_component')) {
				echo '<template id="shipflex-' . esc_attr($feature_id) . '-feature-component">';
				$feature_instance->output_component();
				echo '</template>';
			}
		}

		Condition\Main::output_component();
		Component\Cart_Option::output_component();

		do_action(Utils::get_hook_name('rule-editor', 'output-vue-component')) ?>

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

						<div class="current-status-info" v-if="id > 0" v-html="get_current_status_info"></div>

						<div class="separator"></div>

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
