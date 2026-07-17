<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Reward_Rule_Form class
 */
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
			$values['save_shipflex_rule_nonce'] = wp_create_nonce('shipflex/save_shipflex_rule_nonce');

			// $values['cart_products_options'] = Component\Cart_Products::get_options();
			// foreach (Component\Cart_Products::get_options() as $type_key => $option) {
			// 	if (!empty($option['model'])) {
			// 		$values['cart_products_models'][$option['model']] = array();
			// 	}
			// }

			$shipflex_rule = new ShipFlex_Rule();
			if (!empty($_GET['id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$shipflex_rule = ShipFlex_Rule::get(sanitize_text_field(wp_unslash($_GET['id']))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}

			$values['rule_models'] = $shipflex_rule->get_models();
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

		Component\Cart_Products::output_component();
		$main_condition = Condition\Main::get_instance(); ?>

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

		<template id="shipflex-condition">
			<select v-model="type">
				<?php
				foreach ($main_condition->get_groups() as $group_key => $group_label) {
					$conditions = $main_condition->get_types_by_group($group_key);
					if (count($conditions) == 0) {
						continue;
					}

					echo '<optgroup label="' . esc_attr($group_label) . '">';
					foreach ($conditions as $key => $condition) {
						echo '<option value="' . esc_attr($key) . '">' . esc_html($condition['label']) . ' </option>';
					}
					echo '</optgroup>';
				} ?>
			</select>

			<?php
			$rendered_templates = array();
			foreach ($main_condition->get_condition_types() as $type_key => $condition_type) {
				if (!isset($condition_type['template']) || !is_callable($condition_type['template'])) {
					continue;
				}

				$model_key = !empty($condition_type['model_key']) ? $condition_type['model_key'] : '';
				$template_id = md5($model_key . maybe_serialize($condition_type['template']));
				if (!in_array($template_id, $rendered_templates)) {
					$rendered_templates[] = $template_id;
					call_user_func($condition_type['template'], $condition_type, $type_key);
				}
			} ?>

			<div class="tools">
				<span @click="delete_condition()" class="btn-delete-item dashicons dashicons-no-alt"></span>
			</div>
		</template>

		<template id="shipflex-condition-group">
			<span @click="delete_group()" class="btn-delete-item btn-delete-group dashicons dashicons-trash"></span>

			<div class="shipflex-repeater" v-if="conditions?.length">
				<template v-for="(condition, index) in conditions" :key="condition.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('and', 'shipflex') ?>"></div>
					<div class="repeater-item">
						<condition :condition="condition" :number="index"></condition>
					</div>
				</template>
			</div>

			<a class="button button-small" href="#" @click.prevent="add_condition()">
				<?php esc_html_e('Add condition', 'shipflex') ?>
			</a>
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
		$rule_id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rule_settings = array('id' => $rule_id); ?>

		<div id="shipflex" class="wrap shipflex-rule-editor" data-settings="<?php echo esc_attr(wp_json_encode($rule_settings)) ?>">
			<div class="loading-setting-app" v-if="loading">
				<div class="shipflex-loading-spinner"></div>
				<div><?php esc_html_e('Loading...', 'shipflex') ?></div>
			</div>

			<template v-if="!loading">
				<div class="shipflex-wp-heading">
					<h1 class="wp-heading-inline"><?php esc_html_e('Edit Rule', 'shipflex') ?></h1>
					<a class="button" href="<?php menu_page_url('shipflex-edit') ?>"><?php esc_html_e('Add Rule', 'shipflex') ?></a>
				</div>
				<hr class="wp-header-end">

				<div class="shipflex-editor-container">
					<div class="rule-title">
						<input v-model="title" type="text" placeholder="<?php esc_attr_e('Please enter a title for this reward item', 'shipflex') ?>">
					</div>

					<table class="table-shipflex-form">
						<thead>
							<tr>
								<td colspan="2"><?php esc_html_e('General Settings', 'shipflex') ?></td>
							</tr>
						</thead>
					</table>
					

					<footer class="form-footer">
						<button class="button button-primary button-large" :class="{'in-progress': saving}" @click.prevent="save_reward()">
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
							<h2 v-if="current_modal == 'cart-product-option-advanced'">🚀 Unlock Advanced Cart Calculations</h2>
							<span class="btn-modal-close dashicons dashicons-no-alt" @click.prevent="current_modal = null"></span>
						</header>

						<div class="modal-body">
							<template v-if="current_modal == 'cart-product-option-advanced'">
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
