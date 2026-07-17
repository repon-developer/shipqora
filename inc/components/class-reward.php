<?php

namespace ShipFlex\Component;

use ShipFlex\Utils;
use ShipFlex\Rewards;
use ShipFlex\Cart_Total;
use ShipFlex\Session_Reward;
use ShipFlex\Settings_Fields;
use ShipFlex\Reward as Reward_Model;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Reward Component class
 */
final class Reward {

	/**
	 * Get component models
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_models($reward_type) {
		return Settings_Fields::get_instance('reward_line_' . $reward_type)->get_models();
	}

	/**
	 * Output VueJS component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function output_component($reward_type) {
		$reward_type_configuration = Reward_Model::get_rewards_configuration();
		$settings_fields = Settings_Fields::get_instance('reward_line_' . $reward_type); ?>

		<template id="shipflex-reward-line-<?php echo esc_attr($reward_type)  ?>-component">
			<tr class="row-group-heading">
				<td colspan="2">
					<div class="heading-line">
						<?php echo esc_html($reward_type_configuration[$reward_type]['line_item_title']) ?> #{{lineNumber}}
						<?php do_action('shipflex/reward_component/line_heading_actions', $reward_type) ?>
					</div>
				</td>
			</tr>

			<?php $settings_fields->output_fields('general') ?>
		</template>
<?php
	}

	/**
	 * Hold current reward type
	 * 
	 * @var string
	 */
	private $reward_type = null;

	/**
	 * Hold current context
	 * 
	 * @var string
	 */
	private $context = 'cart';

	/**
	 * Hold product id of current context
	 * 
	 * @var integer
	 */
	public $product_id = 0;

	/**
	 * Hold the variation id
	 * 
	 * @var integer
	 */
	public $variation_id = 0;

	/**
	 * Hold cart object
	 * 
	 * @var Cart_Total
	 */
	private $cart_total = null;

	/**
	 * Cart setting object of required amount
	 * 
	 * @var Cart_Products
	 */
	public $cart_products = null;

	/**
	 * Hold type of minimum required value
	 * 
	 * @var string
	 */
	private $minimum_value_type = 'subtotal';

	/**
	 * Hold require amount for incentive
	 * 
	 * @var float
	 */
	private $minimum_required = 0.00;

	/**
	 * Require coupon or not
	 * 
	 * @var Boolean
	 */
	private $require_coupon = false;

	/**
	 * Hold incentive calculation type
	 * 
	 * @var boolean
	 */
	public $incentive_amount_type = 'fixed';

	/**
	 * Hold incentive amount of current reward
	 * 
	 * @var float
	 */
	private $incentive_amount = 0.00;

	/**
	 * Hold the free product of free-product reward
	 * 
	 * @var array
	 */
	private $free_product_item = array();

	/**
	 * Hold if increase by multiple tier
	 * 
	 * @var boolean
	 */
	private $increase_by_multiple_tier = false;

	/**
	 * Hold all extra values
	 * 
	 * @var array
	 */
	private $extra_values = array();

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 * @param array $settings
	 */
	public function __construct($options, $reward_type) {
		$this->cart_total = new Cart_Total();
		$this->reward_type = $reward_type;

		$session_reward = Session_Reward::get_instance();
		$this->context = $session_reward->get_context();
		$this->product_id = $session_reward->get_product_id();

		$options = wp_parse_args($options, array(
			'incentive_amount' => 0,
			'free_product_item' => array(),
			'cart_products' => array(),
			'incentive_amount_type' => 'fixed',
		));

		$disallow_keys = array('cart_products', 'incentive_amount');
		foreach ($options as $key => $value) {
			if (is_string($key) && strlen($key) > 1 && !in_array($key, $disallow_keys)) {
				$this->{$key} = $value;
			}
		}

		if ('quantity' == $this->minimum_value_type) {
			$this->minimum_required = intval($this->minimum_required);
		} else {
			$this->minimum_required = floatval($this->minimum_required);
		}

		$this->incentive_amount = floatval($options['incentive_amount']);
		$this->cart_products = new Cart_Products($options['cart_products']);

		if (!is_array($this->free_product_item)) {
			$this->free_product_item = array();
		}

		if ('product' == $this->context) {
			$this->cart_total->set_product($this->product_id, $this->variation_id);
		}

		if ('free-product' == $this->reward_type) {
			$this->free_product_identifier = wp_generate_uuid4();
		}

		do_action('shipflex/reward_component/object', $this, $options);
	}

	/**
	 * Set magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value) {
		$this->extra_values[$key] = $value;
	}

	/**
	 * Get magic method
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key) {
		return isset($this->extra_values[$key]) ? $this->extra_values[$key] : null;
	}

	/**
	 * Isset magic method to check if $key exists in $extra_values property
	 * 
	 * @since 1.0.0
	 * @param string $key
	 * @return mixed
	 */
	public function __isset($key) {
		return isset($this->extra_values[$key]);
	}

	/**
	 * Set variation ID
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function set_variation_id($variation_id) {
		$this->variation_id = $variation_id;
		$this->cart_total = new Cart_Total();
		$this->cart_total->set_product($this->product_id, $variation_id);
	}

	/**
	 * Get current product instance
	 * 
	 * @since 1.0.0
	 * @return WC_Product
	 */
	public function get_product($variation_id = 0) {
		$product_id = $this->product_id;
		if ($variation_id > 0) {
			$product_id = $variation_id;
		}

		$_product = wc_get_product($product_id);
		if (is_a($_product, 'WC_Product')) {
			return $_product;
		}

		return false;
	}

	/**
	 * Check if current product is eligible for reward
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_eligible_product($variation_id = 0) {
		$eligible = $this->cart_products->is_eligible_product($this->product_id, $variation_id);
		if (false === $eligible) {
			return false;
		}

		return apply_filters('shipflex/reward_component/is_eligible_product', $eligible, $variation_id, $this);
	}

	/**
	 * Check if current product is variable
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_has_child() {
		$_product = $this->get_product();
		return is_a($_product, 'WC_Product_Variable') && $_product->is_type('variable');
	}

	/**
	 * Get tier quantity
	 * 
	 * @since 1.0.0
	 * @return integer
	 */
	public function get_tier_quantity($minimum_type_total) {
		$tier_quantity = 1;
		if ($minimum_type_total > 0 && $this->minimum_required > 0) {
			$tier_quantity = floor($minimum_type_total / $this->minimum_required);
		}

		if ($tier_quantity <= 0) {
			$tier_quantity = 1;
		}

		return $tier_quantity;
	}

	/**
	 * Has validate free product or not
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function has_validate_free_product() {
		if (isset($this->free_product_item['product_id'])) {
			$_product = wc_get_product($this->free_product_item['product_id']);
			if (is_a($_product, 'WC_Product_Variable') && $_product->is_type('variable')) {
				$_product = wc_get_product($this->free_product_item['variation_id']);
			}

			return is_a($_product, 'WC_Product');
		}

		return false;
	}

	/**
	 * Validate coupon code for reward
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function validate_coupon() {
		if (!method_exists(WC()->cart, 'get_applied_coupons')) {
			return false;
		}

		$applied_coupons = WC()->cart->get_applied_coupons();
		if (empty($applied_coupons)) {
			return false;
		}

		$coupon_code = get_post_field('post_title', $this->require_coupon_id);
		return in_array($coupon_code, $applied_coupons);
	}

	/**
	 * Get line item data of this reward
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function get_line_item_data() {
		$supported_amount_type = array('percentage', 'fixed');
		if (in_array($this->incentive_amount_type, $supported_amount_type) && $this->incentive_amount <= 0 && Utils::is_incentive_reward_type($this->reward_type)) {
			return false;
		}

		if ('product' == $this->context && ($this->hide_badge || !$this->is_eligible_product($this->variation_id))) {
			return false;
		}

		if ('cart' == $this->context && $this->require_coupon && (empty($this->require_coupon_id) || !$this->validate_coupon())) {
			return false;
		}

		if ('free-product' == $this->reward_type && !$this->has_validate_free_product()) {
			return false;
		}

		$line_item_data = array(
			'context' => $this->context,
			'line_id' => wp_generate_uuid4(),
			'product_id' => $this->product_id,
			'variation_id' => $this->variation_id,
			'variation_product' => $this->is_has_child(),

			'tier_quantity' => 1,
			'minimum_cart_total' => 0,
			'minimum_required' => $this->minimum_required,
			'minimum_value_type' => $this->minimum_value_type,
			'minimum_required_in_price' => $this->minimum_required,

			'hide_badge' => $this->hide_badge,
			'badge_icon' => $this->badge_icon,
			'badge_offer_text' => $this->badge_offer_text,
			'badge_primary_text' => $this->badge_primary_text,
			'badge_additional_text' => $this->badge_additional_text,
			'badge_texts_separator' => $this->badge_texts_separator,
		);

		if ('cart' == $this->context) {
			$this->cart_total->set_cart_items_keys($this->cart_products->get_matched_cart_items_keys());
		}

		if ('product' == $this->context) {
			$_product = $this->get_product($this->variation_id);
			if ($_product) {
				$product_price = $_product->get_price();
				$cart_item_key = $this->product_id . '_' . $this->variation_id;

				$cart_item_data = array(
					'quantity' => 1,
					'line_tax' => 0.00,
					'key' => $cart_item_key,
					'is_cart_item' => false,
					'line_subtotal_tax' => 0.00,
					'line_total' => $product_price,
					'line_subtotal' => $product_price,
					'product_id' => $this->product_id,
					'variation_id' => $this->variation_id,
					'data' => $_product
				);

				$this->cart_total->add_cart_item($cart_item_key, $cart_item_data);
				$this->cart_total->set_cart_items_keys(array($cart_item_key));

				$convert_required_price_type = array('quantity', 'weight', 'volume');

				if (in_array($this->minimum_value_type, $convert_required_price_type)) {
					$require_quantity = 100000;
					if ('quantity' == $this->minimum_value_type) {
						$require_quantity = $this->minimum_required;
					}

					if ('weight' == $this->minimum_value_type || 'volume' == $this->minimum_value_type) {
						$cart_type_total = $this->cart_total->get_total($this->minimum_value_type);
						if ($cart_type_total > 0) {
							$require_quantity = ceil($this->minimum_required / $cart_type_total);
						}
					}

					$line_item_data['minimum_required_in_price'] = $cart_item_data['line_subtotal'] * $require_quantity;
				}
			}
		}

		$cart_items = $this->cart_total->get_filtered_cart_items();
		if (count($cart_items) == 0) {
			return false;
		}

		$line_item_data['minimum_value_key'] = md5($line_item_data['minimum_required_in_price']);

		array_walk($cart_items, function (&$cart_item, $cart_item_key) {
			$cart_item_total = new Cart_Total();
			$cart_item_total->set_product($cart_item['product_id'], $cart_item['variation_id']);
			$cart_item['minimum_cart_total'] = $cart_item_total->get_total();

			$cart_total = clone $this->cart_total;
			$cart_total->add_cart_item($cart_item_key, $cart_item);
			$cart_total->set_cart_items_keys(array($cart_item_key));

			$cart_item['cart_total'] = $cart_total;
			$cart_item['minimum_type_total'] = $cart_total->get_total($this->minimum_value_type);

			$cart_item['tier_quantity'] = 1;
			$cart_item['tier_quantity'] = $this->get_tier_quantity($cart_item['minimum_type_total']);

			//Set data for order discount
			$cart_item['calculated_incentive'] = 0;
			$cart_item['incentive_amount'] = $this->incentive_amount;
			$cart_item['incentive_amount_type'] = $this->incentive_amount_type;
			$cart_item['increase_by_multiple_tier'] = $this->increase_by_multiple_tier;

			$cart_item = apply_filters('shipflex/reward_component/cart_item', $cart_item, $this, $cart_item_key);
		});

		$line_item_data['minimum_cart_total'] = array_sum(wp_list_pluck($cart_items, 'minimum_cart_total'));

		if (Utils::is_incentive_reward_type($this->reward_type)) {
			$line_item_data = array_merge($line_item_data, array(
				'calculated_incentive' => 0.00,
				'incentive_amount' => $this->incentive_amount,
				'incentive_amount_type' => $this->incentive_amount_type,
				'increase_by_multiple_tier' => $this->increase_by_multiple_tier,
				'cart_items' => array()
			));

			array_walk($cart_items, function (&$cart_item, $cart_item_key) use (&$line_item_data) {
				if ($cart_item['incentive_amount'] <= 0 || $cart_item['minimum_type_total'] < $this->minimum_required) {
					return;
				}

				$cart_item['calculated_incentive'] = $cart_item['incentive_amount'];
				if ($cart_item['increase_by_multiple_tier'] && 'fixed' == $cart_item['incentive_amount_type']) {
					$cart_item['calculated_incentive'] = ($cart_item['incentive_amount'] * $cart_item['tier_quantity']);
				}

				if ('percentage' == $cart_item['incentive_amount_type']) {
					$cart_item_total = $this->minimum_required;
					if ('cart' == $this->context) {
						$cart_item_total = $cart_item['cart_total']->get_total();
					}

					$cart_item['calculated_incentive'] = ($cart_item_total * $cart_item['incentive_amount']) / 100;
				}

				$cart_item_subtotal = Cart_Total::get_cart_item_subtotal($cart_item);
				if ($cart_item['calculated_incentive'] > $cart_item_subtotal) {
					$cart_item['calculated_incentive'] = $cart_item_subtotal;
				}

				if ($cart_item['calculated_incentive'] > 0) {
					$line_item_data['cart_items'][$cart_item_key] = $cart_item['calculated_incentive'];
				}

				unset($cart_item['data'], $cart_item['cart_total'], $line_item_data['tier_quantity']);
			});

			$line_item_data['calculated_incentive'] = array_sum(wp_list_pluck($cart_items, 'calculated_incentive'));

			if ('product' == $this->context && count($cart_items) > 0) {
				$first_cart_item = reset($cart_items);
				$line_item_data['tier_quantity'] = $first_cart_item['tier_quantity'];
				$line_item_data['incentive_amount'] = $first_cart_item['incentive_amount'];
				$line_item_data['incentive_amount_type'] = $first_cart_item['incentive_amount_type'];
				$line_item_data['increase_by_multiple_tier'] = $first_cart_item['increase_by_multiple_tier'];
				unset($line_item_data['cart_items']);
			}
		}

		if ('free-shipping' == $this->reward_type) {
			$line_item_data['free_shipping_eligible'] = false;
			foreach ($cart_items as $cart_item_key => $cart_item) {
				if ($cart_item['minimum_type_total'] >= $this->minimum_required) {
					$line_item_data['free_shipping_eligible'] = true;
					break;
				}
			}

			if ('cart' == $this->context &&  false == $line_item_data['free_shipping_eligible']) {
				return false;
			}
		}

		if ('free-product' == $this->reward_type) {
			$free_product = array(
				'variation_id' => 0,
				'line_id' => $line_item_data['line_id'],
				'quantity' => $this->free_product_quantity,
				'multiply_by_tier' => $this->multiply_by_tier,
				'identifier' => $this->free_product_identifier,
				'product_id' => $this->free_product_item['product_id'],
				'stop_combine_quantity' => $this->stop_combine_quantity,
				'increase_by_cart_item' => $this->increase_by_cart_item,
			);

			if (!empty($this->free_product_item['variation_id'])) {
				$free_product['variation_id'] = $this->free_product_item['variation_id'];
			}

			$free_product['quantity'] = absint($free_product['quantity']);
			if ($free_product['quantity'] <= 0) {
				$free_product['quantity'] = 1;
			}

			$free_product['total_quantity'] = $free_product['quantity'];

			foreach ($cart_items as $cart_item_key => $cart_item) {
				if ($cart_item['minimum_type_total'] >= $this->minimum_required) {
					$free_product['tier_quantity'] = $cart_item['tier_quantity'];
					if ($free_product['multiply_by_tier']) {
						$free_product['total_quantity'] = $free_product['quantity'] * $cart_item['tier_quantity'];
					}

					if (false === $free_product['stop_combine_quantity'] && true === $free_product['increase_by_cart_item']) {
						$free_product['identifier'] = wp_generate_uuid4();
					}

					$free_product['line_item_product'] = $cart_item['product_id'] . '_' . $cart_item['variation_id'];
					$line_item_data['free_products'][$cart_item_key] = $free_product;
				}
			}

			if (!isset($line_item_data['free_products']) || count($line_item_data['free_products']) == 0) {
				return false;
			}
		}

		return $line_item_data;
	}
}
