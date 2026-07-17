<?php

namespace ShipFlex;

if (!defined('ABSPATH')) {
	exit;
}

use ShipFlex\Utils;

/**
 * Discount_Settings class plugin
 */
final class Discount_Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('shipflex/admin_settings_tabs/order-discount', array($this, 'settings_tabs'));
		add_filter(Settings_Fields::get_hook_name('settings', 'order-discount'), array($this, 'settings_fields'));
	}

	/**
	 * Settings tabs
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function settings_tabs($tabs) {
		$tabs = array_merge($tabs, array(
			'general' => array(
				'priority' => 0,
				'label' => esc_html__('General', 'shipflex'),
			)
		));

		return $tabs;
	}

	/**
	 * Settings fields of free shipping
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function settings_fields($setting_fields) {
		$setting_fields = array_merge($setting_fields, array(
			'order_summary_discount_label' => array(
				'priority' => 10,
				'tab' => 'general',
				'type' => Form_Control::MULTILINGUAL_TEXT,
				'model_key' => 'order_discount.texts.order_summary_discount_label',
				'default_value' => esc_html__('Special Discount', 'shipflex'),
				'label' => esc_html__('Discount Label', 'shipflex'),
				'label_note' => esc_html__('Set the text displayed in the order summary when this discount is applied.', 'shipflex'),
				'option_note' => esc_html__('This label will appear as a discount line and the discount amount will be deducted from the order total.', 'shipflex')
			),

			'product_listing_badge_settings' => array(
				'priority' => 20,
				'type' => 'settings_fields_group',
				'title' => esc_html__('Product Listing Badge Settings', 'shipflex'),
				'sub_settings_fields' => array(
					'badge_customizer' => array(
						'priority' => 10,
						'default_value' => '',
						'model_key' => 'order_discount.product_listing_badge',
						'label' => esc_html__('Badge Template', 'shipflex'),
						'callback' => array(Global_Options::class, 'badge_template_editor'),
						'label_note' => esc_html__('Choose a design template for reward badges displayed on products across your store.', 'shipflex'),
						'option_note' => esc_html__('This setting controls the visual style of badges shown for all reward types. Select a template that best matches your store design.', 'shipflex'),
						'extra_settings' => array(
							'reward-type' => 'order-discount',
							'badge-id' => 'order-discount-product-listing',
							'inner_content_callback' => array(Global_Options::class, 'badge_customizer_inner_content'),
						)
					),

					'badge_position' => array(
						'priority' => 20,
						'options' => Utils::get_product_list_hooks(),
						'classes' => array('option_note' => 'field-note-warning'),
						'default_value' => 'woocommerce_before_shop_loop_item:0',
						'model_key' => 'order_discount.product_listing_badge_position',
						'label' => esc_html__('Badge Position', 'shipflex'),
						'callback' => array(Global_Options::class, 'badge_position'),
						'label_note' => esc_html__('Choose where the order discount badge should appear on the product listing page.', 'shipflex'),
						'option_note' => esc_html__('The badge will be displayed according to your active theme. Since each theme handles WooCommerce product listings differently.', 'shipflex'),
					),
				)
			),

			'single_product_badge_settings' => array(
				'priority' => 30,
				'type' => 'settings_fields_group',
				'title' => esc_html__('Single Product Badge Settings', 'shipflex'),
				'sub_settings_fields' => array(
					'badge_customizer' => array(
						'priority' => 10,
						'default_value' => '',
						'model_key' => 'order_discount.single_product_badge',
						'label' => esc_html__('Badge Template', 'shipflex'),
						'callback' => array(Global_Options::class, 'badge_template_editor'),
						'label_note' => esc_html__('Choose a design template for reward badges displayed on products across your store.', 'shipflex'),
						'option_note' => esc_html__('This setting controls the visual style of badges shown for all reward types. Select a template that best matches your store design.', 'shipflex'),
						'extra_settings' => array(
							'reward-type' => 'order-discount',
							'badge-id' => 'order-discount-single-product',
							'inner_content_callback' => array(Global_Options::class, 'badge_customizer_inner_content'),
						)
					),

					'badge_position' => array(
						'priority' => 20,
						'options' => Utils::get_single_product_hooks(),
						'label' => esc_html__('Badge Position', 'shipflex'),
						'classes' => array('option_note' => 'field-note-warning'),
						'default_value' => 'woocommerce_single_product_summary:31',
						'model_key' => 'order_discount.single_product_badge_position',
						'callback' => array(Global_Options::class, 'badge_position'),
						'label_note' => esc_html__('Choose where the order discount badge should appear on single product page.', 'shipflex'),
						'option_note' => esc_html__('The badge will be displayed according to your active theme. Since each theme handles WooCommerce single product page differently.', 'shipflex'),
					),
				)
			),
		));

		return $setting_fields;
	}
}

new Discount_Settings();
