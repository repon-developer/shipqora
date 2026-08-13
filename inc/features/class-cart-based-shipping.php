<?php

namespace ShipQora\Feature;

use ShipQora\Cart_Total;
use ShipQora\Component\Cart_Option;
use ShipQora\Utils;
use ShipQora\Feature;
use ShipQora\Form_Control;
use ShipQora\Shipping_Cost;
use ShipQora\Condition\Main;
use ShipQora\Settings_Fields;
use ShipQora\Component_Methods;
use ShipQora\Component\Table_Rates_Shipping;

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
			'priority' => 50,
			'feature_priority' => 10,
			'base_model' => 'cart_based_shipping',
			'name' => esc_html__('Cart-Based Shipping Cost', 'shipqora'),
			'section_title' => esc_html__('Cart-Based Shipping Cost', 'shipqora'),
			'description' => esc_html__('Calculate shipping costs dynamically based on cart total, item count, weight, or volume.', 'shipqora'),
		);
	}

	/**
	 * Set shipping cost
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 */
	public function modify_shipping_rate($shipping_rate) {
		$layers = $this->get_shipping_rate_data($shipping_rate);
		if (count($layers) == 0) {
			return;
		}

		$layers = $this->order_priority($layers);

		$best_layer = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			$this->get_hook('applicable-layer'),
			end($layers),
			$layers,
			$this
		);

		if (array_key_exists('calculated_shipping_cost', $best_layer)) {
			$shipping_rate->set_cost($best_layer['calculated_shipping_cost']);
		}
	}

	/**
	 * Calculate shipping cost of tier item
	 * 
	 * @since 1.0.0
	 * @param array $tier_item
	 */
	public function calculate_shipping_cost($current_layer, $cart_total) {
		$current_layer = wp_parse_args($current_layer, array(
			'calculate_basis' => '',
			'calculation_type' => '',
			'calculation_value' => '',
			'target_products' => array(),
			'condition_groups' => array(),
			'table_rates_layers' => array(),
		));

		$calculate_metrics = array('subtotal', 'quantity', 'weight', 'volume');

		$calculate_basis = isset($current_layer['calculate_basis']) ? $current_layer['calculate_basis'] : null;
		if (!in_array($calculate_basis, array('fixed_amount', ...$calculate_metrics))) {
			return;
		}

		$calculation_value = isset($current_layer['calculation_value']) ? trim($current_layer['calculation_value']) : '';
		$calculation_value = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			$this->get_hook('layer', 'calculation-value'),
			$calculation_value,
			$current_layer,
			$this
		);

		if (strlen($calculation_value) == 0 && 'table_rates' !== $current_layer['calculation_type']) {
			return;
		}

		try {
			$calculation_value = floatval($calculation_value);
			if ('fixed_amount' == $calculate_basis) {
				throw new Shipping_Cost($calculation_value);
			}

			if (in_array($calculate_basis, $calculate_metrics)) {
				$calculation_type = isset($current_layer['calculation_type']) ? $current_layer['calculation_type'] : null;

				$metrics_total = $cart_total->get_total($calculate_basis);
				if ($metrics_total <= 0) {
					throw new Shipping_Cost(-1);
				}

				if ('per_unit_or_percentage' == $calculation_type && $calculation_value > 0) {
					$shipping_cost = $metrics_total * $calculation_value;
					if ('subtotal' == $calculate_basis) {
						$shipping_cost = $shipping_cost / 100;
					}

					throw new Shipping_Cost($shipping_cost);
				}

				$table_rates_layers = array();
				if ('table_rates' == $calculation_type && isset($current_layer['table_rates_lite']) && is_array($current_layer['table_rates_lite'])) {
					$table_rates_layers[] = $current_layer['table_rates_lite'];
				}

				$table_rates_layers = apply_filters(
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
					$this->get_hook('table-rates-layers'),
					$table_rates_layers,
					$current_layer,
					$this
				);

				if (count($table_rates_layers) == 0) {
					throw new Shipping_Cost(-1);
				}

				$table_rates_layers = array_map(fn($range_layer) => new Table_Rates_Shipping($range_layer), $table_rates_layers);
				$table_rates_layers = array_filter($table_rates_layers, function ($item) {
					if (!$item->has_validate_ranges()) {
						return false;
					}

					return $item->is_condition_matched();
				});

				if (count($table_rates_layers) == 0) {
					throw new Shipping_Cost(-1);
				}

				array_walk($table_rates_layers, function (&$table_rate_layer) use ($metrics_total, $current_layer) {
					$table_rate_layer->total_shipping_cost = $table_rate_layer->calculate_shipping_cost($metrics_total, $current_layer['calculate_basis']);
				});

				$table_rate_layer = apply_filters(
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
					$this->get_hook('applicable-table-rate-layer'),
					end($table_rates_layers),
					$table_rates_layers
				);

				throw new Shipping_Cost($table_rate_layer->total_shipping_cost);
			}

			throw new Shipping_Cost(-1);
		} catch (Shipping_Cost $e) {
			$current_layer['calculated_shipping_cost'] = $e->getAmount();
		}

		return $current_layer;
	}

	/**
	 * Add shipping rate data
	 * 
	 * @since 1.0.0
	 * @param WC_Shipping_Rate $shipping_rate
	 * @param int $rule_id
	 */
	public function set_shipping_rate_data($shipping_rate, $rule_id) {
		$layers = $this->get_feature_layers($this->lite_layer);
		if (count($layers) == 0) {
			return;
		}

		$cart_total = new Cart_Total();

		array_walk($layers, function (&$current_layer) use ($shipping_rate, $cart_total, $rule_id) {
			if (isset($group['condition_groups'])) {
				$is_matched = Main::get_instance()->is_matched_conditions($group['condition_groups']);
				if (!$is_matched) {
					return;
				}
			}

			$current_layer['rule_id'] = $rule_id;

			if (!isset($current_layer['target_products'])) {
				$current_layer['target_products'] = array();
			}

			$cart_option = apply_filters(
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				$this->get_hook('cart-option-object'),
				new Cart_Option($current_layer['target_products']),
				$current_layer,
				$this
			);

			$cart_total->set_cart_items_keys($cart_option->get_cart_items_keys());

			$current_layer = $this->calculate_shipping_cost($current_layer, $cart_total);
			$current_layer = apply_filters(
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				$this->get_hook('layer'),
				$current_layer,
				$this
			);

			if ($current_layer['calculated_shipping_cost'] >= 0) {
				$this->add_shipping_rate_data($shipping_rate, $current_layer);
			}
		});
	}

	/**
	 * Add settings field of rule editor of current feature
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('lite_layer_settings', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => $this->get_model_key('lite_layer'),
			'callback' => array($this, 'lite_layer_settings_field'),
		), $this->get_id());

		$settings_fields->add_setting('show_cart_tier_notice', array(
			'priority' => 100000,
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array(General::class, 'notice_setting_field'),
			'notice_content' => array(
				'title' => '💡 Unlock Unlimited Cart-Based Shipping Tiers',
				'utm_source' => 'cart+based+shipping+cost+layer',
				'description' => 'Upgrade to the Pro version to create unlimited shipping tiers and build complex, tiered shipping rules based on cart conditions.',
			)
		), $this->get_id());
	}

	/**
	 * Output lite tier settings field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function lite_layer_settings_field() { ?>
		<tbody>
			<template
				:draggable="false"
				is="vue:feature-cart-based-shipping"
				:feature-data="<?php echo esc_attr($this->get_model_key('lite_layer')) ?>"
				@update="(value) => <?php echo esc_attr($this->get_model_key('lite_layer')) ?> = value"
				<?php $this->output_component_attrs('cart-based-shipping', array(
					':hide-heading' => 'true',
					':hide-actions' => '["delete"]'
				)) ?>>
			</template>
		</tbody>
	<?php
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id()); ?>
		<?php $this->output_heading_row(esc_html__('Tier #{{layerNo}}', 'shipqora'), array($this->get_id())) ?>
		<template v-if="!collapse">
			<?php $settings_fields->output_fields('layer') ?>
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
			'label' => esc_html__('Target Cart Items', 'shipqora'),
			'callback' => array($this, 'target_products_setting_field'),
			'label_note' => esc_html__('Select which cart items this rule applies to. You can target all items or filter by specific categories, tags, shipping classes, or taxonomies.', 'shipqora'),
			'option_note' => esc_html__('Shipping cost calculations will apply to the combined total (subtotal, quantity, weight, or volume) of all matching items found in the cart.', 'shipqora'),
		), 'layer');

		$settings_fields->add_setting('exclude_products', array(
			'priority' => 10.10,
			'conditions' => array('layerNo == 1'),
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array(General::class, 'notice_setting_field'),
			'notice_content' => array(
				'title' => '🚀 Want to Exclude Specific Products?',
				'utm_source' => 'exclude+products',
				'description' => 'Upgrade to the <strong>Pro version</strong> to exclude selected products from the <strong>"Target Cart Items"</strong> and create more precise shipping cost with greater control over product eligibility.',
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

		$settings_fields->add_setting('shipping_cost_calculation', array(
			'priority' => 40,
			'label' => esc_html__('Calculate Cost By', 'shipqora'),
			'callback' => array($this, 'shipping_cost_setting_field'),
			'label_note' => esc_html__('Choose how the shipping cost is determined based on cart subtotal, item quantity, total weight, or total volume.', 'shipqora'),
			'row_attributes' => array(
				':data-highlight-section' => "'shipping-cost-calculation-' + id"
			),
			'related_models' => array(
				'calculation_value' => '',
				'calculate_basis' => 'fixed_amount',
				'calculation_type' => 'per_unit_or_percentage',
			)
		), 'layer');

		$settings_fields->add_setting('table_rates_settings', array(
			'priority' => 50,
			'label' => esc_html__('Table Rates', 'shipqora'),
			'label_note' => esc_html__('Configure volume, weight, subtotal, or quantity thresholds and fee calculations for each tier range. Use condition groups to control which rates apply.', 'shipqora'),
			'conditions' => array('calculate_basis !== "fixed_amount" && calculation_type == "table_rates"'),
			'sub_settings_wrap_table' => false,
			'sub_settings_fields' => array(
				'table_rates_lite_setting_field' => array(
					'priority' => 5,
					'model_key' => 'table_rates_lite',
					'default_value' => (object) array(),
					'callback' => array($this, 'table_rates_lite_setting_field'),
				),

				'new_table_rates_notice' => array(
					'priority' => 100000,
					'callback' => array($this, 'new_table_rates_notice'),
				)
			)
		), 'layer');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'layer');
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
				option-label="<?php esc_html_e('All cart items of selected {{option_label_lower}}', 'shipqora') ?>">
				<template v-slot:based-on-first-option>
					<option value=""><?php esc_html_e('All cart items', 'shipqora') ?></option>
				</template>
			</cart-option>
		</div>
	<?php
		$form_control->output_after_input_options();
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
				<option value="fixed_amount"><?php esc_html_e('Fixed Amount', 'shipqora') ?></option>
				<option v-for="(metric, value) in calculation_metrics" :value="value" :key="value">{{metric.long_title}}</option>
			</select>

			<select v-model="calculation_type" v-if="calculate_basis !== 'fixed_amount'">
				<option value="per_unit_or_percentage">
					<template v-if="'subtotal' == calculate_basis"><?php esc_html_e('Percentage', 'shipqora') ?></template>
					<template v-if="'subtotal' != calculate_basis">{{calculation_type_label}}</template>
				</option>
				<option value="table_rates"><?php esc_html_e('Table Rates', 'shipqora') ?></option>
			</select>

			<template v-if="show_calculation_value">
				<input v-model="calculation_value" type="number" min="0" placeholder="0.00">
				<span v-if="calculate_basis == 'subtotal'">%</span>
			</template>
		</div>

		<div class="field-note" v-if="calculate_basis == 'fixed_amount'">
			<?php esc_html_e('Applies a single fixed shipping cost.', 'shipqora') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'subtotal'">
			<?php esc_html_e('Choose "Percentage" to charge a % of the item value, or "Table Rates" for subtotal ranges.', 'shipqora') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'quantity'">
			<?php esc_html_e('Charge a rate per item unit (e.g. $2 per item), or choose "Table Rates" for quantity brackets.', 'shipqora') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'weight'">
			<?php esc_html_e('Charge a rate per weight unit (e.g. $1.50 per kg), or choose "Table Rates" for weight brackets.', 'shipqora') ?>
		</div>

		<div class="field-note" v-if="calculate_basis == 'volume'">
			<?php esc_html_e('Charge a rate per volume unit (e.g. $0.50 per cm³), or choose "Table Rates" for volume brackets.', 'shipqora') ?>
		</div>
	<?php
		$form_control->output_after_input_options();
	}

	/**
	 * Output setting field of table rates
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function table_rates_lite_setting_field(Form_Control $form_control) { ?>
		<table-rates-shipping
			:draggable="false"
			:calculate-basis="calculate_basis"
			:table-rate-data="<?php echo esc_attr($form_control->get_model_key()) ?>"
			@update="(table_rate_data) => <?php echo esc_attr($form_control->get_model_key()) ?> = table_rate_data"
			<?php $this->output_component_attrs('table-rates-lite', array(':hide-heading' => 'false', ':hide-actions' => array('duplicate', 'delete'))) ?>>
		</table-rates-shipping>
	<?php
	}

	/**
	 * Output add new layer button
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function new_table_rates_notice(Form_Control $form_control) {
		$line_button_data = array('utm_source' => 'table+rates+layer'); ?>

		<div class="shipqora-notice-box">
			<h3>💡 Unlock Unlimited Table Rate Tiers</h3>
			<div class="description">Upgrade to the Pro version to create unlimited table rate tiers and build complex, multi-layered shipping rules with advanced conditions.</div>
			<div class="gap-10"></div>
			<?php Utils::get_lite_button($line_button_data) ?>
		</div>
<?php
	}
}

Feature::add_feature(Cart_Based_Shipping::class);
