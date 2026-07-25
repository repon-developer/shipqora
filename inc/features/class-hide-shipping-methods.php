<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Condition\Main;
use ShipFlex\Settings_Fields;

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
	 * Hold available condition groups
	 * 
	 * @var array
	 */
	protected $condition_groups = [];

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
			'priority' => 10,
			'base_model' => 'hide_shipping_methods',
			'name' => esc_html__('Hide Selected Shipping Methods', 'shipflex'),
			'section_title' => esc_html__('Hide Selected Shipping Methods', 'shipflex'),
			'description' => esc_html__('Hide selected shipping methods when the configured conditions are met.', 'shipflex'),
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
			'callback' => array(General::class, 'condition_group_setting_field'),
			'extra_settings' => array(
				'add_group_method' => "add_collection('hide_shipping_methods.condition_groups')",
				'delete_group_method' => "delete_collection('hide_shipping_methods.condition_groups', index)",
			)
		), $this->get_id());
	}

	/**
	 * Output settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_rule_editor(Settings_Fields $settings_fields) { ?>
		<table class="table-shipflex-form">
			<thead>
				<tr>
					<td colspan="2"><?php echo esc_html($this->get_configuration_value('section_title')) ?></td>
				</tr>
			</thead>

			<?php $settings_fields->output_fields($this->get_id()); ?>
		</table>
	<?php
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

//Feature::add_feature(Hide_Shipping_Methods::class);