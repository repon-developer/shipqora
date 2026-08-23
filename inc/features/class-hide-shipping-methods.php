<?php

namespace ShipQora\Feature;

use ShipQora\Feature;
use ShipQora\Debugging;
use ShipQora\Form_Control;
use ShipQora\Condition\Main;
use ShipQora\Settings_Fields;
use ShipQora\Global_Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Hide Shipping Methods class
 */
final class Hide_Shipping_Methods extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'hide-shipping-methods';

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
			'priority' => 10,
			'standalone' => true,
			'feature_priority' => 1,
			'base_model' => 'hide_shipping_methods',
			'name' => esc_html__('Hide Selected Shipping Methods', 'shipqora'),
			'section_title' => esc_html__('Hide Selected Shipping Methods', 'shipqora'),
			'description' => esc_html__('Hide selected shipping methods when the configured conditions are met.', 'shipqora'),
		);
	}

	/**
	 * Add settings field of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'hide_shipping_methods.condition_groups',
			'callback' => array(Global_Settings_Fields::class, 'condition_group_setting_field'),
			'extra_settings' => array(
				'add_group_method' => "add_collection('hide_shipping_methods.condition_groups')",
				'delete_group_method' => "delete_collection('hide_shipping_methods.condition_groups', index)",
			)
		), $this->get_id());
	}

	/**
	 * Visible shipping rate or not based on condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function hide_shipping_methods() {
		return Main::get_instance()->is_matched_conditions($this->condition_groups, $this);
	}
}

Feature::add_feature(Hide_Shipping_Methods::class);
