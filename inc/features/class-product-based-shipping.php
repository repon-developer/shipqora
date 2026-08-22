<?php

namespace ShipQora\Feature;

use ShipQora\Utils;
use ShipQora\Feature;
use ShipQora\Cart_Total;
use ShipQora\Form_Control;
use ShipQora\Condition\Main;
use ShipQora\Settings_Fields;
use ShipQora\Component_Methods;
use ShipQora\Component\Cart_Option;

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
	protected function get_configuration_settings() {
		return array(
			'priority' => 60,
			'feature_priority' => 20,
			'base_model' => 'product_based_shipping',
			'name' => esc_html__('Product-Based Shipping Cost', 'shipqora'),
			'section_title' => esc_html__('Product-Based Shipping Cost', 'shipqora'),
			'description' => esc_html__('Apply product-specific shipping costs to the selected shipping methods when the conditions are met.', 'shipqora'),
		);
	}

	/**
	 * Modify Shipping Rate object
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 */
	public function modify_shipping_rate($shipping_rate) {
		$product_items = array_map(function ($line_items) {
			$line_items = array_map(fn($item) => $this->calculate_line_item($item), $line_items);
			$line_items = array_filter($line_items, fn($item) => $item['calculated_shipping_cost'] >= 0);
			$line_items = $this->order_priority($line_items);
			if (count($line_items) == 0) {
				return false;
			}

			return end($line_items);
		}, $this->line_items);

		$product_items = array_filter($product_items, fn($product) => is_array($product) && $product['calculated_shipping_cost'] >= 0);
		if (count($product_items) == 0) {
			return;
		}

		$shipping_cost = array_sum(wp_list_pluck($product_items, 'calculated_shipping_cost'));
		if ($shipping_cost < 0) {
			return;
		}

		$best_item = array_reduce($product_items, function ($carry, $current_item) {
			if (!$carry) {
				return $current_item;
			}

			if ($carry['calculated_shipping_cost'] > $current_item['calculated_shipping_cost']) {
				return $carry;
			}

			return $current_item;
		});

		if (!empty($best_item['shipping_method_title'])) {
			$shipping_rate->set_label($best_item['shipping_method_title']);
		}

		$shipping_rate->set_cost($shipping_cost);
	}

	/**
	 * Manage feature
	 * 
	 * @since 1.0.0
	 * @param ShipQora_Rule $rule
	 */
	public function manage_feature($rule) {
		$groups = $rule->get_feature_value($this->get_model_key('groups'));
		if (!is_array($groups) || (is_array($groups) && count($groups) == 0)) {
			return;
		}

		$shipping_method_title = $rule->get_feature_value($this->get_model_key('shipping_method_title'));

		$cart_total = new Cart_Total();
		array_walk($groups, function (&$group) use ($rule, $cart_total, $shipping_method_title) {
			if (isset($group['condition_groups'])) {
				$is_matched = Main::get_instance()->is_matched_conditions($group['condition_groups']);
				if (!$is_matched) {
					return;
				}
			}

			$group['rule_id'] = $rule->get_id();
			$group['shipping_method_title'] = $shipping_method_title;

			$cart_option = apply_filters(
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				Utils::get_hook_name('feature', 'cart-option-object'),
				new Cart_Option($group['target_products']),
				$group,
				$this
			);

			$calculate_basis = '';
			if (!empty($group['calculate_basis'])) {
				$calculate_basis = $group['calculate_basis'];
			}

			$cart_items = $cart_total->get_cart_items();
			foreach ($cart_items as $cart_item_key => $cart_item) {
				$is_eligible_product = $cart_option->is_eligible_product($cart_item['product_id'], $cart_item['variation_id']);
				if (!$is_eligible_product) {
					continue;
				}

				$cart_total->set_cart_items_keys(array($cart_item_key));
				$group['metrics_total'] = $cart_total->get_total($calculate_basis);

				$product_key = $cart_item['product_id'] . '-' . $cart_item['variation_id'];
				$this->line_items[$product_key][] = $group;
			}
		});
	}

	/**
	 * Get wrapper attributes of this feature section
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_wrapper_attributes() {
		return array(
			'data-skip-order' => 2,
			'data-group' => 'feature',
			'@end' => 'on_order_change',
			'data-model-key' => $this->get_model_key('groups'),
			'v-sortable' => '{options: {handle: \'.button-drag\', draggable: \'>tbody.sortable-item\'}}',
		);
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('shipping_method_title', array(
			'priority' => 10,
			'type' => Form_Control::TEXTBOX,
			'model_key' => $this->get_model_key('shipping_method_title'),
			'label' => esc_html__('Overwrite Shipping Method Title', 'shipqora'),
			'label_note' => esc_html__('Enter a custom title to replace the original shipping method name on the cart and checkout pages.', 'shipqora'),
			'option_note' => esc_html__('Leave blank to keep the original shipping method name.', 'shipqora'),
		), $this->get_id());

		$settings_fields->add_setting('product_groups_settings_field', array(
			'priority' => 100,
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
			class="sortable-item"
			:key="product_group?.id"
			v-for="(product_group, layer_no) in <?php echo esc_attr($this->get_model_key('groups')) ?>">
			<template
				:hide-heading="false"
				:layer-no="layer_no + 1"
				:feature-data="product_group"
				is="vue:feature-product-based-shipping"
				@update="(value) => <?php echo esc_attr($this->get_model_key('groups')) ?>[layer_no] = value"
				@delete="delete_collection('<?php echo esc_attr($this->get_model_key('groups')) ?>', layer_no)"
				delete-warning="<?php esc_html_e('Are you sure you want to delete this Product Group?', 'shipqora') ?>"
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
				<?php esc_html_e('+ Add Product Group', 'shipqora'); ?>
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

		<?php $this->output_heading_row(esc_html__('Product Group #{{layerNo}}', 'shipqora'), array($this->get_id())) ?>
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
		$cart_based_settings_fields = Settings_Fields::get_instance('cart-based-shipping')->get_settings_fields('general');
		unset($cart_based_settings_fields['shipping_method_title']);

		$cart_based_settings_fields['target_products'] = wp_parse_args(array(
			'label' => esc_html__('Target Products', 'shipqora'),
			'callback' => array($this, 'target_products_setting_field'),
			'label_note' => esc_html__('Select which products this tier applies to. Filter by specific categories, tags, shipping classes, or taxonomies.', 'shipqora'),
			'option_note' => esc_html__('Shipping cost will be calculated individually for each matching product item in the cart, and the total will be the sum of those costs.', 'shipqora'),
		), $cart_based_settings_fields['target_products']);

		$cart_based_settings_fields['exclude_products']['notice_content'] = array(
			'utm_source' => 'exclude+products',
			'title' => '🚀 Want to Exclude Specific Products?',
			'description' => 'Upgrade to the <strong>Pro version</strong> to exclude selected products from the <strong>"Target Products"</strong> and create more precise shipping cost with greater control over product eligibility.',
		);

		foreach ($cart_based_settings_fields as $setting_key => $setting_field_data) {
			$settings_fields->add_setting($setting_key, $setting_field_data, 'product');
		}
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
				cart-option-type="products"
				:cart-option-data="<?php echo esc_attr($form_control->get_model_key()) ?>"
				@on-update="(value) => <?php echo esc_attr($form_control->get_model_key()) ?> = value"
				option-label="<?php esc_html_e('Products in selected {{option_label_lower}}', 'shipqora') ?>">
				<template v-slot:based-on-first-option>
					<option value=""><?php esc_html_e('All products in cart', 'shipqora') ?></option>
				</template>
			</cart-option>
		</div>
<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Product_Based_Shipping::class);
