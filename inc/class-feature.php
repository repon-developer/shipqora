<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Feature class
 */
class Feature {

	/**
	 * Hold all registered features
	 * 
	 * @var array
	 */
	private static $features = array();

	/**
	 * Get available reward types configurations
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function add_feature($feature_class) {
		$feature_instance = new $feature_class();
		self::$features[$feature_instance->get_id()] = $feature_instance;
	}

	/**
	 * Get all registered features
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_features() {
		uasort(self::$features, function ($a, $b) {
			return $a->get_configuration_value('priority') > $b->get_configuration_value('priority') ? 1 : -1;
		});

		return self::$features;
	}

	/**
	 * Hold the feature key of current feature
	 * 
	 * @var string
	 */
	protected $feature_id = '';

	/**
	 * Hold settings of lite tier 
	 * 
	 * @var array
	 */
	protected $lite_tier = [];

	/**
	 * Hold all extra value
	 * 
	 * @var array
	 */
	protected $meta_data = [];

	/**
	 * Constructor.
	 */
	public function __construct($data = null) {
		if (!is_array($data)) {
			return;
		}

		foreach ($data as $key => $value) {
			$this->{$key} = $value;
		}
	}

	/**
	 * isset magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param boolean
	 */
	public function __isset($key) {
		return isset($this->meta_data[$key]);
	}

	/**
	 * Set magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->meta_data[$key] = $value;
	}

	/**
	 * Get magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->meta_data[$key]) ? $this->meta_data[$key] : null;
	}

	/**
	 * Get feature id
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_id() {
		return $this->feature_id;
	}

	/**
	 * Get feature configuration
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_configuration() {
		return array();
	}

	/**
	 * Get value of feature configuration
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_configuration_value($key) {
		$configuration = $this->get_configuration();
		return isset($configuration[$key]) ? $configuration[$key] : null;
	}

	/**
	 * Get model key after add  base model as a prefix
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_model_key($model_key) {
		return $this->get_configuration_value('base_model') . '.' . $model_key;
	}

	/**
	 * Get shipping cost after filter
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_shipping_cost($amount, $tier_data) {
		return apply_filters(Utils::get_hook_name('feature', $this->get_id(), 'shipping-cost'), $amount, $tier_data, $this);
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
	 * Output section wrapper attributes
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_wrapper_attributes() {
	}

	/**
	 * Output settings fields of rule editor
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_rule_editor(Settings_Fields $settings_fields) {
?>
		<table class="table-shipflex-form" <?php $this->output_wrapper_attributes() ?>>
			<thead>
				<tr>
					<td colspan="2">
						<?php echo esc_html($this->get_configuration_value('section_title')) ?>
					</td>
				</tr>
			</thead>

			<?php $settings_fields->output_fields($this->get_id()); ?>
		</table>
<?php
	}

	/**
	 * Get actions button of component heading
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_component_heading_actions() {
		return null;
	}

	/**
	 * Output feature heading row
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_heading_row($title) {
		$action_contents = apply_filters(Utils::get_component_heading_actions_hook($this->get_id()), $this->get_component_heading_actions());
		Utils::output_component_heading_row($title, $action_contents);
	}
}
