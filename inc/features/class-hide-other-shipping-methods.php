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

final class Hide_Other_Shipping_Methods extends Feature {

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
	protected $feature_id = 'hide-other-shipping-methods';

	/**
	 * Hold all hideale shipping rates
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $hideable_rates = array();

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
			'priority' => 20,
			'standalone' => true,
			'feature_priority' => 2,
			'base_model' => 'hide_other_shipping_methods',
			'name' => esc_html__('Hide Other Shipping Methods', 'shipqora'),
			'section_title' => esc_html__('Hide Other Shipping Methods', 'shipqora'),
			'description' => esc_html__('If the selected shipping methods(s) are available on the checkout page, hide the other selected shipping methods.', 'shipqora'),
		);
	}

	/**
	 * Check if current one need to hide
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function hide_shipping_rate($current_rate) {
		$search_data = array();
		$method_id = $current_rate->get_method_id();
		if ('pickup_location' !== $method_id) {
			$zone = \WC_Shipping_Zones::get_zone_by('instance_id', $current_rate->get_instance_id());
			$zone_id = $zone->get_id();

			$search_methods = array(
				$method_id,
				$method_id . ':' . $zone_id . '-0',
				$method_id . ':' . $zone_id . '-' . $current_rate->get_instance_id(),
			);
		} else {
			$search_methods = array($method_id, $current_rate->get_id());
		}

		return count(array_intersect($search_methods, $this->hideable_rates)) > 0;
	}

	/**
	 * Set feature line item
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_line_item($line_item) {
		if (!isset($line_item['shipping_methods']) || !is_array($line_item['shipping_methods'])) {
			return;
		}

		if (isset($line_item['condition_groups'])) {
			$matched = Main::get_instance()->is_matched_conditions($line_item['condition_groups']);
			if (!$matched) {
				return;
			}
		}

		foreach ($line_item['shipping_methods'] as $rate_id) {
			$this->hideable_rates[] = $rate_id;
		}
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

		<?php $this->output_heading_row(esc_html__('Hide Tier #{{layerNo}}', 'shipqora'), array($this->get_id())) ?>
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
		$settings_fields->add_setting('primary_shipping_methods', array(
			'priority' => 10,
			'default_value' => (object) array(),
			'model_key' => $this->get_model_key('primary_hideable_shipping'),
			'callback' => array($this, 'primary_shipping_methods_row'),
		), $this->get_id());

		$settings_fields->add_setting('additional_configuration_notice', array(
			'priority' => 100000,
			'row_attributes' => array('class' => 'shipqora-notice-row'),
			'callback' => array(Global_Settings_Fields::class, 'notice_setting_field'),
			'notice_content' => array(
				'title' => '⚡ Unlock Multiple Hiding Configurations',
				'utm_source' => 'hide+unlimited+shipping+methods',
				'description' => 'Upgrade to <strong>ShipQora Pro</strong> to create multiple hiding configurations and control shipping method visibility without creating separate rules.',
			)
		), $this->get_id());
	}

	/**
	 * Output line items setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function primary_shipping_methods_row(Form_Control $form_control) { ?>
		<tbody>
			<template
				:draggable="false"
				is="vue:feature-hide-other-shipping-methods"
				:feature-data="<?php echo esc_attr($form_control->get_model_key()) ?>"
				@update="(value) => <?php echo esc_attr($form_control->get_model_key()) ?> = value"
				<?php $this->output_component_attrs('hide-other-shipping-methods', array(':hide-heading' => 'true')) ?>>
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
		$settings_fields->add_setting('shipping_methods', array(
			'priority' => 10,
			'default_value' => array(''),
			'model_key' => 'shipping_methods',
			'type' => Form_Control::SHIPPING_METHODS,
			'label' => esc_html__('Shipping Methods to Hide', 'shipqora'),
			'label_note' => esc_html__("Select the shipping methods that should be hidden when this rule's conditions are met.", 'shipqora'),
			'option_note' => esc_html__('Add one or more shipping methods. The selected shipping methods will be hidden.', 'shipqora'),
		), 'general');

		$settings_fields->add_setting('condition_groups', array(
			'priority' => 1000,
			'default_value' => array(),
			'model_key' => 'condition_groups',
			'callback' => array(Global_Settings_Fields::class, 'condition_group_setting_field'),
		), 'general');
	}
}

Feature::add_feature(Hide_Other_Shipping_Methods::class);
