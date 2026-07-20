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
			'section_title' => esc_html__('Hide Shipping Methods Settings', 'shipflex'),
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
		$settings_fields->add_setting('condition-groups', array(
			'priority' => 10,
			'default_value' => array(),
			'label' => esc_html__('Active Features', 'shipflex'),
			'model_key' => 'hide_shipping_methods.condition_groups',
			'callback' => array($this, 'condition_group_settings_field'),
			'label_note' => esc_html__('Configure how this reward is calculated in the above "Product Settings".', 'shipflex'),
			'option_note' => esc_html__('These settings control the discount type, value, and how the discount is applied during checkout.', 'shipflex'),
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
	 * Output visibility condition setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function condition_group_settings_field(Form_Control $form_control) {
		$form_control->output_open_row(); ?>
		<td colspan="2">
			<div class="shipflex-repeater shipflex-repeater-condition-groups" v-if="hide_shipping_methods.condition_groups?.length > 0">
				<template v-for="(group, index) in hide_shipping_methods.condition_groups" :key="group?.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('or', 'shipflex') ?>"></div>
					<div class="repeater-item">
						<condition-group
							:group="group"
							@delete="delete_collection('hide_shipping_methods.condition_groups', index)"
							@update="(group_data) => hide_shipping_methods.condition_groups[index] = group_data">
						</condition-group>
					</div>
				</template>
			</div>

			<button class="button" :class="{'button-large-dashed button-full-width': !hide_shipping_methods.condition_groups?.length}" @click.prevent="add_collection('hide_shipping_methods.condition_groups')">
				<?php esc_html_e('Add condition group', 'shipflex') ?>
			</button>
		</td>
<?php
		$form_control->output_close_row();
	}

	/**
	 * Visible shipping rate or not based on condition
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function hide_shipping_methods() {
		return Main::get_instance()->is_matched_conditions($this->condition_groups);
	}
}

Feature::add_feature(Hide_Shipping_Methods::class);
