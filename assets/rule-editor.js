import Utils from './utils.min.js?v=@@VERSION';
import Cart_Option from './component/cart-option.min.js?v=@@VERSION';
import Condition_Group from './component/condition.min.js?v=@@VERSION';
import Select2_Dropdown from './component/select2-dropdown.min.js?v=@@VERSION';
import Table_Rates_Shipping from './component/table-rates-shipping.min.js?v=@@VERSION';
import Shipping_Methods_Group from './component/shipping-methods-group.min.js?v=@@VERSION';

import Cart_Based_Shipping from './features/cart-based-shipping.min.js?v=@@VERSION';
import Hide_Payment_Methods from './features/hide-payment-methods.min.js?v=@@VERSION';
import Product_Based_Shipping from './features/product-based-shipping.min.js?v=@@VERSION';
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
	enabling_debugging_mode: false,
	original_status: null,
	is_debugging_enabled: shipqora_admin?.is_debugging_enabled == 'yes'
}

const ShipQora_Rule_Editor = {
	components: {
		'feature-cart-based-shipping': Cart_Based_Shipping,
		'feature-hide-payment-methods': Hide_Payment_Methods,
		'feature-product-based-shipping': Product_Based_Shipping,
		'feature-shipping-cost-adjustment': Shipping_Cost_Adjustment,
		'feature-hide-other-shipping-methods': Hide_Other_Shipping_Methods,
	},

	data() {
		return {
			id: 0,
			title: '',
			status: 'development',

			...helper_models,
			...shipqora_admin.rule_data
		}
	},

	created() {
		this.$utils.app = this;
		if (!Array.isArray(this.active_features)) {
			this.active_features = []
		}

		if (!Array.isArray(this.shipping_methods)) {
			this.shipping_methods = []
		}

		this.original_status = this.status;
	},

	computed: {
		...wp.hooks.applyFilters('shipqora.rule_editor.computed', {}),

		get_root_element() {
			return $(this.$el.parentElement);
		},

		rule_title() {
			return this.title?.length ? ': ' + this.title : '';
		},

		get_current_status_info() {
			return `<span class="shipqora-status shipqora-status-${this.original_status}"></span>` + shipqora_admin.statuses?.[this.original_status].currently_text
		}
	},

	watch: {
		...wp.hooks.applyFilters('shipqora.rule_editor.watch', {}),
	},

	mounted() {
		const self = this;
		$(document).keyup(function (e) {
			if (e.key === "Escape") {
				self.modal = null;
				self.current_sidebar = null;
			}
		});

		$('body').on('click', '#shipqora .shipqora-modal', function (e) {
			if ($(e.target).closest('.modal-content').length) {
				return;
			}

			self.modal = null;
		})

		$('body').on('click', '[data-once-modal]', function () {
			const modal_name = $(this).data('once-modal');
			if (!modal_name) {
				return;
			}

			const allow_once_modal = wp.hooks.applyFilters('shipqora.allow_once_modal', true, modal_name, 'rule-editor');
			if (true !== allow_once_modal || self.once_modals?.includes(modal_name)) {
				return;
			}

			self.current_modal = modal_name;
			self.once_modals.push(modal_name);
			$(this).blur();
		})

		this.loading = false;
	},

	methods: {
		...wp.hooks.applyFilters('shipqora.rule_editor.methods', {}),

		add_collection(model_keys, default_value = {}) {
			if (!model_keys || !model_keys?.length) {
				throw new Error("Please pass 'Model Key' at first argument");
			}

			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				collections = []
			}

			collections?.push(default_value)
		},

		duplicate_collection(model_keys, data, position = 1) {
			if (!model_keys || !model_keys?.length) {
				throw new Error("Please pass 'Model Key' at first argument");
			}

			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				collections = []
			}

			collections.splice(position, 0, data)
		},

		delete_collection(model_keys, index_no) {
			const collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				return;
			}

			collections.splice(index_no, 1)
		},

		on_order_change(event) {
			const source_element = jQuery(event.from);
			const drag_group = source_element.data('group');
			if ('feature' !== drag_group) {
				return;
			}

			const model_keys = source_element.data('model-key');
			if (!model_keys || !model_keys?.length) {
				throw new Error("Please add 'data-model' attribute");
			}

			const skip = parseInt(source_element.data('skip-order')) || 0;

			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			const item = collections.splice(event.oldIndex - skip, 1)[0];
			collections.splice(event.newIndex - skip, 0, item);
		},

		save_rule() {
			if (!this.title?.length) {
				this.$utils.highlight_section('shipqora-rule-title')
				return this.$utils.set_toast_message(__('Please provide a rule title to save your ShipQora rule.', 'shipqora'));
			}

			if (this.title?.length > 200) {
				this.$utils.highlight_section('shipqora-rule-title')
				return this.$utils.set_toast_message(__('The rule title must be within 200 characters.', 'shipqora'));
			}

			const shipping_methods = this.shipping_methods?.filter((item) => item.length > 0)
			if (!shipping_methods?.length) {
				this.$utils.highlight_section('general-shipping-methods');
				return this.$utils.set_toast_message(__('At least one shipping method is required to apply this rule.', 'shipqora'));
			}

			this.saving = true;

			const rule_data = JSON.parse(JSON.stringify(this.$data));
			delete rule_data.id;
			for (const key in helper_models) {
				delete rule_data[key];
			}

			const formData = new FormData();
			formData.append('id', this.id);
			formData.append('nonce', shipqora_admin.save_rule_nonce);
			formData.append('action', 'shipqora/save_rule');
			formData.append('data', JSON.stringify(rule_data))

			fetch(shipqora_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong while saving the rule data.', 'shipqora'));
				}

				if (false === result.success) {
					if (result?.data?.modal) {
						return this.modal = result.data.modal;
					}

					throw new Error(result?.data?.message);
				}

				this.original_status = this.status;

				this.id = result.data.id;
				Utils.set_toast_message(__('Successfully saved rule.', 'shipqora'), 'success');

				if (true === result.data.is_new) {
					window.location = result.data.edit_url
				}

			}).catch((e) => Utils.set_toast_message(e.message)).finally(() => {
				this.saving = false;
			})

		},

		enable_debugging_mode() {
			this.enabling_debugging_mode = true;

			const formData = new FormData();
			formData.append('enable_debugging', !this.is_debugging_enabled);
			formData.append('nonce', shipqora_admin.debugging_nonce);
			formData.append('action', 'shipqora/update_debugging_mode');

			fetch(shipqora_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong while enable debugging mode', 'shipqora'));
				}

				if (false === result.success) {
					throw new Error(__('Something went wrong while enable debugging mode', 'shipqora'));
				}

				this.is_debugging_enabled = !this.is_debugging_enabled;
				//setTimeout(() => this.is_debugging_enabled = true, 1000)

			}).catch((e) => Utils.set_toast_message(e.message)).finally(() => {
				this.enabling_debugging_mode = false;
			})
		}
	}
}

if ($('.shipqora-rule-editor').length) {
	const ShipQora_Rule_Editor_App = Vue.createApp(ShipQora_Rule_Editor).use(sortablejs)

	ShipQora_Rule_Editor_App.config.globalProperties.$utils = Utils;

	const components = wp.hooks.applyFilters('shipqora.rule_editor_components', {
		'cart-option': Cart_Option,
		'condition-group': Condition_Group,
		'select2-dropdown': Select2_Dropdown,
		'table-rates-shipping': Table_Rates_Shipping,
		'shipping-methods-group': Shipping_Methods_Group,
	});

	for (const key in components) {
		ShipQora_Rule_Editor_App.component(key, components[key]);
	}

	ShipQora_Rule_Editor_App.mount('.shipqora-rule-editor');
}