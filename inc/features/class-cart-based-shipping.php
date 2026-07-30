<?php

namespace ShipFlex\Feature;

use ShipFlex\Cart_Total;
use ShipFlex\Component\Cart_Option;
use ShipFlex\Utils;
use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Shipping_Cost;
use ShipFlex\Condition\Main;
use ShipFlex\Settings_Fields;
use ShipFlex\Component_Methods;
use ShipFlex\Component\Shipping_Cost_Range_Tier;

if (!defined('ABSPATH')) {
	exit;
}

class Cart_Based_Shipping extends Feature {

	/**
	 * Provides common helper methods of component
	 *
	 * @since 1.0.0
	 */
	use Component_Methods;

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'cart-based-shipping';

	/**
	 * Constructor.
	 */
	public function __construct($data = null) {
		if (!is_array($data)) {
			return;
		}

		parent::__construct($data);
	}

	/**
	 * Configuration of this feature
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_configuration() {
		return array(
			'priority' => 40,
			'base_model' => 'cart_based_shipping',
			'name' => esc_html__('Cart-Based Shipping Cost', 'shipflex'),
			'section_title' => esc_html__('Cart-Based Shipping Cost', 'shipflex'),
			'description' => esc_html__('Calculate shipping costs dynamically based on cart total, item count, weight, or volume.', 'shipflex'),
		);
	}

	/**
	 * Set shipping cost
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 */
	public function set_shipping_cost($shipping_rate) {
		$tier_items = $shipping_rate->{$this->get_id()};
		if (!is_array($tier_items) || count($tier_items) == 0) {
			return;
		}

		$tier_items = $this->order_priority($tier_items);

		$best_tier = apply_filters($this->get_hook('applicable-layer'), end($tier_items), $this);
		if (isset($best_tier['calculated_shipping_cost'])) {
			$shipping_rate->set_cost($best_tier['calculated_shipping_cost']);
		}
	}

	/**
	 * Calculate shipping cost of tier item
	 * 
	 * @since 1.0.0
	 * @param array $tier_item
	 */
	public function calculate_tier_item_shipping_cost($tier_item, $cart_total) {
		$tier_item = wp_parse_args($tier_item, array(
			'calculate_basis' => '',
			'calculation_type' => '',
			'calculation_value' => '',
			'target_products' => array(),
			'condition_groups' => array(),
			'shipping_cost_range_layers' => array(),
		));

		$calculate_metrics = array('subtotal', 'quantity', 'weight', 'volume');

		$calculate_basis = isset($tier_item['calculate_basis']) ? $tier_item['calculate_basis'] : null;
		if (!in_array($calculate_basis, array('fixed_amount', ...$calculate_metrics))) {
			return;
		}

		$calculation_value = isset($tier_item['calculation_value']) ? trim($tier_item['calculation_value']) : '';
		$calculation_value = apply_filters($this->get_hook('layer', 'calculation-value'), $calculation_value, $tier_item, $this);
		if (strlen($calculation_value) == 0 && 'based_on_ranges' !== $tier_item['calculation_type']) {
			return;
		}

		try {
			$calculation_value = floatval($calculation_value);
			if ('fixed_amount' == $calculate_basis) {
				throw new Shipping_Cost($calculation_value);
			}

			if (in_array($calculate_basis, $calculate_metrics)) {
				$calculation_type = isset($tier_item['calculation_type']) ? $tier_item['calculation_type'] : null;

				$metrics_total = $cart_total->get_total($calculate_basis);

				if ($metrics_total > 0) {
					if ('per_unit_or_percentage' == $calculation_type && $calculation_value > 0) {
						$shipping_cost = $metrics_total * $calculation_value;
						if ('subtotal' == $calculate_basis) {
							$shipping_cost = $shipping_cost / 100;
						}

						throw new Shipping_Cost($shipping_cost);
					}

					if (
						'based_on_ranges' == $calculation_type &&
						isset($tier_item['shipping_cost_range_layers']) &&
						is_array($tier_item['shipping_cost_range_layers']) &&
						count($tier_item['shipping_cost_range_layers']) > 0
					) {
						$shipping_cost_range_layers = array_map(fn($range_layer) => new Shipping_Cost_Range_Tier($range_layer), $tier_item['shipping_cost_range_layers']);
						$shipping_cost_range_layers = array_filter($shipping_cost_range_layers, function ($item) {
							if (!$item->has_validate_ranges()) {
								return false;
							}

							return $item->is_condition_matched();
						});

						if (count($shipping_cost_range_layers) == 0) {
							throw new Shipping_Cost(-1);
						}

						usort($shipping_cost_range_layers, fn($a, $b) => $a->get_priority() <=> $b->get_priority());
						$shipping_cost_range = end($shipping_cost_range_layers);
						$shipping_cost = $shipping_cost_range->calculate_shipping_cost($metrics_total, $tier_item['calculate_basis']);
						if (false !== $shipping_cost) {
							throw new Shipping_Cost($shipping_cost);
						}
					}
				}
			}

			throw new Shipping_Cost(-1);
		} catch (Shipping_Cost $e) {
			$tier_item['calculated_shipping_cost'] = $e->getAmount();
		}

		return $tier_item;
	}

	/**
	 * Add shipping rate data
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @param int $rule_id
	 */
	public function add_shipping_rate_data($shipping_rate, $rule_id) {
		$tier_items = apply_filters($this->get_hook('layers'), array($this->lite_tier));
		if (count($tier_items) == 0) {
			return;
		}

		$existsed_tiers = $shipping_rate->{$this->get_id()};
		if (!is_array($existsed_tiers)) {
			$existsed_tiers = array();
		}

		$cart_total = new Cart_Total();

		array_walk($tier_items, function (&$tier_item) use (&$existsed_tiers, $rule_id, $cart_total) {
			if (isset($group['condition_groups'])) {
				$is_matched = Main::get_instance()->is_matched_conditions($group['condition_groups']);
				if (!$is_matched) {
					return;
				}
			}

			if (!isset($tier_item['target_products'])) {
				$tier_item['target_products'] = array();
			}

			$cart_option = new Cart_Option($tier_item['target_products']);
			$cart_total->set_cart_items_keys($cart_option->get_cart_items_keys());


			$tier_item = $this->calculate_tier_item_shipping_cost($tier_item, $cart_total);
			$tier_item['rule_id'] = $rule_id;

			if (!isset($tier_item['id'])) {
				$tier_item['id'] = md5(wp_json_encode($tier_item));
			}

			if ($tier_item['calculated_shipping_cost'] >= 0) {
				$tier_item_key = $rule_id . '-' . $tier_item['id'];
				$existsed_tiers[$tier_item_key] = apply_filters($this->get_hook('layer'), $tier_item, $this);
			}
		});

		$shipping_rate->{$this->get_id()} = $existsed_tiers;
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('lite_tier_settings', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => $this->get_model_key('lite_tier'),
			'callback' => array($this, 'lite_tier_settings_field'),
		), $this->get_id());

		$settings_fields->add_setting('add_new_tier_setting_field', array(
			'priority' => 10000,
			'row_attributes' => array('class' => 'shipflex-notice-row'),
			'callback' => array($this, 'add_new_tier_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output lite tier settings field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function lite_tier_settings_field() { ?>
		<tbody>
			<template
				:draggable="false"
				is="vue:feature-cart-based-shipping"
				:feature-data="<?php echo esc_attr($this->get_model_key('lite_tier')) ?>"
				@update="(value) => <?php echo esc_attr($this->get_model_key('lite_tier')) ?> = value"
				<?php $this->output_component_attrs(
					array(':hide-heading' => 'true', ':hide-actions' => '["delete"]'),
					array('type' => 'feature', 'model' => 'lite_tier', 'component' => $this->get_id())
				) ?>>
			</template>
		</tbody>
	<?php
	}

	/**
	 * Output add new layer button
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_new_tier_setting_field(Form_Control $form_control) {
		$line_button_data = array('utm_source' => 'cart+based+shipping+cost');
		$form_control->output_row(); ?>
		<td colspan="2">
			<div class="shipflex-notice-box">
				<h3>💡 Unlock Unlimited Cart Tiers</h3>
				<div class="description">Upgrade to the Pro version to create unlimited shipping tiers and build complex, tiered shipping rules based on cart conditions.</div>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id()); ?>
		<?php $this->output_heading_row(esc_html__('Tier #{{tierNo}}', 'shipflex'), array($this->get_id())) ?>
		<template v-if="!collapse">
			<?php $settings_fields->output_fields('cart-tier') ?>
		</template>
	<?php
	}

	/**
	 * Add component settings field 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_component_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('target_products', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => 'target_products',
			'label' => esc_html__('Target Cart Items', 'shipflex'),
			'callback' => array($this, 'target_products_setting_field'),
			'label_note' => esc_html__('Select which cart items this rule applies to. You can target all items or filter by specific categories, tags, shipping classes, or taxonomies.', 'shipflex'),
			'option_note' => esc_html__('Shipping cost calculations will apply to the combined total (subtotal, quantity, weight, or volume) of all matching items found in the cart.', 'shipflex'),
		), 'cart-tier');

		$settings_fields->add_setting('exclude_products', array(
			'priority' => 10.10,
			'conditions' => array('tierNo == 1'),
			'row_attributes' => array('class' => 'shipflex-notice-row'),
			'callback' => array($this, 'exclude_products_setting_field'),
		), 'cart-tier');

		$settings_fields->add_setting('priority', array(
			'priority' => 30,
			'default_value' => '',
			'placeholder' => '10',
			'model_key' => 'priority',
			'type' => Form_Control::NUMBER,
			'label' => esc_html__('Global Priority', 'shipflex'),
			'attributes' => array('min' => '0', 'step' => '1'),
			'label_note' => esc_html__('Determines which rule wins when rules target the same shipping method. Highest priority number applies; ties go to the latest rule.', 'shipflex'),
			'option_note' => esc_html__('Defines the execution priority when multiple rules share the same shipping method selected in "Apply to Shipping Methods". If multiple rules match, only the rule with the highest priority number will be applied. If priorities are equal, the latest created rule (highest Rule ID) takes precedence.', 'shipflex'),
		), 'cart-tier');

		$settings_fields->add_setting('shipping_cost_calculation', array(
			'priority' => 40,
			'label' => esc_html__('Calculate Cost By', 'shipflex'),
			'callback' => array($this, 'shipping_cost_setting_field'),
			'label_note' => esc_html__('Choose how the shipping cost is determined based on cart subtotal, item quantity, total weight, or total volume.', 'shipflex'),
			'row_attributes' => array(
				':data-highlight-section' => "'shipping-cost-calculation-' + id"
			),
			'related_models' => array(
				'calculation_value' => '',
				'calculate_basis' => 'fixed_amount',
				'calculation_type' => 'per_unit_or_percentage',
			)
		), 'cart-tier');

		$settings_fields->add_setting('shipping_cost_range_layers', array(
			'priority' => 50,
			'default_value' => array((object)array()),
			'model_key' => 'shipping_cost_range_layers',
			'label' => esc_html__('Shipping Cost Range Settings', 'shipflex'),
			'callback' => array($this, 'shipping_cost_range_layers_setting_field'),
			'label_note' => esc_html__('Configure volume, weight, subtotal, or quantity thresholds and fee calculations for each tier range. Use priority settings and condition groups to control which rates apply.', 'shipflex'),
			'conditions' => array('calculate_basis !== "fixed_amount" && calculation_type == "based_on_ranges"'),
		), 'cart-tier');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'cart-tier');
	}

	/**
	 * Output setting field of product source
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function target_products_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<cart-option
				based-on=""
				:hide-operator="true"
				cart-option-type="cart-items"
				:cart-option-data="<?php echo esc_attr($form_control->get_model_key()) ?>"
				@on-update="(value) => <?php echo esc_attr($form_control->get_model_key()) ?> = value"
				option-label="<?php esc_html_e('All cart items of selected {{option_label_lower}}', 'shipflex') ?>">
				<template v-slot:based-on-first-option>
					<option value=""><?php esc_html_e('All cart items', 'shipflex') ?></option>
				</template>
			</cart-option>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output adjust cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function exclude_products_setting_field(Form_Control $form_control) {
		$line_button_data = array('utm_source' => 'exclude+products');
		$form_control->output_row(); ?>
		<td colspan="2">
			<div class="shipflex-notice-box">
				<h3>🚀 Want to Exclude Specific Products?</h3>
				<div class="description">Upgrade to the <strong>Pro version</strong> to exclude selected products from the <strong>"Target Cart Items"</strong> and create more precise shipping cost with greater control over product eligibility.</div>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Output shipping cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="calculate_basis">
				<option value="fixed_amount"><?php esc_html_e('Fixed Amount', 'shipflex') ?></option>
				<option v-for="(metric, value) in calculation_metrics" :value="value" :key="value">{{metric.long_title}}</option>
			</select>

			<select v-model="calculation_type" v-if="calculate_basis !== 'fixed_amount'">
				<option value="per_unit_or_percentage">
					<template v-if="'subtotal' == calculate_basis"><?php esc_html_e('Percentage', 'shipflex') ?></template>
					<template v-if="'subtotal' != calculate_basis">{{calculation_type_label}}</template>
				</option>
				<option value="based_on_ranges"><?php esc_html_e('Shipping Cost Ranges', 'shipflex') ?></option>
			</select>

			<template v-if="show_calculation_value">
				<input v-model="calculation_value" type="number" min="0" placeholder="0.00">
				<span v-if="calculate_basis == 'subtotal'">%</span>
			</template>
		</div>

		<div class="field-note" v-if="calculate_basis == 'fixed_amount'">
			<?php esc_html_e('Applies a single fixed shipping cost.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'subtotal'">
			<?php esc_html_e('Choose "Percentage" to charge a % of the item value, or "Shipping Cost Ranges" for subtotal ranges.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'quantity'">
			<?php esc_html_e('Charge a rate per item unit (e.g. $2 per item), or choose "Shipping Cost Ranges" for quantity brackets.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'weight'">
			<?php esc_html_e('Charge a rate per weight unit (e.g. $1.50 per kg), or choose "Shipping Cost Ranges" for weight brackets.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'volume'">
			<?php esc_html_e('Charge a rate per volume unit (e.g. $0.50 per cm³), or choose "Shipping Cost Ranges" for volume brackets.', 'shipflex') ?>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output setting field of shipping cost range
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_range_layers_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>

		<div
			@end="layers_order_change"
			class="sortable-items-container"
			data-group="shipping-cost-range-layers"
			v-if="shipping_cost_range_layers?.length"
			data-model-key="shipping_cost_range_layers"
			v-sortable="{options: {handle: '.button-drag.button-drag-shipping-cost-range'}}">
			<shipping-cost-range
				:tier-no="index + 1"
				:hide-heading="false"
				:key="shipping_rage_data?.id"
				:range-data="shipping_rage_data"
				:calculate-basis="calculate_basis"
				@delete="delete_shipping_cost_range(index)"
				v-for="(shipping_rage_data, index) in shipping_cost_range_layers"
				@update="(range_data) => shipping_cost_range_layers[index] = range_data"
				@duplicate="(range_data) => duplicate_shipping_cost_range(range_data, index+1)">
			</shipping-cost-range>
		</div>

		<a class="button button-full-width" href="#" @click.prevent="add_shipping_cost_range()"><?php esc_html_e('+ Add New Shipping Cost Range', 'shipflex') ?></a>
<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Cart_Based_Shipping::class);
