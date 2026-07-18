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
		add_action('init', array($this, 'add_rule_editor_settings_fields'), 1);
	}

	/**
	 * Add settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_rule_editor_settings_fields() {
		$editor_settings_fields = Settings_Fields::get_instance('rule-editor');

		$editor_settings_fields->add_setting('shipping_instances', array(
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
			$registered_feature_options[$feature_id] = array(
				'label' => $feature_configuration['instance']->get_configuration_value('name'),
				'description' => $feature_configuration['instance']->get_configuration_value('description'),
			);
		}

		$editor_settings_fields->add_setting('active_features', array(
			'priority' => 10,
			'default_value' => array('visibility-condition'),
			'model_key' => 'active_features',
			'option_type' => 'checkbox',
			'type' => Form_Control::MULTIPLE_OPTIONS,
			'options' => $registered_feature_options,
			'label' => esc_html__('Active Features', 'shipflex'),
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
		), 'general');

		foreach ($registered_features as $feature_id => $feature_configuration) {
			$feature_configuration['instance']->add_editor_settings_fields($editor_settings_fields);
		}

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
			<li class="repeater-item" :key="instance_index" v-for="(instance, instance_index) in shipping_instances">
				<select2-dropdown
					:multiple="false"
					type="shipping_instances"
					@update="(value) => update_shipping_instance(value, instance_index)"
					placeholder="<?php esc_html_e('Choose a Shipping Instance', 'shipflex') ?>">
				</select2-dropdown>

				<div class="tools">
					<a href="#" @click.prevent="remove_shipping_instance(instance_index)" class="btn-delete-item dashicons dashicons-no-alt"></a>
				</div>
			</li>
		</ul>

		<a href="#" class="button" :class="{'button-small': shipping_instances?.length > 0, 'button-large-dashed': !shipping_instances?.length}" @click.prevent="add_shipping_instance()"><?php esc_html_e('Add a Shipping Instance', 'shipflex') ?></a>

<?php
		$form_control->output_after_input_options();
	}
}

new General();
