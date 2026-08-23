const { __ } = wp.i18n;

const Condition = {
	template: '#shipqora-woocommerce-condition',

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
			type: 'cart_subtotal',
			user_operator: 'any_in_list',
			cart_operator: 'greater_than',
			id: this.$utils.generate_uuid(),
			cart_products_operator: 'any_in_list',
			billing_shipping_operator: 'any_in_list',
			...shipqora_admin.condition_models,
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
				'cart_subtotal': __('Subtotal of', 'shipqora-woocommerce'),
				'cart_total_quantity': __('Total quantity of', 'shipqora-woocommerce'),
				'cart_total_weight': __('Total weight of', 'shipqora-woocommerce'),
				'cart_total_volume': __('Total volume of', 'shipqora-woocommerce'),
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
		...wp.hooks.applyFilters('shipqora.condition.methods', {}),

		set_value(value, model_key) {
			this[model_key] = value;
		},

		delete_condition() {
			const response = confirm(__('Do you want to delete this condition?', 'shipqora-woocommerce'))
			if (response) {
				this.$parent.conditions.splice(this.number, 1);
			}
		},
	},
}

const Condition_Group = {
	template: '#shipqora-woocommerce-condition-group',

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
			id: this.$utils.generate_uuid(),
			conditions: [{ id: this.$utils.generate_uuid() }],
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

			this.conditions.push({ id: this.$utils.generate_uuid() })
		},

		delete_group() {
			const response = confirm(__('Do you want to delete this condition group?', 'shipqora-woocommerce'))
			if (response) {
				this.$emit('delete');
			}
		}
	}
}

export default Condition_Group;