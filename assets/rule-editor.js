import Utils from './utils.min.js?v=@@VERSION';
import Condition_Group from './component/condition.min.js?v=@@VERSION';
import Input_Product from './component/input-product.min.js?v=@@VERSION';
import Select2_Dropdown from './component/select2-dropdown.min.js?v=@@VERSION';
import Cart_Products_Input from './component/cart-products-input.min.js?v=@@VERSION';

const $ = jQuery;
const { __ } = wp.i18n;

const helper_models = {
	saving: false,
	loading: true,
	toast_message: null,
	current_modal: null,
	show_toast_message: false,
	toast_message_type: 'error',
}

const ShipFlex_Rule_Editor = {
	components: {
		'condition-group': Condition_Group
	},

	data() {
		return {
			id: 0,
			title: '',
			status: 'development',

			...helper_models,
			...shipflex_admin.rule_models
		}
	},

	created() {
		Utils.app = this;

		if (!Array.isArray(this.active_features)) {
			this.active_features = []
		}

		if (!Array.isArray(this.shipping_instances)) {
			this.shipping_instances = []
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

		add_shipping_instance() {
			this.shipping_instances.push(0);
		},

		remove_shipping_instance(index_no) {
			this.shipping_instances?.splice(index_no, 1);
		},

		update_shipping_instance(instance_id, index_no) {
			this.shipping_instances[index_no] = instance_id || 0;
		},

		add_collection(model_keys, value_type) {
			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				collections = Array()
			}

			let default_value = {}
			if ('condition_group' == value_type) {
				default_value = { id: Utils.generate_uuid() }
			}

			collections.push({ ...default_value })
		},

		delete_collection(model_keys, index_no) {
			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
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

		save_reward() {
			if (!this.title?.length) {
				return Utils.set_toast_message(__('Please enter reward title.', 'shipflex'));
			}

			if (this.title?.length > 200) {
				return Utils.set_toast_message(__('The reward title must be within 200 characters.', 'shipflex'));
			}

			if (!this.validate_save_data()) {
				return;
			}

			this.saving = true;

			const reward_data = JSON.parse(JSON.stringify(this.$data));
			delete reward_data.id;
			for (const key in helper_models) {
				delete reward_data[key];
			}

			const formData = new FormData();
			formData.append('id', this.id);
			formData.append('lang', shipflex_admin.current_lang);
			formData.append('nonce', shipflex_admin.save_reward_rule_nonce);
			formData.append('action', 'shipflex/save_reward_rule');
			formData.append('reward-data', new URLSearchParams({ data: JSON.stringify(reward_data) }))

			fetch(shipflex_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong while saving the reward data.', 'shipflex'));
				}

				if (false === result.success) {
					if (result?.data?.modal) {
						return this.modal = result.data.modal;
					}

					throw new Error(result?.data?.message);
				}

				this.id = result.data.id;
				Utils.set_toast_message(__('Successfully saved reward.', 'shipflex'), 'success');

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
		'input-product': Input_Product,
		'select2-dropdown': Select2_Dropdown,
		'cart-product-input': Cart_Products_Input,
	});

	for (const key in components) {
		ShipFlex_Rule_Editor_App.component(key, components[key]);
	}

	ShipFlex_Rule_Editor_App.mount('.shipflex-rule-editor');
}