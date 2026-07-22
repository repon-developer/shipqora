<?php

namespace ShipFlex\Feature;

use ShipFlex\Utils;
use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Condition\Main;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

final class Product_Based_Shipping extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'product-based-shipping';

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
			'base_model' => 'product_based_shipping',
			'name' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
			'section_title' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
			'description' => esc_html__('Apply product-specific shipping costs to the selected shipping methods when the conditions are met.', 'shipflex'),
		);
	}

	/**
	 * Manage shipping rate object
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @return WC_Shipping_Rate
	 */
	public function modify_shipping_rate($shipping_rate) {
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
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id()); ?>
		<tr class="row-group-heading">
			<td colspan="2">
				<div class="heading-line">
					<?php esc_html_e('Layer', 'shipflex') ?> #{{tier_no}}
					<?php $this->get_tier_header_action(); ?>
				</div>
			</td>
		</tr>

		<template v-if="!collapse || tier_no == 1">
			<?php $settings_fields->output_fields('product-layer') ?>
		</template>
	<?php
	}

	/**
	 * Output settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_rule_editor(Settings_Fields $settings_fields) { ?>
		<table class="table-shipflex-form">
			<thead>
				<tr>
					<td colspan="2">
						<?php echo esc_html($this->get_configuration_value('section_title')) ?>
					</td>
				</tr>
			</thead>

			<?php $settings_fields->output_fields($this->get_id()); ?>
		</table>
	<?php
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

		$settings_fields->add_setting('add_new_layer', array(
			'priority' => 10000,
			'callback' => array($this, 'add_new_layer_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function layer_items_setting_field() { ?>
		<tbody v-for="(layer, layer_index) in <?php echo esc_attr($this->get_model_key('layers')) ?>" :key="layer?.id">
			<template
				:feature-data="layer"
				:tier-index="layer_index"
				is="vue:feature-product-based-shipping"
				@update="(value) => <?php echo esc_attr($this->get_model_key('layers')) ?>[layer_index] = value">
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
		$settings_fields->add_setting('product_source', array(
			'priority' => 5,
			'default_value' => (object) array(),
			'model_key' => 'product_source',
			'label' => esc_html__('Product Source', 'shipflex'),
			'callback' => array($this, 'product_source_setting_field'),
			'label_note' => esc_html__('Choose the product data to match, then select one or more items for this layer.', 'shipflex'),
			'option_note' => esc_html__("This layer will apply only when the customer's cart contains the selected items.", 'shipflex'),
		), 'product-layer');

		$settings_fields->add_setting('exclude_products', array(
			'priority' => 5.05,
			'conditions' => array('tierNo == 1'),
			'row_attributes' => array('class' => 'pro-notice-row'),
			'callback' => array($this, 'exclude_products_setting_field'),
		), 'product-layer');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
			'extra_settings' => array(
				'add_group_method' => 'add_condition_group()',
				'delete_group_method' => 'delete_condition_group(index)'
			)
		), 'product-layer');
	}

	/**
	 * Output setting field of product source
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function product_source_setting_field(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<div class="field-row">
			<cart-option
				:hide-operator="true"
				based-on="taxonomy:product_cat"
				@on-update="(value) => product_source = value"
				option-label="<?php esc_html_e('{{option_label}}', 'shipflex') ?>">
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
			<div class="shipflex-pro-notice">
				<h3>🚀 Want to Exclude Specific Products?</h3>
				<div class="description">Upgrade to the <strong>Pro version</strong> to exclude selected products from the <strong>"Product Source"</strong> and create more precise shipping cost with greater control over product eligibility.</div>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Output add new layer button
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_new_layer_setting_field(Form_Control $form_control) {
		$form_control->output_row(); ?>
		<td colspan="2">
			<a style="--inputHeight: 46px;" @click.prevent="add_collection('<?php echo esc_attr($this->get_model_key('layers')) ?>')" class="button button-primary button-full-row" href="#">
				<?php esc_html_e('Add Layer', 'codiepress-cart-rewards-pro'); ?>
			</a>
		</td>
<?php
		$form_control->output_row('close');
	}
}

Feature::add_feature(Product_Based_Shipping::class);
