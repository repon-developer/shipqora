import Utils from './utils.min.js?v=@@VERSION';
import Input_Product from './modules/input-product.min.js?v=@@VERSION';
import Select2_Dropdown from './modules/select2-dropdown.min.js?v=@@VERSION';
import Cart_Products_Input from './modules/cart-products-input.min.js?v=@@VERSION';


const $ = jQuery;
const { __ } = wp.i18n;

const Condition = {
	template: '#shipflex-condition',

	props: {
		condition: {
			type: Object,
			required: true
		},

		number: {
			type: Number,
			required: true
		}
	},

	data() {
		return {
			value: '',
			value2: '',
			type: 'cart:subtotal',
			id: Utils.generate_uuid(),
			user_operator: 'any_in_list',
			cart_operator: 'greater_than',
			cart_products_operator: 'any_in_list',
			billing_shipping_operator: 'any_in_list',
			...shipflex_admin.condition_models,
			...this.condition
		}
	},

	computed: {
		condition_data() {
			return JSON.stringify(this.$data);
		},

		get_states() {
			return wcSettings.countryStates;
		},

		cart_products_prefix() {
			const cart_prefixes = {
				'cart:subtotal': __('Subtotal of', 'shipflex'),
				'cart:total_quantity': __('Total quantity of', 'shipflex'),
				'cart:total_weight': __('Total weight of', 'shipflex'),
				'cart:total_volume': __('Total volume of', 'shipflex'),
			}

			return cart_prefixes?.[this.type] ? cart_prefixes?.[this.type] : '';
		}
	},

	created() {
		this.$parent.conditions[this.number] = JSON.parse(this.condition_data)
	},

	watch: {
		condition_data(data) {
			this.$parent.conditions[this.number] = JSON.parse(data)
		}
	},

	methods: {
		...wp.hooks.applyFilters('shipflex.condition.methods', {}),

		set_value(value, model_key) {
			this[model_key] = value;
		},

		delete_condition() {
			const response = confirm(__('Do you want to delete this condition?', 'shipflex'))
			if (response) {
				this.$parent.conditions.splice(this.number, 1);
			}
		}
	},
}

const Condition_Group = {
	template: '#shipflex-condition-group',

	components: {
		'condition': Condition
	},

	props: {
		group: {
			type: Object,
			required: true
		},

		number: {
			type: Number,
			required: true
		}

	},

	data() {
		return {
			conditions: [],
			match_type: 'all',
			id: Utils.generate_uuid(),
			...this.group
		}
	},

	computed: {
		group_data() {
			const data = JSON.parse(JSON.stringify(this.$data));
			return JSON.stringify(data)
		}
	},

	watch: {
		group_data() {
			this.$root.condition_groups[this.number] = JSON.parse(this.group_data)
		}
	},

	methods: {
		add_condition() {
			if (!Array.isArray(this.conditions)) {
				this.conditions = Array();
			}

			this.conditions.push({ id: Utils.generate_uuid() })
		},

		delete_group() {
			const response = confirm(__('Do you want to delete this group?', 'shipflex'))
			if (response) {
				this.$root.condition_groups.splice(this.number, 1);
			}
		}
	}
}

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
			settings: {},
			conditions: [],
			status: 'development',
			condition_groups_match: 'any',

			...helper_models,
			...shipflex_admin.rule_models
		}
	},

	created() {
		Utils.app = this;

		if (!Array.isArray(this.conditions)) {
			this.conditions = []
		}

		this.conditions.forEach((group, index) => {
			if (!group?.id) {
				this.conditions[index].id = Utils.generate_uuid()
			}
		})
	},

	computed: {
		...wp.hooks.applyFilters('shipflex.rule_editor.computed', {}),

		get_root_element() {
			return $(this.$el.parentElement);
		}
	},

	watch: {
		...wp.hooks.applyFilters('shipflex.rule_editor.watch', {}),

		status(new_status, prev_status) {
			this.handle_rule_form(new_status, prev_status);
		}
	},

	mounted() {
		const settings_data = this.get_root_element.data('settings');

		console.log(settings_data)
		this.id = settings_data?.id || 0;

		this.handle_rule_form(this.status);

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

	methods: {
		...wp.hooks.applyFilters('shipflex.rule_editor.methods', {}, Utils),

		handle_rule_form(new_status, prev_status) {
			$('body').addClass('shipflex-status-' + new_status);
			$('body').removeClass('shipflex-status-' + prev_status);

			$('#shipflex').addClass('shipflex-rule-' + new_status)
			$('#shipflex').removeClass('shipflex-rule-' + prev_status)
		},

		is_highlight_section(section_name) {
			return this?.highlighted_section?.name == section_name;
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

		add_new_reward_tier(model_key, reward_type = null) {
			if (!Array.isArray(this[model_key])) {
				this[model_key] = Array();
			}

			let default_values = { id: Utils.generate_uuid() };

			const reward_type_line_default_values = shipflex_admin.rewards_configuration?.[reward_type]?.badge_default_values;
			if (typeof reward_type_line_default_values === 'object') {
				default_values = { ...default_values, ...reward_type_line_default_values };
			}

			this[model_key].push(default_values)
		},

		duplicate_reward_tier(model_key, item_no, duplicate_data) {
			const duplicate_item = { ...duplicate_data, id: Utils.generate_uuid() };
			this[model_key].splice(item_no, 0, duplicate_item);
		},

		delete_reward_tier(modal_key, item_no) {
			this[modal_key].splice(item_no, 1);
		},

		add_condition_group() {
			if (!Array.isArray(this.condition_groups)) {
				this.condition_groups = Array();
			}

			this.condition_groups.push({
				conditions: [{}],
				match_type: 'all',
				id: Utils.generate_uuid(),
			})
		},

		set_sidebar(sidebar_id) {
			this.current_sidebar = sidebar_id;
		}
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