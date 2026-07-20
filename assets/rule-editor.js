import { Utils } from './global-module.min.js?v=@@VERSION';
import Cart_Option from './component/cart-option.min.js?v=@@VERSION';
import Condition_Group from './component/condition.min.js?v=@@VERSION';
import Input_Product from './component/input-product.min.js?v=@@VERSION';
import Select2_Dropdown from './component/select2-dropdown.min.js?v=@@VERSION';
import Shipping_Method_Input from './component/shipping-method-input.min.js?v=@@VERSION';

import Shipping_Cost_Adjustment from './features/shipping-cost-adjustment.min.js?v=@@VERSION';
import Hide_Other_Shipping_Methods from './features/hide-other-shipping-methods.min.js?v=@@VERSION';

const $ = jQuery;
const { __ } = wp.i18n;

const helper_models = {
	saving: false,
	loading: true,
	once_modals: [],
	toast_message: null,
	current_modal: null,
	show_toast_message: false,
	toast_message_type: 'error',
}

const ShipFlex_Rule_Editor = {
	components: {
		'feature-shipping-cost-adjustment': Shipping_Cost_Adjustment,
		'feature-hide-other-shipping-methods': Hide_Other_Shipping_Methods,
	},

	data() {
		return {
			id: 0,
			title: '',
			status: 'development',

			...helper_models,
			...shipflex_admin.rule_data
		}
	},

	created() {
		Utils.app = this;

		if (!Array.isArray(this.active_features)) {
			this.active_features = []
		}

		if (!Array.isArray(this.shipping_methods)) {
			this.shipping_methods = []
		}
	},

	computed: {
		...wp.hooks.applyFilters('shipflex.rule_editor.computed', {}),

		get_root_element() {
			return $(this.$el.parentElement);
		}
	},

	watch: {
		...wp.hooks.applyFilters('shipflex.rule_editor.watch', {}),
	},

	mounted() {
		const self = this;
		$(document).keyup(function (e) {
			if (e.key === "Escape") {
				self.modal = null;
				self.current_sidebar = null;
			}
		});

		$('body').on('click', '#shipflex .shipflex-modal', function (e) {
			if ($(e.target).closest('.modal-content').length) {
				return;
			}

			self.modal = null;
		})

		this.loading = false;
	},

	updated() {
		console.log(this.$data);
	},

	methods: {
		...wp.hooks.applyFilters('shipflex.rule_editor.methods', {}, Utils),

		add_collection(model_keys, default_value = {}) {
			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				collections = []
			}

			collections?.push(default_value)
		},

		delete_collection(model_keys, index_no) {
			const collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				return;
			}

			collections.splice(index_no, 1)
		},

		validate_save_data() {
			if ('after' == this.date_validity && !this?.start_date) {
				this.highlighted_section.name = 'date-validity';
				return false;
			}

			if ('before' == this.date_validity && !this?.end_date) {
				this.highlighted_section.name = 'date-validity';
				return false;
			}

			if ('between' == this.date_validity && (!this?.start_date || !this?.end_date)) {
				this.highlighted_section.name = 'date-validity';
				return false;
			}

			return true;
		},

		save_rule() {
			if (!this.title?.length) {
				return Utils.set_toast_message(__('Please enter rule title.', 'shipflex'));
			}

			if (this.title?.length > 200) {
				return Utils.set_toast_message(__('The rule title must be within 200 characters.', 'shipflex'));
			}

			if (!this.validate_save_data()) {
				//return;
			}

			this.saving = true;

			const rule_data = JSON.parse(JSON.stringify(this.$data));
			delete rule_data.id;
			for (const key in helper_models) {
				delete rule_data[key];
			}

			const formData = new FormData();
			formData.append('id', this.id);
			formData.append('nonce', shipflex_admin.save_rule_nonce);
			formData.append('action', 'shipflex/save_rule');
			formData.append('data', JSON.stringify(rule_data))

			fetch(shipflex_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong while saving the rule data.', 'shipflex'));
				}

				if (false === result.success) {
					if (result?.data?.modal) {
						return this.modal = result.data.modal;
					}

					throw new Error(result?.data?.message);
				}

				this.id = result.data.id;
				Utils.set_toast_message(__('Successfully saved rule.', 'shipflex'), 'success');

				if (true === result.data.is_new) {
					window.location = result.data.edit_url
				}

			}).catch((e) => Utils.set_toast_message(e.message)).finally(() => {
				this.saving = false;
			})

		},
	}
}

if ($('.shipflex-rule-editor').length) {
	const ShipFlex_Rule_Editor_App = Vue.createApp(ShipFlex_Rule_Editor).use(sortablejs)

	const components = wp.hooks.applyFilters('shipflex.rule_editor_components', {
		'cart-option': Cart_Option,
		'input-product': Input_Product,
		'condition-group': Condition_Group,
		'select2-dropdown': Select2_Dropdown,
		'shipping-method-input': Shipping_Method_Input,
	});



	for (const key in components) {
		ShipFlex_Rule_Editor_App.component(key, components[key]);
	}

	ShipFlex_Rule_Editor_App.mount('.shipflex-rule-editor');
}