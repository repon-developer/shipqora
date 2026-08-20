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
	 * Hold all hideable payment methods
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $hideable_payments = array();

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
	 * Set hideable payment methods
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function set_hideable_methods($line_item) {
		$payment_methods = $line_item['payment_methods'] ?? null;
		if (is_array($payment_methods) && count($payment_methods) > 0) {
			$condition_groups = $line_item['condition_groups'] ?? array();
			$condition_matched = Main::get_instance()->is_matched_conditions($condition_groups);
			if (false === $condition_matched) {
				return;
			}

			foreach ($payment_methods as $gateway_id) {
				$this->hideable_payments[] = $gateway_id;
			}
		}
	}

	/**
	 * Get all hideable payment methods
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_hideable_payment_methods() {
		return $this->hideable_payments;
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

		<?php $this->output_heading_row(esc_html__('Payment Method Hide Configuration #{{layerNo}}', 'shipqora'), array($this->get_id())) ?>
		<template v-if="!collapse">
			<?php $settings_fields->output_fields('general') ?>
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
		$settings_fields->add_setting('primary_settings_row', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => $this->get_model_key('primary_settings'),
			'callback' => array($this, 'primary_settings_row'),
		), $this->get_id());

		$settings_fields->add_setting('additional_item_notice', array(
			'priority' => 100000,
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array(Global_Settings_Fields::class, 'notice_setting_field'),
			'notice_content' => array(
				'title' => '⚡ Need Multiple Payment Hiding Configurations?',
				'utm_source' => 'hide+payment+methos+unlimited',
				'description' => 'Get <strong>ShipQora Pro</strong> to set up and run multiple payment hiding configurations directly inside a single rule.',
			)
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function primary_settings_row() { ?>
		<tbody>
			<template
				:draggable="false"
				is="vue:feature-hide-payment-methods"
				:feature-data="<?php echo esc_attr($this->get_model_key('primary_settings')) ?>"
				@update="(value) => <?php echo esc_attr($this->get_model_key('primary_settings')) ?> = value"
				<?php $this->output_component_attrs('hide-payment-methods', array(':hide-heading' => 'true')) ?>>
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
		$settings_fields->add_setting('payment_methods', array(
			'priority' => 10,
			'default_value' => array(''),
			'model_key' => 'payment_methods',
			'callback' => array($this, 'hide_payment_methods'),
			'label' => esc_html__('Payment Methods to Hide', 'shipqora'),
			'label_note' => esc_html__('Select the payment methods (e.g., Cash on Delivery, Stripe) to hide when a customer chooses any of the selected shipping methods above.', 'shipqora'),
			'option_note' => esc_html__('Select the payment methods to hide when a customer chooses any of the selected shipping methods above.', 'shipqora'),
		), 'general');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(Global_Settings_Fields::class, 'condition_group_setting_field'),
		), 'general');
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
