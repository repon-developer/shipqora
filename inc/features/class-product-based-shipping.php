<?php

namespace ShipFlex\Feature;

use ShipFlex\Utils;
use ShipFlex\Feature;
use ShipFlex\Cart_Total;
use ShipFlex\Form_Control;
use ShipFlex\Condition\Main;
use ShipFlex\Settings_Fields;
use ShipFlex\Component_Methods;
use ShipFlex\Component\Cart_Option;

if (!defined('ABSPATH')) {
	exit;
}

final class Product_Based_Shipping extends Cart_Based_Shipping {

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
	protected $feature_id = 'product-based-shipping';

	/**
	 * Hold product groups
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	protected $groups = array();

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
			'calculation_priority' => 20,
			'base_model' => 'product_based_shipping',
			'name' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
			'section_title' => esc_html__('Product-Based Shipping Cost', 'shipflex'),
			'description' => esc_html__('Apply product-specific shipping costs to the selected shipping methods when the conditions are met.', 'shipflex'),
		);
	}

	/**
	 * Set shipping cost
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 */
	public function set_shipping_cost($shipping_rate) {
		$product_items = $shipping_rate->{$this->get_id()};
		if (!is_array($product_items) || count($product_items) == 0) {
			return;
		}

		$product_costs = array_map(function ($product_tiers) {
			$product_item = end($this->order_priority($product_tiers));
			if (array_key_exists('calculated_shipping_cost', $product_item) && $product_item['calculated_shipping_cost'] >= 0) {
				return $product_item['calculated_shipping_cost'];
			}

			return false;
		}, $product_items);

		$shipping_cost = array_sum(array_filter($product_costs));
		$shipping_cost = apply_filters($this->get_hook('shipping-cost'), $shipping_cost, $product_items, $this);

		if ($shipping_cost >= 0) {
			$shipping_rate->set_cost($shipping_cost);
		}
	}

	/**
	 * Add shipping rate data
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @param int $rule_id
	 */
	public function add_shipping_rate_data($shipping_rate, $rule_id) {
		if (!is_array($this->groups) || (is_array($this->groups) && count($this->groups) == 0)) {
			return;
		}

		$product_items = $shipping_rate->{$this->get_id()};
		if (!is_array($product_items)) {
			$product_items = array();
		}

		$cart_total = new Cart_Total();

		array_walk($this->groups, function (&$group) use (&$product_items, $rule_id, $cart_total) {
			if (isset($group['condition_groups'])) {
				$is_matched = Main::get_instance()->is_matched_conditions($group['condition_groups']);
				if (!$is_matched) {
					return;
				}
			}

			$group['rule_id'] = $rule_id;
			if (isset($group['product_source'])) {
				$group['target_products'] = $group['product_source'];
			}

			$cart_items = $cart_total->get_cart_items();

			$cart_option = new Cart_Option($group['target_products']);

			foreach ($cart_items as $cart_item_key => $cart_item) {
				$is_eligible_product = $cart_option->is_eligible_product($cart_item['product_id'], $cart_item['variation_id']);
				if (!$is_eligible_product) {
					continue;
				}

				$cart_total->set_cart_items_keys(array($cart_item_key));
				$product_item = $this->calculate_tier_item_shipping_cost($group, $cart_total);

				if ($product_item['calculated_shipping_cost'] >= 0) {
					$product_slug = $cart_item['product_id'] . '-' . $cart_item['variation_id'];
					$product_items[$product_slug][] = apply_filters($this->get_hook('product'), $product_item, $group, $this);
				}
			}
		});

		$shipping_rate->{$this->get_id()} = $product_items;
	}

	/**
	 * Get wrapper attributes of this feature section
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_wrapper_attributes() {
		return array(
			'data-skip-order' => 1,
			'@end' => 'on_order_change',
			'data-model-key' => $this->get_model_key('groups'),
			'v-sortable' => '{options: {handle: \'tr.row-group-heading .button-drag\', draggable: \'>tbody.sortable-item\'}}',
		);
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('product_groups_settings_field', array(
			'priority' => 10,
			'default_value' => array((object) array()),
			'model_key' => $this->get_model_key('groups'),
			'callback' => array($this, 'product_groups_settings_field'),
		), $this->get_id());

		$settings_fields->add_setting('add_new_product_group', array(
			'priority' => 10000,
			'callback' => array($this, 'add_group_setting_field'),
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function product_groups_settings_field() { ?>
		<tbody
			:key="product_group?.id"
			class="sortable-item"
			v-for="(product_group, index_no) in <?php echo esc_attr($this->get_model_key('groups')) ?>">
			<template
				:hide-heading="false"
				:tier-no="index_no + 1"
				:feature-data="product_group"
				is="vue:feature-product-based-shipping"
				@update="(value) => <?php echo esc_attr($this->get_model_key('groups')) ?>[index_no] = value"
				@delete="delete_collection('<?php echo esc_attr($this->get_model_key('groups')) ?>', index_no)"
				delete-warning="<?php esc_html_e('Are you sure you want to delete this Product Group?', 'shipflex') ?>"
				@duplicate="(value, position) => duplicate_collection('<?php echo esc_attr($this->get_model_key('groups')) ?>', value, position)">
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
	public function add_group_setting_field(Form_Control $form_control) {
		$form_control->output_row(); ?>
		<td class="no-padding" colspan="2">
			<a style="--inputHeight: 46px;font-size: 16px" @click.prevent="add_collection('<?php echo esc_attr($this->get_model_key('groups')) ?>')" class="button button-primary button-full-width" href="#">
				<?php esc_html_e('+ Add Product Group', 'shipflex'); ?>
			</a>
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

		<?php $this->output_heading_row(esc_html__('Product Group #{{tierNo}}', 'shipflex'), array($this->get_id())) ?>
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

		$table_rates_layers = $cart_based_component->get_setting('table_rates_layers', 'cart-tier');
		$settings_fields->add_setting('table_rates_layers', $table_rates_layers, 'product');

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
				cart-option-type="products"
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