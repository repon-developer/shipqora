import { Utils } from '../global-module.min.js?v=@@VERSION';

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
		}
	},

	emits: ['update', 'delete'],

	data() {
		return {
			id: Utils.generate_uuid(),
			conditions: [{ id: Utils.generate_uuid() }],
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
			this.$emit('update', JSON.parse(this.group_data));
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
			const response = confirm(__('Do you want to delete this condition group?', 'shipflex'))
			if (response) {
				this.$emit('delete');
			}
		}
	}
}

export default Condition_Group;