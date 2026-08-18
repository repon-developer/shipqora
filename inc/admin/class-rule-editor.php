<?php

namespace ShipQora;

use ShipQora\Condition\Main;

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
		add_filter('shipqora/admin_enqueue_scripts', array($this, 'enqueue_scripts'), 10, 2);
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
			$values[] = 'shipqora-rule-editor';
		}

		if ('localize' == $source) {
			$main_condition = Condition\Main::get_instance();

			$values['condition_models'] = $main_condition->get_models();
			$values['save_rule_nonce'] = wp_create_nonce('shipqora/save_rule_nonce');

			$shipqora_rule = new ShipQora_Rule();
			if (!empty($_GET['id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$shipqora_rule = ShipQora_Rule::get(sanitize_text_field(wp_unslash($_GET['id']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			$values['rule_data'] = $shipqora_rule->get_models();

			$registered_features = Feature::get_features();
			foreach ($registered_features as $feature_id => $feature_instance) {
				$settings_fields = Settings_Fields::get_instance($feature_id);
				$values['features'][$feature_id] = $settings_fields->get_models();
			}



			$weight_label = get_option('woocommerce_weight_unit');
			if (empty($weight_label)) {
				$weight_label = 'weight';
			}

			$dimension_label = get_option('woocommerce_dimension_unit');
			if (empty($dimension_label)) {
				$dimension_label = 'unit';
			}

			$values['calculation_types'] = array(
				'subtotal' => esc_html__('Percentage', 'shipqora'),
				'quantity' => esc_html__('Cost per Item', 'shipqora'),
				'weight' => sprintf(
					/* translators: %s: weight unit */
					esc_html__('Cost per %s', 'shipqora'),
					$weight_label
				),

				'volume' => sprintf(
					/* translators: %s: weight unit */
					esc_html__('Cost per %s', 'shipqora'),
					$dimension_label
				)
			);

			$values['calculation_metrics'] = array(
				'subtotal' => array(
					'short_lower' => esc_html__('subtotal', 'shipqora'),
					'long_title' => esc_html__('Product Subtotal', 'shipqora'),
				),

				'quantity' => array(
					'short_lower' => esc_html__('quantity', 'shipqora'),
					'long_title' => esc_html__('Product Quantity', 'shipqora'),
				),

				'weight' => array(
					'short_lower' => esc_html__('weight', 'shipqora'),
					'long_title' => sprintf(
						/* translators: %s: weight unit */
						esc_html__('Product Weight (%s)', 'shipqora'),
						$weight_label
					)
				),

				'volume' => array(
					'short_lower' => esc_html__('volume', 'shipqora'),
					'long_title' => sprintf(
						/* translators: %s: dimension unit */
						esc_html__('Product Volume (%s)', 'shipqora'),
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
				echo '<template id="shipqora-' . esc_attr($feature_id) . '-feature-component">';
				$feature_instance->output_component();
				echo '</template>';
			}
		}

		Component\Cart_Option::output_component();
		Main::get_instance()->output_component();

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		do_action(Utils::get_hook_name('rule-editor', 'output-vue-component')) ?>

		<template id="shipqora-shipping-methods-group-component">
			<ul class="shipqora-repeater" v-if="shipping_methods?.length" style="margin-bottom: 8px;" v-sortable="{options: {handle: '.button-drag-item'}}" @end="order_change">
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
				<?php esc_html_e('Add Shipping Method', 'shipqora') ?>
			</a>
		</template>

		<template id="shipqora-shipping-method-input-component">
			<span class="button-drag-item dashicons dashicons-menu-alt2" v-if="!loading && draggable"></span>

			<?php
			$shipping_method_options = array();
			foreach (WC()->shipping()->get_shipping_methods() as $shipping_id => $shipping_method) {
				$shipping_method_options[$shipping_id] = $shipping_method->get_method_title();
			}

			unset($shipping_method_options['local_pickup']); ?>

			<select v-model="method_id">
				<option value=""><?php esc_html_e('Choose a shipping method', 'shipqora') ?></option>
				<?php foreach ($shipping_method_options as $method_id => $method_title) {
					printf('<option value="%s">%s</option>', esc_attr($method_id), esc_html($method_title));
				} ?>
			</select>

			<select2-dropdown
				:multiple="false"
				:is-loading="loading"
				type="shipping_instances"
				:initial-value="instance_id"
				:options="shipping_instances"
				v-if="loading || has_shipping_instance"
				@update="(value) => instance_id = value"
				:placeholder="'pickup_location' == method_id ? '<?php esc_html_e('All locations', 'shipqora') ?>' : '<?php esc_html_e('All shipping rates', 'shipqora') ?>'">
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
		<div id="shipqora" class="wrap shipqora-rule-editor">
			<div class="shipqora-loading-app" v-if="loading">
				<div class="shipqora-loading-spinner"></div>
				<div><?php esc_html_e('Loading...', 'shipqora') ?></div>
				<div class="loading-instruction">
					<?php
					printf(
						/* translators: %1$s: Mail link open, %2$s: Mail link close */
						esc_html__('If it takes more than 30 seconds, please reload the page. If the issue persists, check the browser console for errors and %1$ssend email%2$s us.', 'shipqora'),
						'<a href="mailto:support@shipqora.com">',
						'</a>',
					) ?>
				</div>
			</div>

			<template v-if="!loading">
				<div class="shipqora-wp-heading">
					<h1 class="wp-heading-inline">
						<?php printf(
							/* translators: %s: For ShipQora rule title */
							esc_html__('Edit Rule%s', 'shipqora'),
							'<strong>{{rule_title}}</strong>'
						) ?>
					</h1>
					<a class="button" href="<?php menu_page_url('shipqora-edit') ?>"><?php esc_html_e('Add a Rule', 'shipqora') ?></a>
				</div>
				<hr class="wp-header-end">

				<div class="shipqora-editor-container">
					<div class="rule-title" data-highlight-section="shipqora-rule-title">
						<input v-model="title" type="text" placeholder="<?php esc_attr_e('Enter rule title (e.g., Free Shipping Over $50)', 'shipqora') ?>">
					</div>

					<table class="table-shipqora-form">
						<thead>
							<tr>
								<td colspan="2"><?php esc_html_e('General Settings', 'shipqora') ?></td>
							</tr>
						</thead>

						<?php $settings_fields->output_fields('general'); ?>
					</table>

					<?php foreach ($registered_features as $feature_id => $feature_instance) : ?>
						<table class="table-shipqora-form" v-if="active_features?.includes('<?php echo esc_attr($feature_id) ?>')" <?php $feature_instance->output_wrapper_attributes() ?>>
							<thead>
								<tr>
									<td colspan="2">
										<?php echo esc_html($feature_instance->get_configuration('section_title')) ?>
									</td>
								</tr>
							</thead>

							<?php $settings_fields->output_fields($feature_instance->get_id()); ?>
						</table>
					<?php endforeach; ?>

					<footer class="form-footer">
						<button class="button button-primary button-large" :class="{'in-progress': saving}" @click.prevent="save_rule()">
							<?php esc_html_e('Save ShipQora Rule', 'shipqora') ?>
						</button>

						<div class="current-status-info" v-if="id > 0" v-html="get_current_status_info"></div>

						<div class="separator"></div>

						<div class="review-request">
							If you enjoy this plugin, please <a href="https://wordpress.org/support/plugin/shipqora/reviews/#new-post" target="_blank">leave us</a> a 5-star review and help it grow! ⭐⭐⭐⭐⭐
						</div>
					</footer>
				</div>

				<div :class="{'shipqora-toast-box': true, shown: show_toast_message}" :data-type="toast_message_type" v-html="toast_message"></div>

				<div class="shipqora-modal" :class="{shown: current_modal !== null}">
					<div class="modal-content">
						<header class="modal-header">
							<h2 v-if="current_modal == 'cart-option-advanced'">🚀 Unlock Advanced Product Targeting</h2>
							<h2 v-if="current_modal == 'advanced-condition-types'">🚀 Unlock Advanced Pro Conditions</h2>
							<span class="btn-modal-close dashicons dashicons-no-alt" @click.prevent="current_modal = null"></span>
						</header>

						<div class="modal-body">
							<template v-if="current_modal == 'cart-option-advanced'">
								<p>Target specific products or individual variations to calculate shipping costs based only on matching items in the cart.</p>

								<h3 style="margin-block: 5px;">What you can do with Pro:</h3>
								<ul style="margin-top: 0">
									<li><strong>Products</strong>: Filter and calculate costs using only specific chosen products in the cart.</li>
									<li><strong>Product variations</strong>: Target specific product variations and attributes (e.g., T-Shirt — Large / Blue) so shipping rules apply strictly to variable product selections.</li>
								</ul>
								<p>Upgrade to <strong>ShipQora Pro</strong> to unlock per-product filtering and advanced cart calculation rules.</p>
							</template>

							<template v-if="current_modal == 'advanced-condition-types'">
								<h4>Take full control over when your shipping rules apply.</h4>
								<p>Upgrading to ShipQora Pro unlocks advanced condition rules that let you target exact customer criteria, specific cart items, and detailed location data with precision:</p>

								<ul style="margin-top: 0">
									<li><strong>Cart Products:</strong> Target rules down to specific <strong>Products</strong> or individual <strong>Product Variations</strong> present in the cart.</li>
									<li><strong>Location Targeting:</strong> Match rules precisely using customer Shipping Post Codes / ZIP codes.</li>
									<li><strong>Customer Roles:</strong> Trigger shipping options dynamically based on logged-in <strong>User Roles</strong> (e.g., VIP, Wholesale, Subscriber).</li>
								</ul>
								<p>Fine-tune your store's shipping options to protect profit margins, offer targeted discounts, and deliver a seamless checkout experience.</p>
							</template>
						</div>
						<footer class="modal-footer">
							<a href="#" class="button btn-modal-close" @click.prevent="current_modal = null"><?php echo esc_html_e('Back', 'shipqora') ?></a>
							<?php Utils::get_lite_button() ?>
						</footer>
					</div>
				</div>
			</template>
		</div>
<?php
	}
}
