<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Form Control class
 */
final class Form_Control {

	/**
	 * Hold field type of text box
	 * 
	 * @var string
	 */
	const TEXTBOX = 'textbox';

	/**
	 * Hold field type of number like
	 * 
	 * @var string
	 */
	const NUMBER = 'number';

	/**
	 * Hold field type of select dropdown
	 * 
	 * @var string
	 */
	const SELECT = 'select_dropdown';

	/**
	 * Hold field type of checkbox (single item)
	 * 
	 * @var string
	 */
	const CHECKBOX_SINGLE = 'checkbox_single';

	/**
	 * Hold field type of multiple option for radio or checkbox
	 * 
	 * @var string
	 */
	const MULTIPLE_OPTIONS = 'multiple_options';

	/**
	 * Hold input type of Shipping Method Groups
	 * 
	 * @var string
	 */
	const SHIPPING_METHODS = 'shipping_method_input';

	/**
	 * Hold control type
	 * 
	 * @var string
	 */
	private $type = '';

	/**
	 * Hold option data
	 * 
	 * @var array
	 */
	private $options = array();

	/**
	 * Hold current field type
	 * 
	 * @var string
	 */
	private $field_id = null;

	/**
	 * Hold all available attributess
	 * 
	 * @var array
	 */
	private $attributes = array();

	/**
	 * Hold all available attributes for row
	 * 
	 * @var array
	 */
	private $row_attributes = array();

	/**
	 * Hold extra settings
	 * 
	 * @var array
	 */
	private $extra_settings = array();

	/**
	 * Constructor.
	 */
	public function __construct($options = '', $field_id = null) {
		$options = wp_parse_args($options, array(
			'type' => '',
			'label' => '',
			'label_note' => '',
			'option_note' => '',
			'conditions' => array(),
			'row_attributes' => array(),
		));

		$options = apply_filters(Utils::get_hook_name('form-control', 'options'), $options, $field_id);

		$this->type = sanitize_key($options['type']);
		unset($options['type']);

		$this->field_id = $field_id;
		$this->options = $options;

		if (isset($options['extra_settings']) && is_array($options['extra_settings'])) {
			$this->extra_settings = $options['extra_settings'];
		}

		$model_key = $this->get_model_key();
		if (!empty($model_key)) {
			$this->attributes['v-model'] = $model_key;
		}

		if (!empty($options['attributes']) && is_array($options['attributes'])) {
			foreach ($options['attributes'] as $attr_key => $attr_value) {
				$this->add_attribute($attr_key, $attr_value);
			}
		}

		if (!empty($options['placeholder'])) {
			$this->add_attribute('placeholder', $options['placeholder']);
		}

		if (is_array($options['row_attributes']) && count($options['row_attributes']) > 0) {
			$this->row_attributes = $options['row_attributes'];
		}

		if (is_array($options['conditions']) && count($options['conditions']) > 0) {
			$conditions = array_map(fn($item) => '(' . $item . ')', $options['conditions']);
			$this->row_attributes['v-if'] = implode(' || ', $conditions);
		}
	}

	/**
	 * Allow VueJS attributes
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function allow_vue_attrs() {
		return array('v-model' => true, 'v-if' => true, ':class' => true);
	}

	/**
	 * Add attribute
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_attribute($key, $value) {
		if (!empty($key) && strlen($value)) {
			if ('class' == $key && array_key_exists('class', $this->attributes)) {
				$value = $this->attributes['class'] . ' ' . $value;
			}

			$this->attributes[$key] = $value;
		}
	}

	/**
	 * Get option value by key
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_option($key) {
		return isset($this->options[$key]) ? $this->options[$key] : null;
	}

	/**
	 * Get value of extra settings key
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get_extra_setting($key) {
		return (isset($this->extra_settings[$key])) ? $this->extra_settings[$key] : null;
	}

	/**
	 * Get model keys
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function get_model_key() {
		if (empty($this->options['model_key'])) {
			return null;
		}

		return $this->options['model_key'];
	}

	/**
	 * Output row attributes
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_row_attributes() {
		$attributes_html = array();
		foreach ($this->row_attributes as $key => $value) {
			$attributes_html[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
		}

		return implode(' ', $attributes_html);
	}

	/**
	 * Output attributes
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_attributes() {
		$attributes_html = array();
		foreach ($this->attributes as $key => $value) {
			$attributes_html[] = sprintf('%s="%s"', esc_attr($key), esc_attr($value));
		}

		return implode(' ', $attributes_html);
	}

	/**
	 * Output tag of row
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_row($type = 'open') {
		if ('open' == $type) {
			echo '<tr ' . wp_kses($this->output_row_attributes(), $this->allow_vue_attrs()) . '>';
		}

		if ('close' == $type) {
			echo '</tr>';
		}
	}

	/**
	 * Output content before input options
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_before_input_options() {
		$this->output_row();

		echo '<th';
		if (!empty($this->options['classes']['label_heading'])) {
			echo ' class="' . esc_attr($this->options['classes']['label_heading']) . '"';
		}
		echo '>';

		$label = $this->get_option('label');
		if (!empty($label)) {
			echo wp_kses_post($label);
		}

		$label_note = $this->get_option('label_note');
		if (!empty($label_note)) {
			echo '<div class="field-note">' . wp_kses_post($label_note) . '</div>';
		}

		if (!empty($this->options['sidebar'])) {
			echo '<a class="sidebar-icon dashicons dashicons-editor-help" @click.prevent="' . esc_attr($this->options['sidebar']) . '" href="/"></a>';
		}

		echo '</th><td>';
	}

	/**
	 * Output content before input options
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_after_input_options() {
		$option_note = $this->get_option('option_note');
		if (!empty($option_note)) {
			$option_note_classes = 'field-note';
			if (!empty($this->options['classes']['option_note'])) {
				$option_note_classes .= ' ' . $this->options['classes']['option_note'];
			}

			echo '<div class="' . esc_attr($option_note_classes) . '">' . wp_kses_post($option_note) . '</div>';
		}

		$option_note_callback = $this->get_option('option_note_callback');
		if ($option_note_callback && is_callable($option_note_callback)) {
			call_user_func($option_note_callback);
		}

		echo '</td>';

		$this->output_row('close');
	}

	/**
	 * Output sub settings fields
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_sub_settings_fields() {
		if (!is_array($this->options['sub_settings_fields'])) {
			return;
		}

		$sub_settings_wrap_table = $this->get_option('sub_settings_wrap_table') !== false;
		if ($sub_settings_wrap_table) {
			echo '<table class="table-shipflex-form">';
		}

		$settings_fields = Utils::priority_rearrange($this->options['sub_settings_fields']);
		foreach ($settings_fields as $field_id => $setting_field) {
			(new Form_Control($setting_field, $field_id))->render();
		}

		if ($sub_settings_wrap_table) {
			echo '</table>';
		}
	}

	/**
	 * Render field type
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function render() {
		if (isset($this->options['callback'])) {
			return call_user_func($this->options['callback'], $this);
		}

		$model_key = $this->get_model_key();
		if (empty($model_key) && !isset($this->options['sub_settings_fields'])) {
			throw new \Exception('Missing required key: "model_key".');
		}

		$this->output_before_input_options();

		if (isset($this->options['sub_settings_fields'])) {
			$this->output_sub_settings_fields();
		} else {
			$this->output_control();
		}

		$this->output_after_input_options();
	}

	/**
	 * Render field type
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_control() {
		if (method_exists($this, $this->type)) {
			call_user_func(array($this, $this->type));
		}
	}

	/**
	 * Textbox control
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function textbox() {
		$this->add_attribute('type', 'text');
		$this->add_attribute('class', 'regular-text-box');
		echo '<input ' . wp_kses($this->output_attributes(), $this->allow_vue_attrs()) . ' />';
	}

	/**
	 * Textbox control
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function number() {
		$this->add_attribute('min', 0);
		$this->add_attribute('type', 'number');
		$this->add_attribute('class', 'textbox-number');
		echo '<input ' . wp_kses($this->output_attributes(), $this->allow_vue_attrs()) . ' />';
	}

	/**
	 * Select dropdown control
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function select_dropdown() {
		$options = $this->get_option('options');
		if (!is_array($options) || count($options) == 0) {
			throw new \Exception('You need to declare options.');
		}

		echo '<select ' . wp_kses($this->output_attributes(), $this->allow_vue_attrs()) . '>';
		foreach ($options as $option_value => $option_label) {
			echo '<option value="' . esc_attr($option_value) . '">' . esc_html($option_label) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Multiple options for radio buttons or checkboxes
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function multiple_options() {
		$options = $this->get_option('options');
		if (!is_array($options) || count($options) == 0) {
			throw new \Exception('You need to declare options.');
		}

		$option_type = $this->get_option('option_type');
		$model_key = $this->get_model_key();
		if (!in_array($option_type, array('radio', 'checkbox'))) {
			throw new \Exception('You need to declare option_type - radio or checkbox.');
		}

		$this->add_attribute('type', $option_type);

		echo '<ul class="multiple-options-list multiple-options-list-' . esc_attr($model_key) . '">';
		foreach ($options as $option_value => $option) {
			$option = wp_parse_args($option, array('label' => '', 'description' => ''));
			if (empty($option['label'])) {
				continue;
			}

			$this->add_attribute('value', esc_attr($option_value));

			echo '<li class="' . esc_attr($model_key . '-' . $option_value) . '">';
			echo '<label>';
			echo '<input ' . wp_kses($this->output_attributes(), $this->allow_vue_attrs()) . '>';
			echo esc_html($option['label']);
			echo '</label>';
			if (!empty($option['description'])) {
				echo '<div class="field-note">' . wp_kses_post($option['description']) . '</div>';
			}
			echo '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Single checkbox control
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function checkbox_single() {
		$this->add_attribute('type', 'checkbox');

		echo '<label>';
		echo '<input ' . wp_kses($this->output_attributes(), $this->allow_vue_attrs()) . '>';
		echo esc_html($this->options['checkbox_label']);
		echo '</label>';
	}

	/**
	 * Output shipping methods group field
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_method_input() {
		$model_key = $this->get_model_key(); ?>
		<shipping-methods-group
			:shipping-methods="<?php echo esc_attr($model_key) ?>"
			@update="(value) => <?php echo esc_attr($model_key) ?> = value">
		</shipping-methods-group>
<?php
	}
}
