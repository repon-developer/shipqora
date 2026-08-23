<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Settings fields class
 */
final class Settings_Fields {

	/**
	 * Hold the current instance of settings fields
	 * 
	 * @since 1.0.0
	 * @var Settings_Fields
	 */
	private static $instance = array();

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Settings_Fields
	 */
	public static function get_instance($context) {
		if (!isset(self::$instance[$context])) {
			self::$instance[$context] = new self($context);
		}

		return self::$instance[$context];
	}

	/**
	 * Hold current context of settings
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	private $context = '';

	/**
	 * Hold settings fields
	 * 
	 * @since 1.0.0
	 * @var array
	 */
	private $settings_fields = array();

	/**
	 * Constructor.
	 * 
	 * @param string $context - rule editor
	 * @since 1.0.0
	 */
	public function __construct($context) {
		$this->context = sanitize_key($context);
	}

	/**
	 * Get settings data of a setting field
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_setting($key, $group) {
		$key = sanitize_key($key);
		$group = sanitize_key($group);
		return isset($this->settings_fields[$group][$key]) ? $this->settings_fields[$group][$key] : false;
	}

	/**
	 * Add setting field
	 * 
	 * @since 1.0.0
	 * @param string $key - key of setting field
	 * @param array $setting_data
	 * @param string $group
	 * @return void
	 */
	public function add_setting($key, $setting_data, $group) {
		$key = sanitize_key($key);
		if (empty($key)) {
			throw new \Exception('Assign a valid key');
		}

		$group = sanitize_key($group);
		$this->settings_fields[$group][$key] = $setting_data;
	}

	/**
	 * Modify setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function modify_setting($key, $setting_data, $group) {
		$group = sanitize_key($group);
		if (!isset($this->settings_fields[$group][$key])) {
			throw new \Exception('This setting key does not exists of this settings group.');
		}

		$this->add_setting($key, $setting_data, $group);
	}

	/**
	 * Remove setting field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function remove_setting($group, $key) {
		unset($this->settings_fields[$group][$key]);
	}

	/**
	 * Assign deep models
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function assign_model(&$original_data, $deep_model_keys, $model_value = null) {
		$model_key_chain = Utils::get_deep_model_split_keys($deep_model_keys);
		if (false === $model_key_chain) {
			return;
		}

		$current_data = &$original_data;
		foreach ($model_key_chain['deep_keys'] as $deep_key) {
			if (!isset($current_data[$deep_key]) || !is_array($current_data[$deep_key])) {
				$current_data[$deep_key] = [];
			}

			$current_data = &$current_data[$deep_key];
		}

		$last_key = $model_key_chain['last_key'];
		if (is_array($model_value) && isset($current_data[$last_key]) && is_array($current_data[$last_key])) {
			$model_value = Utils::deep_merge_arrays($current_data[$last_key], $model_value);
		}

		$current_data[$last_key] = $model_value;
	}

	/**
	 * Get available model of current type of field
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_models() {
		$settings_models = array();
		foreach ($this->settings_fields as $group) {
			foreach ($group as $setting_field) {
				if (!empty($setting_field['model_key'])) {
					$model_value = isset($setting_field['default_value']) ? $setting_field['default_value'] : null;
					$this->assign_model($settings_models, $setting_field['model_key'], $model_value);
				}

				if (isset($setting_field['related_models']) && is_array($setting_field['related_models'])) {
					foreach ($setting_field['related_models'] as $model_key => $related_model_value) {
						$this->assign_model($settings_models, $model_key, $related_model_value);
					}
				}

				if (isset($setting_field['sub_settings_fields']) && is_array($setting_field['sub_settings_fields'])) {
					foreach ($setting_field['sub_settings_fields'] as $sub_setting_field) {
						if (!empty($sub_setting_field['model_key'])) {
							$model_value = isset($sub_setting_field['default_value']) ? $sub_setting_field['default_value'] : null;
							$this->assign_model($settings_models, $sub_setting_field['model_key'], $model_value);
						}

						if (isset($sub_setting_field['related_models']) && is_array($sub_setting_field['related_models'])) {
							foreach ($sub_setting_field['related_models'] as $model_key => $related_model_value) {
								$this->assign_model($settings_models, $model_key, $related_model_value);
							}
						}
					}
				}
			}
		}

		return $settings_models;
	}

	/**
	 * Get settings fields of group
	 * 
	 * @since 1.0.0
	 * @param string $group - feature key
	 * @return array
	 */
	public function get_settings_fields($group) {
		$group = sanitize_key($group);

		$settings_fields = array();
		if (isset($this->settings_fields[$group])) {
			$settings_fields = $this->settings_fields[$group];
		}

		$settings_fields = array_map(fn($setting_field) => wp_parse_args($setting_field, array('priority' => 10)), $settings_fields);
		uasort($settings_fields, fn($a, $b) => $a['priority'] > $b['priority'] ? 1 : -1);

		return $settings_fields;
	}

	/**
	 * Output settings fields
	 * 
	 * @since 1.0.0
	 * @param string $group - feature slug
	 * @return array
	 */
	public function output_fields($group) {
		$settings_fields = $this->get_settings_fields($group);
		foreach ($settings_fields as $field_id => $setting_field) {
			(new Form_Control($setting_field, $field_id))->render();
		}
	}
}
