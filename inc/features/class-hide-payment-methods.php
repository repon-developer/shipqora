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

final class Hide_Payment_Methods extends Feature {

	/**
	 * Provides common helper methods of component
	 *
	 * @since 1.0.0
	 */
	use Component_Methods;

	/**
	 * Hold the feature id of this feature
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	protected $feature_id = 'hide-payment-methods';

	/**
	 * Hold settings of lite layer
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	protected $lite_layer = null;

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
			'priority' => 30,
			'standalone' => true,
			'feature_priority' => 3,
			'base_model' => 'hide_payment_methods',
			'name' => esc_html__('Hide Payment Methods', 'shipqora'),
			'section_title' => esc_html__('Hide Payment Methods', 'shipqora'),
			'description' => esc_html__('If the selected shipping method(s) are chosen on the checkout page, hide the selected payment methods.', 'shipqora'),
		);
	}

	/**
	 * Get all hideable shipping rates
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function payment_methods() {
		$layer_items = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('feature', $this->get_id(), 'layers'),
			array($this->lite_layer),
			$this
		);

		$payment_gatways = array();
		foreach ($layer_items as $layer_item) {
			if (isset($layer_item['payment_methods']) && is_array($layer_item['payment_methods'])) {
				if (isset($layer_item['condition_groups'])) {
					$condition_matched = Main::get_instance()->is_matched_conditions($layer_item['condition_groups']);
					if (false == $condition_matched) {
						continue;
					}
				}

				$payment_gatways = array_merge($payment_gatways, $layer_item['payment_methods']);
			}
		}

		return array_filter($payment_gatways);
	}

	/**
	 * Output component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_component() {
		$settings_fields = Settings_Fields::get_instance($this->get_id());
		$action_contents = apply_filters(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			Utils::get_hook_name('component-heading-actions', $this->get_id()),
			null
		); ?>

		<?php $this->output_heading_row(esc_html__('Hide Tier #{{tierNo}}', 'shipqora'), array($this->get_id())) ?>
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

		$settings_fields->add_setting('layer_notice_callback', array(
			'priority' => 100000,
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array($this, 'layer_notice_callback'),
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
				is="vue:feature-hide-payment-methods"
				:feature-data="<?php echo esc_attr($this->get_model_key('lite_layer')) ?>"
				@update="(value) => <?php echo esc_attr($this->get_model_key('lite_layer')) ?> = value"
				<?php $this->output_component_attrs('hide-payment-methods', array(':hide-heading' => 'true')) ?>>
			</template>
		</tbody>
	<?php
	}

	/**
	 * Add new adjustment tier notice
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function layer_notice_callback(Form_Control $form_control) {
		$line_button_data = array('utm_source' => 'hide+other+shipping+methos+tier');
		$form_control->output_row(); ?>
		<td colspan="2">
			<div class="shipqora-notice-box">
				<h3>⚡ Need Multiple Hiding Tiers?</h3>
				<div class="description">Upgrade to <strong>ShipQora Pro</strong> to add unlimited hiding tiers. Create advanced, multi-layered rules to hide different payment methods for different shipping methods, products, or customer roles simultaneously.</div>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Add component settings field 
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_component_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('payment_methods', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'payment_methods',
			'callback' => array($this, 'hide_payment_methods'),
			'label' => esc_html__('Payment Methods to Hide', 'shipqora'),
			'label_note' => esc_html__('Select the payment methods (e.g., Cash on Delivery, Stripe) to hide when a customer chooses any of the selected shipping methods above.', 'shipqora'),
			'option_note' => esc_html__('Select the payment methods to hide when a customer chooses any of the selected shipping methods above.', 'shipqora'),
		), 'layer');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(General::class, 'condition_group_setting_field'),
		), 'layer');
	}

	/**
	 * Output hide payment methods options
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function hide_payment_methods(Form_Control $form_control) {
		$form_control->output_before_input_options(); ?>
		<ul class="shipqora-repeater" v-if="<?php echo esc_attr($form_control->get_model_key()) ?>?.length" style="margin-bottom: 8px;" v-sortable="{options: {handle: '.button-drag-item'}}" @end="order_change">
			<li class="repeater-item" v-for="(payment_method, index) in <?php echo esc_attr($form_control->get_model_key()) ?>" :key="payment_method">
				<span class="button-drag-item dashicons dashicons-menu-alt2" v-if="<?php echo esc_attr($form_control->get_model_key()) ?>?.length > 1"></span>
				<select v-model="payment_methods[index]">
					<option value=""><?php esc_html_e('Choose a Payment Method', 'shipqora') ?></option>
					<?php
					$payment_gateways = WC()->payment_gateways()->payment_gateways();
					foreach ($payment_gateways as $gateway_id => $payment_gateway) {
						printf('<option value="%s">%s</option>', esc_attr($gateway_id), esc_html($payment_gateway->get_title()));
					} ?>
				</select>

				<div class="tools" v-if="!loading">
					<a href="#" @click.prevent="delete_payment_method(index)" class="btn-delete-item dashicons dashicons-no-alt"></a>
				</div>
			</li>
		</ul>

		<a href="#" class="button" @click.prevent="add_payment_method()">
			<?php esc_html_e('Add Payment Method', 'shipqora') ?>
		</a>
<?php
		$form_control->output_after_input_options();
	}
}

Feature::add_feature(Hide_Payment_Methods::class);
