<?php

namespace ShipFlex\Feature;


use ShipFlex\Form_Control;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * General class
 */
final class General {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('init', array($this, 'add_general_settings'), 1);
	}

	/**
	 * Add general settings
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_general_settings() {
		$general_settings = Settings_Fields::get_instance('rule-editor');

		$general_settings->add_setting('shipping_instances', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'shipping_instances',
			'callback' => array($this, 'shipping_instance_setting_field'),
			'type' => Form_Control::MULTIPLE_OPTIONS,
			'label' => esc_html__('Discount Calculation', 'shipflex'),
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
		), 'general');


		$registered_features = \ShipFlex\Feature::get_features();

		$registered_feature_options = array();
		foreach ($registered_features as $feature_id => $feature_configuration) {
			$feature_instance = new $feature_configuration['class_name']();

			$registered_feature_options[$feature_id] = array(
				'label' => $feature_instance->get_configuration_value('name'),
				'description' => $feature_instance->get_configuration_value('description'),
			);
		}

		$general_settings->add_setting('active_features', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'active_features',
			'option_type' => 'checkbox',
			'type' => Form_Control::MULTIPLE_OPTIONS,
			'options' => $registered_feature_options,
			'label' => esc_html__('Active Features', 'shipflex'),
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
		), 'general');
	}

	/**
	 * Output shipping instance setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_instance_setting_field(Form_Control $form_control) {
		$model_key = $form_control->get_model_key();
		$form_control->output_before_input_options(); ?>

		<ul class="shipflex-repeater">
			<li class="repeater-item" v-for="(instance, instance_index) in shipping_instances" :key="instance_index">
				<select2-dropdown
					:multiple="false"
					type="shipping_instances"
					@update="(value) => console.log(value, instance_index)"
					placeholder="<?php esc_html_e('Choose a Shipping Instance', 'shipflex') ?>">
				</select2-dropdown>

				<div class="tools">
					<a href="#" class="btn-delete-item dashicons dashicons-no-alt"></a>
				</div>
			</li>
		</ul>

		<a href="#" class="button button-small" @click.prevent="add_shipping_instance()"><?php esc_html_e('Add a Shipping Instance', 'shipflex') ?></a>

<?php
		$form_control->output_after_input_options();
	}
}

new General();
