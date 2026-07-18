<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Adjust Cost class
 */
final class Adjust_Cost extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'adjust-shipping-cost';

	/**
	 * Constructor.
	 */
	public function __construct($data = null) {
	}

	/**
	 * Configuration of this feature
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_configuration() {
		return array(
			'priority' => 10,
			'base_model' => 'adjust_shipping_cost',
			'name' => esc_html__('Adjust Shipping Cost', 'shipflex'),
			'description' => esc_html__('Adjust Shipping Cost of exists', 'shipflex'),
		);
	}

	/**
	 * Add settings field of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_editor_settings_fields(Settings_Fields $settings_fields) {
	}

	/**
	 * Output settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_rule_editor(Settings_Fields $settings_fields) {
	}
}

Feature::add_feature(Adjust_Cost::class);
