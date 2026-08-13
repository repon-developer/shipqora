<?php

namespace ShipQora\Feature;

use ShipQora\Utils;
use ShipQora\Feature;
use ShipQora\Form_Control;
use ShipQora\Condition\Main;
use ShipQora\Settings_Fields;
use ShipQora\Component_Methods;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Shipping Cost Adjustment class
 */
final class Shipping_Cost_Adjustment extends Feature {

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
	protected $feature_id = 'shipping-cost-adjustment';

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
			'feature_priority' => 10000,
			'base_model' => 'shipping_cost_adjustment',
			'name' => esc_html__('Shipping Cost Adjustment', 'shipqora'),
			'section_title' => esc_html__('Shipping Cost Adjustment', 'shipqora'),
			'description' => esc_html__('Increase, decrease, or override shipping costs based on your configured rules.', 'shipqora'),
		);
	}

	/**
	 * Modify shipping rate object
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @return void
	 */
	public function modify_shipping_rate($shipping_rate) {
		$layers = $this->get_shipping_rate_data($shipping_rate);
		if (count($layers) == 0) {
			return;
		}

		$layers = $this->order_priority($layers);
		$applicable_layer = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			$this->get_hook('applicable-layer'),
			end($layers),
			$this
		);

		if (!$applicable_layer || !array_key_exists('calculated_shipping_cost', $applicable_layer)) {
			return;
		}

		if (!empty($applicable_layer['shipping_method_title'])) {
			$shipping_rate->set_label($applicable_layer['shipping_method_title']);
		}

		$shipping_cost = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			$this->get_hook('shipping-cost'),
			$applicable_layer['calculated_shipping_cost'],
			$layers,
			$this
		);

		$shipping_rate->set_cost($shipping_cost);
	}

	/**
	 * Manage shipping rate object
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @return void
	 */
	public function set_shipping_rate_data($shipping_rate) {
		$layers = $this->get_feature_layers($this->lite_layer);
		if (count($layers) == 0) {
			return;
		}

		$current_shipping_cost = $shipping_rate->get_cost();
		array_walk($layers, function (&$current_layer) use ($current_shipping_cost, $shipping_rate) {
			$current_layer = wp_parse_args($current_layer, array(
				'type' => '',
				'amount' => '',
				'min_cost' => '',
				'max_cost' => '',
				'condition_groups' => array(),
			));

			if (!isset($current_layer['id'])) {
				$current_layer['id'] = md5(wp_json_encode($current_layer));
			}

			if (empty($current_layer['type'])) {
				return;
			}

			$amount = trim($current_layer['amount']);
			if (strlen($amount) == 0 && 'free_shipping' != $current_layer['type']) {
				return;
			}

			$matched = Main::get_instance()->is_matched_conditions($current_layer['condition_groups'], $this);
			if (false === $matched) {
				return;
			}

			$amount = floatval($current_layer['amount']);
			if ('free_shipping' == $current_layer['type']) {
				$amount = 0.00;
			}

			if ('increase_percentage' == $current_layer['type']) {
				$amount = $current_shipping_cost + ($current_shipping_cost * $amount / 100);
			}

			if ('decrease_percentage' == $current_layer['type']) {
				$amount = $current_shipping_cost - ($current_shipping_cost * $amount / 100);
			}

			if ('increase_amount' == $current_layer['type']) {
				$amount = $current_shipping_cost + $amount;
			}

			if ('decrease_amount' == $current_layer['type']) {
				$amount = $current_shipping_cost - $amount;
			}

			if ('free_shipping' !== $current_layer['type']) {
				$min_cost = trim($current_layer['min_cost']);
				if (strlen($min_cost) > 0) {
					$amount = max(floatval($min_cost), $amount);
				}

				$max_cost = trim($current_layer['max_cost']);
				if (strlen($max_cost) > 0) {
					$amount = min(floatval($max_cost), $amount);
				}
			}

			if ($amount < 0) {
				$amount = 0.00;
			}

			$current_layer['calculated_shipping_cost'] = $amount;

			$this->add_shipping_rate_data($shipping_rate, $current_layer);
		});
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id()); ?>
		<?php $this->output_heading_row(esc_html__('Shipping Cost Adjustment Tier #{{layerNo}}', 'shipqora'), array($this->get_id())) ?>
		<template v-if="!collapse">
			<?php $settings_fields->output_fields('layer') ?>
		</template>
	<?php
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('lite_layer', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => $this->get_model_key('lite_layer'),
			'callback' => array($this, 'lite_layer_setting_field'),
		), $this->get_id());

		$settings_fields->add_setting('new_layer_notice', array(
			'priority' => 100000,
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array(General::class, 'notice_setting_field'),
			'notice_content' => array(
				'title' => '⚡ Need Multiple Adjustment Tiers?',
				'utm_source' => 'add+shipping+cost+adjustment+layer',
				'description' => 'Upgrade to <strong>ShipQora Pro</strong> to unlock matrix pricing, weight-based tiers, and conditional rate overrides.',
			)
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function lite_layer_setting_field() { ?>
		<tbody>
			<template
				:draggable="false"
				is="vue:feature-shipping-cost-adjustment"
				:feature-data="<?php echo esc_attr($this->get_model_key('lite_layer')) ?>"
				@update="(value) => <?php echo esc_attr($this->get_model_key('lite_layer')) ?> = value"
				<?php $this->output_component_attrs('shipping-cost-adjustment', array(
					':hide-heading' => 'true',
					':hide-actions' => array('delete')
				)) ?>>
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
		$settings_fields->add_setting('shipping_cost_adjustment', array(
			'priority' => 10,
			'label' => esc_html__('Adjustment Method & Value', 'shipqora'),
			'callback' => array($this, 'shipping_cost_adjustment_setting_field'),
			'label_note' => esc_html__('Select how to modify the shipping rate (increase, decrease, or set a fixed price) and enter the value to apply.', 'shipqora'),
			'option_note' => esc_html__('Enter a numerical value (e.g., 10 for 10% or $10.00 depending on the selected method).', 'shipqora'),
			'related_models' => array(
				'amount' => '',
				'type' => 'increase_percentage',
			)
		), 'layer');

		$settings_fields->add_setting('shipping_cost_limit', array(
			'priority' => 20,
			'label' => esc_html__('Cost Limits', 'shipqora'),
			'conditions' => array('type != "free_shipping"'),
			'callback' => array($this, 'shipping_cost_limit_setting_field'),
			'label_note' => esc_html__('Set the minimum and maximum allowed shipping cost after the adjustment is applied.', 'shipqora'),
			'option_note' => esc_html__('Leave blank for no limit.', 'shipqora'),
			'related_models' => array(
				'min_cost' => '',
				'max_cost' => '',
			)
		), 'layer');

		$settings_fields->add_setting('priority', array(
			'priority' => 30,
			'default_value' => '',
			'placeholder' => '10',
			'model_key' => 'priority',
			'type' => Form_Control::NUMBER,
			'label' => esc_html__('Global Priority', 'shipqora'),
			'attributes' => array('min' => '0', 'step' => '1'),
			'label_note' => esc_html__('Determines which rule wins when rules target the same shipping method. Highest priority number applies; ties go to the latest rule.', 'shipqora'),
			'option_note' => esc_html__('Defines the execution priority when multiple rules share the same shipping method selected in "Apply to Shipping Methods". If multiple rules match, only the rule with the highest priority number will be applied. If priorities are equal, the latest created rule (highest Rule ID) takes precedence.', 'shipqora'),
		), 'layer');

		$settings_fields->add_setting('overwrite_shipping_method_title', array(
			'priority' => 40,
			'type' => Form_Control::TEXTBOX,
			'model_key' => 'shipping_method_title',
			'label' => esc_html__('Overwrite Shipping Method Title', 'shipqora'),
			'label_note' => esc_html__('Enter a custom title to replace the original shipping method name on the cart and checkout pages.', 'shipqora'),
			'option_note' => esc_html__('Leave blank to keep the original shipping method name.', 'shipqora'),
		), 'layer');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'layer');
	}

	/**
	 * Output adjust cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_adjustment_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="type">
				<option value="free_shipping"><?php esc_html_e('Free Shipping', 'shipqora') ?></option>
				<option value="fixed_amount"><?php esc_html_e('Set Fixed Cost', 'shipqora') ?></option>
				<option value="-" disabled>-------------------------</option>
				<option value="increase_amount"><?php esc_html_e('Increase by Amount', 'shipqora') ?></option>
				<option value="decrease_amount"><?php esc_html_e('Decrease by Amount', 'shipqora') ?></option>
				<option value="increase_percentage"><?php esc_html_e('Increase by Percentage', 'shipqora') ?></option>
				<option value="decrease_percentage"><?php esc_html_e('Decrease by Percentage', 'shipqora') ?></option>
			</select>

			<template v-if="'free_shipping' !== type">
				<input type="number" v-model="amount" min="0" placeholder="<?php esc_html_e('Amount', 'shipqora') ?>">
				<span v-if="'increase_percentage' == type || 'decrease_percentage' == type">%</span>
			</template>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Setting field for min/max shipping cost
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_cost_limit_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<input type="number" v-model="min_cost" placeholder="<?php esc_html_e('Min', 'shipqora') ?>">
			<input type="number" v-model="max_cost" placeholder="<?php esc_html_e('Max', 'shipqora') ?>">
		</div>
<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Shipping_Cost_Adjustment::class);
