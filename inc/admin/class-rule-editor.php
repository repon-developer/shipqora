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
					'short_title' => esc_html__('Subtotal', 'shipflex'),
					'short_lower' => esc_html__('subtotal', 'shipflex'),
					'long_title' => esc_html__('Product Subtotal', 'shipflex'),
					'long_lower' => esc_html__('product subtotal', 'shipflex'),
				),

				'quantity' => array(
					'short_title' => esc_html__('Quantity', 'shipflex'),
					'short_lower' => esc_html__('quantity', 'shipflex'),
					'long_title' => esc_html__('Product Quantity', 'shipflex'),
					'long_lower' => esc_html__('product quantity', 'shipflex'),
				),

				'weight' => array(
					'short_title' => esc_html__('Weight', 'shipflex'),
					'short_lower' => esc_html__('weight', 'shipflex'),

					'long_title' => sprintf(
						/* translators: %s: weight unit */
						esc_html__('Product Weight (%s)', 'shipflex'),
						$weight_label
					),

					'long_lower' => sprintf(
						/* translators: %s: weight unit */
						esc_html__('product weight (%s)', 'shipflex'),
						$weight_label
					),
				),

				'volume' => array(
					'short_title' => esc_html__('Volume', 'shipflex'),
					'short_lower' => esc_html__('volume', 'shipflex'),

					'long_title' => sprintf(
						/* translators: %s: dimension unit */
						esc_html__('Product Volume (%s)', 'shipflex'),
						$dimension_label
					),

					'long_lower' => sprintf(
						/* translators: %s: dimension unit */
						esc_html__('product volume (%s)', 'shipflex'),
						$dimension_label
					),
				),
			);

			$values['debugging_nonce'] = Debugging::get_instance()->get_nonce_value();
			$values['is_debugging_enabled'] = Debugging::get_instance()->is_debugging_mode_enabled() ? 'yes' : 'no';

			//error_log(print_r($values, true));
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
		Component\Shipping_Cost_Range_Tier::output_component(); ?>

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
					<h1 class="wp-heading-inline"><?php esc_html_e('Edit Rule', 'shipflex') ?></h1>
					<a class="button" href="<?php menu_page_url('shipflex-edit') ?>"><?php esc_html_e('Add a Rule', 'shipflex') ?></a>
				</div>
				<hr class="wp-header-end">

				<div class="shipflex-editor-container">
					<div class="rule-title">
						<input v-model="title" type="text" placeholder="<?php esc_attr_e('Please enter a rule title', 'shipflex') ?>">
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
						<template v-if="active_features?.includes('<?php echo esc_attr($feature_id)  ?>')">
							<?php $feature_instance->output_rule_editor($settings_fields); ?>
						</template>
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
							<h2 v-if="current_modal == 'cart-option-advanced'">🚀 Unlock Advanced Cart Calculations</h2>
							<span class="btn-modal-close dashicons dashicons-no-alt" @click.prevent="current_modal = null"></span>
						</header>

						<div class="modal-body">
							<template v-if="current_modal == 'cart-option-advanced'">
								<p>Unlock advanced calculation options based on cart items in the <strong>selected products</strong> or <strong>selected product variations</strong>.</p>

								<h3 style="margin-block: 5px;">Advanced options available in the Pro version:</h3>
								<ul style="margin-top: 0;font-size: 15px">
									<li><strong>Products</strong>: Checks only cart items that match the selected products.</li>
									<li><strong>Product variations</strong>: Checks only cart items that match the selected product variations.</li>
								</ul>
								<p>Upgrade to the <strong>Pro version</strong> to calculate subtotal, quantity, weight, or volume using only the matching products or product variations in the cart.</p>
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
