<?php

namespace ShipQora_WooCommerce;

use ShipQora_WooCommerce\Feature;
use ShipQora_WooCommerce\Form_Control;
use ShipQora_WooCommerce\Settings_Fields;
use ShipQora_WooCommerce\ShipQora_Rule;
use ShipQora_WooCommerce\Utils;

if (!defined('ABSPATH')) {
	exit;
}

final class Global_Settings_Fields {
	/**
	 * Output setting field of notice
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function notice_setting_field(Form_Control $form_control) {
		$notice_content = $form_control->get_option('notice_content');

		$line_button_data = array();
		if (!empty($notice_content['utm_source'])) {
			$line_button_data['utm_source'] = $notice_content['utm_source'];
		}

		$form_control->output_row(); ?>
		<td colspan="2">
			<div class="shipqora-woocommerce-notice-box">
				<?php
				if (!empty($notice_content['title'])) {
					echo '<h3>' . esc_html($notice_content['title']) . '</h3>';
				}

				if (!empty($notice_content['description'])) {
					echo '<div class="description">' . wp_kses_post($notice_content['description']) . '</div>';
				} ?>
				<div class="gap-10"></div>
				<?php Utils::get_lite_button($line_button_data) ?>
			</div>
		</td>
	<?php
		$form_control->output_row('close');
	}

	/**
	 * Output setting field of condition group
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public static function condition_group_setting_field(Form_Control $form_control) {
		$model_key = $form_control->get_model_key();

		$add_group_method = $form_control->get_extra_setting('add_group_method');
		if (empty($add_group_method)) {
			$add_group_method = 'add_condition_group()';
		}

		$delete_group_method = $form_control->get_extra_setting('delete_group_method');
		if (empty($delete_group_method)) {
			$delete_group_method = 'delete_condition_group(index)';
		}

		$form_control->output_row(); ?>
		<td class="no-padding" colspan="2">
			<div class="shipqora-woocommerce-repeater shipqora-woocommerce-repeater-condition-groups" v-if="<?php echo esc_attr($model_key); ?>?.length > 0">
				<template v-for="(group, index) in <?php echo esc_attr($model_key); ?>" :key="group?.id">
					<div class="repeater-item repeater-item-separator" v-if="index > 0" data-text="<?php esc_attr_e('or', 'shipqora-woocommerce') ?>"></div>
					<div class="repeater-item">
						<condition-group
							:group="group"
							@delete="<?php echo esc_attr($delete_group_method) ?>"
							@update="(group_data) => <?php echo esc_attr($model_key); ?>[index] = group_data">
						</condition-group>
					</div>
				</template>
			</div>

			<button class="button button-large-dashed button-full-width" @click.prevent="<?php echo esc_attr($add_group_method) ?>">
				<?php esc_html_e('+ Add Condition Group', 'shipqora-woocommerce') ?>
			</button>
		</td>
	<?php
		$form_control->output_row('close');
	}
}
