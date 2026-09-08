<?php

namespace ShipQora\Feature;

use ShipQora\Utils;
use ShipQora\Feature;
use ShipQora\Form_Control;
use ShipQora\Condition\Main;
use ShipQora\Settings_Fields;
use ShipQora\Component_Methods;
use ShipQora\Global_Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Additional Shipping Charge class
 */
final class Additional_Shipping_Charge extends Feature {

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
	protected $feature_id = 'additional-shipping-charge';

	/**
	 * Get feature labels
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_labels() {
		return array(
			'name' => esc_html__('Additional Shipping Charge', 'shipqora'),
			'section_title' => esc_html__('Additional Shipping Charge Settings', 'shipqora'),
			'description' => esc_html__('Add an additional shipping charge when the conditions are met, with the option to apply it to selected shipping methods.', 'shipqora'),
		);
	}

	/**
	 * Configuration of this feature
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_configuration_settings() {
		return array(
			'priority' => 70,
			'standalone' => true,
			'base_model' => 'additional_shipping_charge',
		);
	}

	/**
	 * Get applicable charge
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_applicable_charge() {
		if (count($this->line_items) == 0) {
			return false;
		}

		$line_items = array_filter($this->line_items, function ($charge_item) {
			if (!isset($charge_item['charge_amount']) || !($charge_item['charge_amount'] > 0)) {
				return false;
			}

			return isset($charge_item['condition_groups']) && Main::get_instance()->is_matched_conditions($charge_item['condition_groups']);
		});

		if (count($line_items) == 0) {
			return false;
		}

		$line_items = $this->order_priority($line_items);

		$line_items = array_map(function ($item) {
			$calculated_charge = $item['charge_amount'];

			if ($item['charge_type'] === 'percentage') {
				$calculated_charge = 0;
				if (WC()->cart && method_exists(WC()->cart, 'get_shipping_total')) {
					$shipping_cost =  WC()->cart->get_shipping_total();
					if ($shipping_cost > 0) {
						$calculated_charge = ($shipping_cost / 100) * $item['charge_amount'];
					}
				}
			}

			$item['calculated_charge'] = $calculated_charge;
			return $item;
		}, $line_items);

		$charge_item = end($line_items);

		if ($charge_item['calculated_charge'] <= 0) {
			return false;
		}

		if (empty($charge_item['order_summary_label'])) {
			$charge_item['order_summary_label'] = esc_html__('Post Office Surcharge', 'shipqora');
		}

		return $charge_item;
	}

	/**
	 * Manage feature data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function manage_feature($shipqora_rule) {
		$primary_charge = $shipqora_rule->get_feature_value($this->get_model_key('primary_charge'));
		$primary_charge['rule_id'] = $shipqora_rule->get_id();
		$this->add_line_item($primary_charge);

		do_action(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			$this->get_hook('manage-feature'),
			$shipqora_rule,
			$this
		);
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id()); ?>
		<?php $this->output_heading_row(esc_html__('Additional Shipping Charge #{{layerNo}}', 'shipqora'), array($this->get_id())) ?>
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
		$settings_fields->add_setting('primary_additional_charge', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => $this->get_model_key('primary_charge'),
			'callback' => array($this, 'additional_charge_setting_field_layer'),
		), $this->get_id());

		$settings_fields->add_setting('additional_item_notice', array(
			'priority' => 100000,
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array(Global_Settings_Fields::class, 'notice_setting_field'),
			'notice_content' => array(
				'title' => '⚡ Need Multiple Configuration of Shipping Charges?',
				'utm_source' => 'add+shipping+charge+layer',
				'description' => 'Need more flexibility? Free users can create additional rules, or you can upgrade to <strong>ShipQora Pro</strong> to add multiple shipping charge configurations directly inside a single rule.',
			)
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function additional_charge_setting_field_layer(Form_Control $form_control) { ?>
		<tbody>
			<template
				:draggable="false"
				is="vue:feature-additional-shipping-charge"
				:feature-data="<?php echo esc_attr($form_control->get_model_key()) ?>"
				@update="(value) => <?php echo esc_attr($form_control->get_model_key()) ?> = value"
				<?php $this->output_component_attrs('additional-shipping-charge', array(':hide-heading' => 'true', ':hide-actions' => array('delete'))) ?>>
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
			'priority' => 10,
			'default_value' => '',
			'placeholder' => '10',
			'model_key' => 'priority',
			'type' => Form_Control::NUMBER,
			'label' => esc_html__('Global Priority', 'shipqora'),
			'attributes' => array('min' => '0', 'step' => '1'),
			'label_note' => esc_html__('Determines which rule wins when rules target the same shipping method. Highest priority number applies; ties go to the latest rule.', 'shipqora'),
			'option_note' => esc_html__('Defines the execution priority when multiple rules share the same shipping method selected in "Apply to Shipping Methods". If multiple rules match, only the rule with the highest priority number will be applied. If priorities are equal, the latest created rule (highest Rule ID) takes precedence.', 'shipqora'),
		), 'layer');

		$settings_fields->add_setting('additional_charge', array(
			'priority' => 20,
			'label' => esc_html__('Additional Charge', 'shipqora'),
			'callback' => array($this, 'additional_charge_setting_field'),
			'label_note' => esc_html__('Configure the additional fee and select how it should be calculated.', 'shipqora'),
			'option_note' => esc_html__('Select a fixed amount or a percentage of the shipping cost, then enter the value. (Percentage calculation will not work for "Free Shipping").', 'shipqora'),
			'related_models' => array(
				'charge_amount' => '',
				'charge_type' => 'fixed_amount',
			)
		), 'layer');

		$settings_fields->add_setting('order_summary_label', array(
			'priority' => 30,
			'type' => Form_Control::TEXTBOX,
			'model_key' => 'order_summary_label',
			'label' => esc_html__('Charge Label', 'shipqora'),
			'placeholder' => esc_html__('e.g., Post Office Surcharge', 'shipqora'),
			'label_note' => esc_html__('Set a custom name for this additional fee to display to customers at checkout.', 'shipqora'),
			'option_note' => esc_html__('Enter a custom label to display alongside the additional fee in the order summary table at checkout. If left empty, the default charge name will be used.', 'shipqora'),
		), 'layer');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(Global_Settings_Fields::class, 'condition_group_setting_field'),
		), 'layer');
	}

	/**
	 * Output adjust cost setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function additional_charge_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<select v-model="charge_type">
				<option value="fixed_amount"><?php esc_html_e('Fixed Amount', 'shipqora') ?></option>
				<option value="percentage"><?php esc_html_e('Percentage of Shipping Cost', 'shipqora') ?></option>
			</select>

			<input type="number" v-model="charge_amount" min="0" placeholder="0.00">
			<span v-if="'percentage' == charge_type">%</span>
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

Feature::add_feature(Additional_Shipping_Charge::class);
