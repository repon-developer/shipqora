<?php

namespace ShipQora;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Import class
 */
final class Import {

	/**
	 * Hold the current instance of import class
	 * 
	 * @since 1.0.0
	 * @var Import
	 */
	private static $instance = null;

	/**
	 * Get instance of current class
	 * 
	 * @since 1.0.0
	 * @return Import
	 */
	public static function get_instance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		add_action('wp_ajax_shipqora/import_third_party_plugin', array($this, 'import_data'));
	}

	/**
	 * Check if eligible for import 
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_eligible() {
		$plugins = array('Advanced_Rule_Based_Shipping\Main');

		$eligible = false;
		foreach ($plugins as $main_class) {
			if (class_exists($main_class)) {
				$eligible = true;
				break;
			}
		}

		return $eligible;
	}

	/**
	 * Enqueue script on the supported page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		if (!$this->is_eligible() || strpos(get_current_screen()->id, 'shipqora') === false) {
			return;
		}

		wp_enqueue_style('shipqora-admin');
		wp_enqueue_script('shipqora-import', SHIPQORA_URI . 'assets/import.min.js', ['shipqora-vue'], Utils::get_plugin_version(), true);
		wp_localize_script('shipqora-import', 'shipqora_import', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('shipqora/import_nonce')
		));
	}

	/**
	 * Migrate condition
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function migrate_condition($condition) {
		if (!isset($condition['type'])) {
			$condition['type'] = '';
		}

		$condition = apply_filters(Utils::get_hook_name('migrate-condition'), $condition);

		$not_supported_types = array('cart_products:products', 'shipping:zipcode', 'user:roles');
		if (in_array($condition['type'], $not_supported_types)) {
			return false;
		}

		$product_taxonomies = Utils::get_product_taxonomies();

		$types = array(
			'user:roles' => 'user_roles',
			'user:users' => 'user_users',
			'billing:city' => 'billing_cities',
			'date:weekly_days' => 'weekly_days',
			'billing:state' => 'billing_states',
			'shipping:city' => 'shipping_cities',
			'billing:country' => 'billing_countries',
			'shipping:state' => 'shipping_states',
			'shipping:country' => 'shipping_countries',
			'billing:zipcode' => 'billing_postal_codes',
			'shipping:zipcode' => 'shipping_postal_codes',
			'order_history:first_purchase' => 'first_purchase',
		);

		foreach ($types as $key => $value) {
			$condition['type'] = str_replace($key, $value, $condition['type']);
		}

		$condition['type'] = str_replace('date:', 'datetime_', $condition['type']);
		$condition['type'] = str_replace(':', '_', $condition['type']);

		if (isset($condition['operator'])) {
			$operator_map = array(
				'cart_subtotal' => 'cart_operator',
				'cart_total_quantity' => 'cart_operator',
				'cart_total_weight' => 'cart_operator',
				'cart_total_volume' => 'cart_operator',
				'datetime_time' => 'date_operator',
				'datetime_date' => 'date_operator',
				'weekly_days' => 'weekly_days_operator',
				'billing_cities' => 'billing_shipping_operator',
				'billing_postal_codes' => 'billing_shipping_operator',
				'billing_states' => 'billing_shipping_operator',
				'billing_countries' => 'billing_shipping_operator',
				'shipping_cities' => 'billing_shipping_operator',
				'shipping_countries' => 'billing_shipping_operator',
				'user_users' => 'user_operator',
			);

			foreach ($product_taxonomies as $key => $taxonomy) {
				$operator_map['cart_products_' . $key] = 'cart_products_operator';
			}

			if (isset($operator_map[$condition['type']])) {
				$condition[$operator_map[$condition['type']]] = $condition['operator'];
			}
		}

		$cart_types = array('cart_subtotal', 'cart_total_quantity', 'cart_total_weight', 'cart_total_volume');
		if (in_array($condition['type'], $cart_types)) {
			$based_on = $condition['cart_value_type'] ?? '';
			if ($based_on == 'in_cart') {
				$based_on = '';
			}

			$based_on = str_replace('in_', 'taxonomy:', $based_on);

			$condition['cart_cart_option'] = array(
				'based_on' => $based_on
			);

			foreach ($product_taxonomies as $tax_slug => $taxonomy) {
				$model_key = 'cart_' . $tax_slug;

				if (isset($condition[$model_key]) && is_array($condition[$model_key])) {
					$condition['cart_cart_option'][$taxonomy['model']] = $condition[$model_key];
				}
			}
		}


		$replace_models = array(
			'users' => 'user_users',
			'zipcodes' => 'billing_postal_codes',
			'logged_in' => 'user_logged_in',
		);

		if ($condition['type'] == 'shipping_postal_codes') {
			$replace_models['zipcodes'] = 'shipping_postal_codes';
		}

		foreach ($replace_models as $key => $value) {
			$condition[$value] = $condition[$key];
			unset($condition[$key]);
		}

		return $condition;
	}

	/**
	 * Import rules of advanced rules based shipping plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function import_advanced_rule_based_shipping() {
		$query = new \WP_Query(array(
			'posts_per_page' => -1,
			'post_status' => 'any',
			'post_type' => \Advanced_Rule_Based_Shipping\Shipping_Rule::POST_TYPE,
		));

		$rules = array_map(fn($post) => new \Advanced_Rule_Based_Shipping\Shipping_Rule($post), $query->posts);

		foreach ($rules as $rule_item) {
			$priority_type = $rule_item->get_setting('shipping_rule_priority');

			$feature_object = Feature::get_feature('cart-based-shipping');
			if ('sum_all' == $priority_type) {
				$feature_object = Feature::get_feature('product-based-shipping');
			}

			if (false === $feature_object) {
				continue;
			}

			$layers = $rule_item->get_rules();
			if (count($layers) == 0) {
				continue;
			}

			if ('match_first_rule' == $priority_type) {
				$layers = array_reverse($layers);
			}

			if (empty($priority_type)) {
				uasort($layers, function ($layera, $layerb) {
					if (!isset($layera['conditions']) || !is_array($layera['conditions'])) {
						$layera['conditions'] = array();
					}

					if (!isset($layerb['conditions']) || !is_array($layerb['conditions'])) {
						$layerb['conditions'] = array();
					}

					return count($layera['conditions']) > count($layerb['conditions']) ? 1 : -1;
				});
			}



			//error_log(print_r(end($layers), true));

			$layers = array_map(function ($layer) {
				if (isset($layer['disabled']) && true == $layer['disabled']) {
					return false;
				}

				static $priority = 10;
				if (!isset($layer['conditions']) || !is_array($layer['conditions'])) {
					$layer['conditions'] = array();
				}

				$layer['conditions'] = array_filter(array_map(fn($condition) => $this->migrate_condition($condition), $layer['conditions']));
				//error_log(print_r($layer['conditions'], true));

				$calculate_basis_items = array(
					'based_on_weight' => 'weight',
					'fixed_amount' => 'fixed_amount',
					'based_on_subtotal' => 'subtotal',
					'free_shipping' => 'free_shipping',
					'based_on_quantity' => 'quantity',
				);

				$shipping_cost_type = !empty($layer['shipping_cost_type']) ? $layer['shipping_cost_type'] : 'fixed_amount';

				$calculate_basis = 'fixed_amount';
				if (isset($calculate_basis_items[$shipping_cost_type])) {
					$calculate_basis = $calculate_basis_items[$shipping_cost_type];
				}


				$calculation_value = $layer['shipping_cost'] ?? 0.00;
				if ($calculation_value > 0 && $shipping_cost_type == 'based_on_subtotal' && isset($layer['shipping_cost_operator'])) {
					if ($layer['shipping_cost_operator'] == 'multiply') {
						$calculation_value = $calculation_value * 100;
					}

					if ($layer['shipping_cost_operator'] == 'divide') {
						$calculation_value = round(100 / $calculation_value, 2);
					}
				}

				$based_on = $layer['calculate_in'] ?? '';
				if ($based_on == 'in_cart') {
					$based_on = '';
				}

				$based_on = str_replace('in_', 'taxonomy:', $based_on);

				$target_products = array('based_on' => $based_on, 'operator' => 'any_in_list');

				foreach (Utils::get_product_taxonomies() as $tax_slug => $taxonomy) {
					$model_key = 'calculate_in_' . $taxonomy['model'];
					if (isset($layer[$model_key]) && is_array($layer[$model_key])) {
						$target_products[$taxonomy['model']] = $layer[$model_key];
					}
				}

				$shipqora_data = array(
					'priority' => $priority,
					'calculate_basis' => $calculate_basis,
					'target_products' => $target_products,
					'collapse' => $layer['collapse'] ?? false,
					'calculation_type' => 'per_unit_or_percentage',
					'shipping_method_title' => $layer['title'] ?? '',

					'calculation_value' => $calculation_value,
					'extra_charge' => $layer['shipping_cost_extra_charge'] ?? 0.00,
				);

				$shipqora_data['min_cost'] = $layer['min_shipping_cost'] ?? '';
				$shipqora_data['max_cost'] = $layer['max_shipping_cost'] ?? '';

				if (count($layer['conditions']) > 0) {
					if ('match_all' == $layer['condition_relationship']) {
						$shipqora_data['condition_groups'][] = array(
							'conditions' => $layer['conditions']
						);
					}

					if ('match_any' == $layer['condition_relationship']) {
						foreach ($layer['conditions'] as $condition) {
							$shipqora_data['condition_groups'][]['conditions'][] = $condition;
						}
					}
				}

				$priority = $priority + 10;

				return $shipqora_data;
			}, $layers);

			$layers = array_filter($layers);

			$shipqora_rule = ShipQora_Rule::get(5);
			$shipqora_rule->title = $rule_item->title;
			$shipqora_rule->set_active_features($feature_object->get_id());

			$feature_data = end($layers);
			$shipqora_rule->update_feature_data(
				$feature_data,
				$feature_object->get_model_key('primary_shipping_cost'),
			);

			$shipqora_rule->save();



			$rule_layers = apply_filters(
				Utils::get_hook_name('advanced-rule-based-shipping', 'rule-layers'),
				$layers,
				$feature_object,
				$rule_item,
			);








			$shipping_methods = $rule_item->get_attached_shipping_rates();
			//error_log(print_r($shipping_methods, true));
		}
	}

	/**
	 * Import data from third party plugin
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function import_data() {
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'shipqora/import_nonce')) {
			wp_send_json_error(array('message' => esc_html__('Security missing.', 'shipqora')));
		}

		if (!current_user_can('manage_woocommerce')) {
			wp_send_json_error(array('message' => esc_html__('You do not have permission to save data.', 'shipqora')));
		}

		if (!isset($_POST['plugin'])) {
			wp_send_json_error(array('message' => esc_html__('We have failed to detect the plugin data you want to import', 'shipqora')));
		}

		$plugin_id = sanitize_text_field(wp_unslash($_POST['plugin']));
		if ('advanced-rule-based-shipping' == $plugin_id) {
			$this->import_advanced_rule_based_shipping();
		}

		wp_send_json_success();
	}



	/**
	 * Output import section
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output() {
		if (!$this->is_eligible()) {
			return;
		} ?>
		<div id="shipqora-import">
			<?php if (class_exists('Advanced_Rule_Based_Shipping\Main')): ?>
				<div class="plugin-import-box">
					<h3>Import Your Data from Advanced Rule-Based Shipping</h3>
					<p>It looks like you currently have <strong>Advanced Rule-Based Shipping</strong> installed. <strong>ShipQora</strong> serves as a modern alternative to <strong>Advanced Rule-Based Shipping</strong>, offering all of its core capabilities along with expanded features to handle your store's shipping needs.</p>
					<p>To prevent rule conflicts and maintain optimal performance, we highly recommend using <strong>ShipQora</strong> exclusively. Click the button below to seamlessly import your saved data and rules into ShipQora before disabling the older plugin.</p>
					<div class="import-footer">
						<a class="button" href="#" @click.prevent="import_plugin_data('advanced-rule-based-shipping')">Import Rules</a>
					</div>
				</div>
			<?php endif; ?>

		</div>
<?php
	}
}


Import::get_instance();
