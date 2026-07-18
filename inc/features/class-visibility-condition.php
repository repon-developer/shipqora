<?php

namespace ShipFlex\Feature;

use ShipFlex\Feature;
use ShipFlex\Form_Control;
use ShipFlex\Settings_Fields;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Visibility Condition class
 */
final class Visibility_Condition extends Feature {

	/**
	 * Hold the feature id of this feature
	 * 
	 * @var string
	 */
	protected $feature_id = 'visibility-condition';

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
			'priority' => 5,
			'base_model' => 'visibility_condition',
			'name' => esc_html__('Visibility Condition', 'shipflex'),
			'editor_box_title' => esc_html__('Visibility Conditions', 'shipflex'),
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
		$settings_fields->add_setting('conditions', array(
			'priority' => 10,
			'default_value' => array(),
			'model_key' => 'visibility_condition.conditions',
			'callback' => array($this, 'visibility_condition'),
			'label' => esc_html__('Active Features', 'shipflex'),
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
					<td colspan="2"><?php echo esc_html($this->get_configuration_value('editor_box_title')) ?></td>
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
	public function visibility_condition(Form_Control $form_control) {
		$form_control->output_open_row(); ?>
		<td colspan="2">
			<div class="shipflex-repeater shipflex-repeater-condition-groups" v-if="visibility_condition.conditions?.length > 0">
				<template v-for="(group, index) in visibility_condition.conditions" :key="group.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('or', 'shipflex') ?>"></div>
					<div class="repeater-item">
						<condition-group
							:group="group"
							@delete="delete_collection('visibility_condition.conditions', index)"
							@update="(group_data) => visibility_condition.conditions[index] = group_data">
						</condition-group>
					</div>
				</template>
			</div>

			<button class="button" :class="{'button-large-dashed button-full-width': !visibility_condition.conditions?.length}" @click.prevent="add_collection('visibility_condition.conditions', 'condition_group')">
				<?php esc_html_e('Add condition group', 'shipflex') ?>
			</button>
		</td>
<?php
		$form_control->output_close_row();
	}
}

Feature::add_feature(Visibility_Condition::class);
