<?php

namespace ShipFlex\Feature;

use ShipFlex\Cart_Total;
use ShipFlex\Utils;
use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Shipping_Cost;
use ShipFlex\Condition\Main;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

final class Cart_Based_Shipping extends Feature {

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
	 * Manage shipping rate object
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @param float $amount
	 */
	public function set_shipping_cost($shipping_rate, $amount) {
		$hook_name = Utils::get_hook_name('feature', $this->get_id(), 'shipping-cost');
		$shipping_cost = apply_filters($hook_name, $current_shipping_cost, array($tier_shipping_costs));
		$shipping_rate->set_cost($amount);
	}



	/**
	 * Manage shipping rate object
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @return WC_Shipping_Rate
	 */
	public function modify_shipping_rate($shipping_rate) {
		$lite_tier = $this->lite_tier;
		if (!is_array($lite_tier) || empty($lite_tier)) {
			return $shipping_rate;
		}

		$calculate_metrics = array('subtotal', 'quantity', 'weight', 'volume');

		$calculate_basis = isset($lite_tier['calculate_basis']) ? $lite_tier['calculate_basis'] : null;
		if (!in_array($calculate_basis, array('fixed_amount', ...$calculate_metrics))) {
			return $shipping_rate;
		}

		$calculation_value = isset($lite_tier['calculation_value']) ? trim($lite_tier['calculation_value']) : '';
		if (strlen($calculation_value) == 0) {
			return $shipping_rate;
		}

		error_log(print_r($lite_tier, true));

		try {
			$calculation_value = floatval($calculation_value);
			if ('fixed_amount' == $calculate_basis) {
				throw new Shipping_Cost($calculation_value);
			}

			if (in_array($calculate_basis, $calculate_metrics)) {
				$calculation_type = isset($lite_tier['calculation_type']) ? $lite_tier['calculation_type'] : null;

				$metrics_total = (new Cart_Total())->get_total($calculate_basis);

				if ($metrics_total > 0) {
					if ('per_unit_or_percentage' == $calculation_type && $calculation_value > 0) {
						$shipping_cost = $metrics_total * $calculation_value;
						if ('subtotal' == $calculate_basis) {
							$shipping_cost = $shipping_cost / 100;
						}

						throw new Shipping_Cost($shipping_cost);
					}

					if (
						'advanced_calculation' == $calculation_type
						&& isset($lite_tier['advanced_calculation_tiers'])
						&& is_array($lite_tier['advanced_calculation_tiers'])
						&& count($lite_tier['advanced_calculation_tiers']) > 0
					) {
						$tiers = array_map(function ($tier_item) {
							$tier_item = wp_parse_args($tier_item, array('priority' => '', 'condition_groups' => array(), 'shipping_cost_ranges' => array()));
							if (strlen($tier_item['priority']) == 0) {
								$tier_item['priority'] = 10;
							}

							if (!is_array($tier_item['condition_groups'])) {
								$tier_item['condition_groups'] = array();
							}

							if (!is_array($tier_item['shipping_cost_ranges'])) {
								$tier_item['shipping_cost_ranges'] = array();
							}

							return $tier_item;
						}, $lite_tier['advanced_calculation_tiers']);

						$tiers = array_filter($tiers, function ($item) {
							if (count($item['shipping_cost_ranges']) == 0) {
								return false;
							}

							return true;
						});

						uasort($tiers, fn($a, $b) => $a['priority'] > $b['priority'] ? -1 : 1);

						error_log(print_r($tiers, true));
					}
				}


				throw new Shipping_Cost($shipping_rate->get_cost());
			}





			throw new Shipping_Cost(5555555);
		} catch (Shipping_Cost $e) {
			$shipping_cost = $this->get_shipping_cost($e->getAmount(), $lite_tier);
			$shipping_rate->set_cost($e->getAmount());
			return $shipping_rate;
		}



		$current_shipping_cost = $shipping_rate->get_cost();
		$tier_items = apply_filters(Utils::get_hook_name('feature', $this->get_id(), 'modify-shipping-rate'), array($this->lite_tier));

		$tier_shipping_costs = array();
		foreach ($tier_items as $tier_item) {
			$tier_item = wp_parse_args($tier_item, array(
				'type' => '',
				'amount' => '',
				'min_cost' => '',
				'max_cost' => '',
				'condition_groups' => array(),
			));

			if (empty($tier_item['type'])) {
				continue;
			}

			$amount = trim($tier_item['amount']);
			if (strlen($amount) == 0 && 'free_shipping' != $tier_item['type']) {
				continue;
			}

			if ('free_shipping' == $tier_item['type']) {
				$tier_shipping_costs[] = 0.00;
				continue;
			}

			$matched = Main::get_instance()->is_matched_conditions($tier_item['condition_groups'], $this);
			if (false === $matched) {
				continue;
			}

			$amount = floatval($tier_item['amount']);
			if ('increase_percentage' == $tier_item['type']) {
				$amount = $current_shipping_cost + ($current_shipping_cost * $amount / 100);
			}

			if ('decrease_percentage' == $tier_item['type']) {
				$amount = $current_shipping_cost - ($current_shipping_cost * $amount / 100);
			}

			if ('increase_amount' == $tier_item['type']) {
				$amount = $current_shipping_cost + $amount;
			}

			if ('decrease_amount' == $tier_item['type']) {
				$amount = $current_shipping_cost - $amount;
			}

			$min_cost = trim($tier_item['min_cost']);
			if (strlen($min_cost) > 0) {
				$amount = max(floatval($min_cost), $amount);
			}

			$max_cost = trim($tier_item['max_cost']);
			if (strlen($max_cost) > 0) {
				$amount = min(floatval($max_cost), $amount);
			}

			if ($amount < 0) {
				$amount = 0.00;
			}

			$tier_shipping_costs[] = $amount;
		}

		if (count($tier_shipping_costs) > 0) {
			$current_shipping_cost = max($tier_shipping_costs);
		}

		$hook_name = Utils::get_hook_name('feature', $this->get_id(), 'shipping-cost');
		$shipping_cost = apply_filters($hook_name, $current_shipping_cost, array($tier_shipping_costs));
		$shipping_rate->set_cost($shipping_cost);

		return $shipping_rate;
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('layer_items', array(
			'priority' => 10,
			'default_value' => array((object) array()),
			'model_key' => $this->get_model_key('layers'),
			'callback' => array($this, 'layer_items_setting_field'),
		), $this->get_id());

		$settings_fields->add_setting('add_new_tier', array(
			'priority' => 10,
			'row_attributes' => array('class' => 'shipflex-notice-row'),
			'callback' => array($this, 'add_new_tier_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function layer_items_setting_field() { ?>
		<template
			:hide-heading="true"
			is="vue:feature-cart-based-shipping"
			:feature-data="<?php echo esc_attr($this->get_model_key('lite_tier')) ?>"
			@update="(value) => <?php echo esc_attr($this->get_model_key('lite_tier')) ?> = value">
		</template>
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
		<tbody>
			<?php $this->output_heading_row(esc_html__('Tier #{{tierNo}}', 'shipflex')) ?>
			<template v-if="!collapse">
				<?php $settings_fields->output_fields('cart-tier') ?>
			</template>
		</tbody>
	<?php
	}

	/**
	 * Add component settings field 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_component_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('priority', array(
			'priority' => 30,
			'default_value' => '',
			'placeholder' => '10',
			'model_key' => 'priority',
			'type' => Form_Control::NUMBER,
			'label' => esc_html__('Priority', 'shipflex'),
			'label_note' => esc_html__('Set the priority for this rule. If multiple blocks match, the block with the highest priority number will apply.', 'shipflex'),
			'option_note' => esc_html__('Higher numbers take precedence over lower numbers. Only the highest-priority rule will be executed (e.g., if Priority 15 and Priority 10 both match, only Priority 15 will be executed).', 'shipflex'),

		), 'cart-tier');

		$settings_fields->add_setting('exclude_products', array(
			'priority' => 30,
			'conditions' => array('tierNo == 1'),
			'row_attributes' => array('class' => 'shipflex-notice-row'),
			'callback' => array($this, 'exclude_products_setting_field'),
		), 'cart-tier');

		$settings_fields->add_setting('shipping_cost_calculation', array(
			'priority' => 40,
			'label' => esc_html__('Calculate Cost By', 'shipflex'),
			'callback' => array($this, 'shipping_cost_setting_field'),
			'label_note' => esc_html__('Choose how the shipping cost is determined based on cart subtotal, item quantity, total weight, or total volume.', 'shipflex'),
			'related_models' => array(
				'calculation_value' => '',
				'calculate_basis' => 'fixed_amount',
				'calculation_type' => 'per_unit_or_percentage',
			)
		), 'cart-tier');

		$settings_fields->add_setting('advanced_calculation', array(
			'priority' => 40.10,
			'default_value' => array((object)array()),
			'model_key' => 'advanced_calculation_tiers',
			'label' => esc_html__('Advanced Calculation Settings', 'shipflex'),
			'callback' => array($this, 'advanced_calculation_setting_field'),
			'label_note' => esc_html__('Configure volume, weight, subtotal, or quantity thresholds and fee calculations for this tier. Use priority settings and condition groups to control which rates apply.', 'shipflex'),
			'conditions' => array('calculate_basis !== "fixed_amount" && calculation_type == "advanced_calculation"'),
		), 'cart-tier');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'cart-tier');
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
				<div class="description">Upgrade to <strong>ShipFlex Pro</strong> to exclude specific products or categories from cart-based shipping rules and gain precise control over product eligibility.</div>
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
				<option value="advanced_calculation"><?php esc_html_e('Advanced Calculation', 'shipflex') ?></option>
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
			<?php esc_html_e('Choose "Percentage" to charge a % of the item value, or "Advanced Calculation" for subtotal ranges.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'quantity'">
			<?php esc_html_e('Charge a rate per item unit (e.g. $2 per item), or choose "Advanced Calculation" for quantity brackets.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'weight'">
			<?php esc_html_e('Charge a rate per weight unit (e.g. $1.50 per kg), or choose "Advanced Calculation" for weight brackets.', 'shipflex') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'volume'">
			<?php esc_html_e('Charge a rate per volume unit (e.g. $0.50 per cm³), or choose "Advanced Calculation" for volume brackets.', 'shipflex') ?>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output setting field for advanced calculation
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function advanced_calculation_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>

		<div
			@end="on_order_change"
			style="margin-bottom: 10px;"
			class="sortable-items-container"
			v-if="advanced_calculation_tiers?.length"
			data-model-key="advanced_calculation_tiers"
			v-sortable="{options: {handle: '.button-drag'}}">
			<shipping-cost-range
				:tier-no="index + 1"
				:key="shipping_rage_data?.id"
				:range-data="shipping_rage_data"
				:calculate-basis="calculate_basis"
				@delete="delete_shipping_cost_range(index)"
				:total-tier="advanced_calculation_tiers?.length"
				v-for="(shipping_rage_data, index) in advanced_calculation_tiers"
				@update="(range_data) => advanced_calculation_tiers[index] = range_data"
				@duplicate="(range_data) => duplicate_shipping_cost_range(range_data, index+1)">
			</shipping-cost-range>
		</div>

		<a class="button button-full-width" href="#" @click.prevent="add_shipping_cost_range()"><?php esc_html_e('+ Add New Shipping Cost Range', 'shipflex') ?></a>
<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Cart_Based_Shipping::class);
