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

final class Product_Based_Shipping extends Cart_Based_Shipping {

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
			'priority' => 50,
			'base_model' => 'product_based_shipping',
			'name' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
			'section_title' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
			'description' => esc_html__('Apply product-specific shipping costs to the selected shipping methods when the conditions are met.', 'shipflex'),
		);
	}

	/**
	 * Add shipping rate data
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @param int $rule_id
	 */
	public function add_shipping_rate_data($shipping_rate, $rule_id) {
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_wrapper_attributes() {
		printf(
			'@end="(event) => on_order_change(event, \'%s\', 1)"',
			esc_attr($this->get_model_key('layers'))
		);

		echo ' v-sortable="{options: {handle: \'tr.row-group-heading .button-drag\'}, filter: \'>tbody.sortable-item\'}"';

		echo ' :key="' . $this->get_model_key('layers') . '?.length"';
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$editor_settings_fields = Settings_Fields::get_instance('rule-editor');

		$cart_based_shipping_fields = $editor_settings_fields->get_settings_fields('cart-based-shipping');
		unset($cart_based_shipping_fields['add_new_tier']);

		$layer_items = $editor_settings_fields->get_setting('layer_items', 'cart-based-shipping');
		$layer_items = wp_parse_args(array(
			'model_key' => $this->get_configuration_value('base_model'),
			'callback' => array($this, 'layer_items_setting_field')
		), $layer_items);

		$settings_fields->add_setting('layer_items', $layer_items, $this->get_id());

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
		<tbody
			:key="layer?.id"
			class="sortable-item"
			v-for="(layer, layer_index) in <?php echo esc_attr($this->get_model_key('layers')) ?>">
			<template
				:feature-data="layer"
				:tier-no="layer_index + 1"
				is="vue:feature-product-based-shipping"
				:total-tier="<?php echo esc_attr($this->get_model_key('layers')) ?>?.length"
				delete-warning="<?php esc_html_e('Are you sure you want to delete this "Product Rule"?', 'shipflex') ?>"
				@update="(value) => <?php echo esc_attr($this->get_model_key('layers')) ?>[layer_index] = value"
				@delete="delete_collection('<?php echo esc_attr($this->get_model_key('layers')) ?>', layer_index)"
				@duplicate="(value, position) => duplicate_collection('<?php echo esc_attr($this->get_model_key('layers')) ?>', value, position)">
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
	public function add_new_layer_setting_field(Form_Control $form_control) {
		$form_control->output_row(); ?>
		<td class="no-padding" colspan="2">
			<a style="--inputHeight: 46px;font-size: 16px" @click.prevent="add_collection('<?php echo esc_attr($this->get_model_key('layers')) ?>')" class="button button-primary button-full-width" href="#">
				<?php esc_html_e('+ Add Product Tier', 'codiepress-cart-rewards-pro'); ?>
			</a>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Get actions button of component heading
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_component_heading_actions() {
		$actions = Utils::get_component_heading_actions();
		$actions['delete']['content'] = '<a @click.prevent="delete_tier()" class="button button-small" href="#"><span class="dashicons dashicons-trash"></span>' . esc_html__('Delete', 'shipflex') . '</a>';
		return $actions;
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id()); ?>

		<?php $this->output_heading_row(esc_html__('Product Tier #{{tierNo}}', 'shipflex')) ?>
		<template v-if="!collapse">
			<?php $settings_fields->output_fields('product') ?>
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
		$cart_based_component = Settings_Fields::get_instance('cart-based-shipping');

		$product_source = $cart_based_component->get_setting('target_products', 'cart-tier');
		$product_source = wp_parse_args(array(
			'model_key' => 'product_source',
			'label' => esc_html__('Target Products', 'shipflex'),
			'callback' => array($this, 'target_products_setting_field'),
			'label_note' => esc_html__('Select which products this tier applies to. Filter by specific categories, tags, shipping classes, or taxonomies.', 'shipflex'),
			'option_note' => esc_html__('Shipping cost will be calculated individually for each matching product item in the cart, and the total will be the sum of those costs.', 'shipflex'),
		), $product_source);


		$settings_fields->add_setting('product_source', $product_source, 'product');

		$exclude_products = $cart_based_component->get_setting('exclude_products', 'cart-tier');
		$exclude_products['callback'] = array($this, 'exclude_products_setting_field');
		$settings_fields->add_setting('exclude_products', $exclude_products, 'product');

		$priority_setting_field = $cart_based_component->get_setting('priority', 'cart-tier');
		$settings_fields->add_setting('priority', $priority_setting_field, 'product');

		$shipping_cost_calculation = $cart_based_component->get_setting('shipping_cost_calculation', 'cart-tier');
		$settings_fields->add_setting('shipping_cost_calculation', $shipping_cost_calculation, 'product');


		$shipping_cost_ranges_settings = $cart_based_component->get_setting('shipping_cost_ranges_settings', 'cart-tier');
		$settings_fields->add_setting('shipping_cost_ranges_settings', $shipping_cost_ranges_settings, 'product');

		$condition_groups = $cart_based_component->get_setting('condition_groups', 'cart-tier');
		$settings_fields->add_setting('condition_groups', $condition_groups, 'product');
	}

	/**
	 * Output setting field of target products
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
				:cart-option-data="<?php echo esc_attr($form_control->get_model_key()) ?>"
				@on-update="(value) => <?php echo esc_attr($form_control->get_model_key()) ?> = value"
				option-label="<?php esc_html_e('Products in selected {{option_label_lower}}', 'shipflex') ?>">
				<template v-slot:based-on-first-option>
					<option value=""><?php esc_html_e('All products in cart', 'shipflex') ?></option>
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
				<div class="description">Upgrade to the <strong>Pro version</strong> to exclude selected products from the <strong>"Target Products"</strong> and create more precise shipping cost with greater control over product eligibility.</div>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
	}
}

Feature::add_feature(Product_Based_Shipping::class);
