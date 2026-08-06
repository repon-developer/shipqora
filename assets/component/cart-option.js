const { __ } = wp.i18n;

const Cart_Option = {
	template: '#shipflex-cart-option-component',
	props: {
		cartOptionData: {
			required: true
		},

		optionLabel: {
			type: String,
			default: __('of the cart items in the selected {{option_label_lower}}', 'shipflex')
		},

		basedOn: {
			default: '',
			type: String,
		},

		hideOperator: {
			default: false,
			type: Boolean,
		}
	},

	emits: ['on-update'],

	data() {
		return {
			based_on: this.basedOn,
			operator: 'any_in_list',
			...shipflex_admin.cart_option_models,
			...this.cartOptionData,
		}
	},

	computed: {
		options() {
			return shipflex_admin.cart_options;
		},

		cart_option_data() {
			const cart_option_data = JSON.parse(JSON.stringify(this.$data));
			delete cart_option_data.loading;
			return cart_option_data;
		},

		hide_operator() {
			if (!this.based_on?.length || this.based_on == 'of_the_cart' || this.hideOperator) {
				return true;
			}

			const option_settings = this.options?.[this.based_on];
			if (true === option_settings?.hide_operator) {
				return true;
			}

			return false;
		}
	},

	watch: {
		cart_option_data(cart_option_data) {
			this.$emit('on-update', cart_option_data)
		},
	},

	methods: {
		get_value(model_key) {
			return this?.[model_key];
		},

		set_value(value, model_key) {
			this[model_key] = value;
		},

		get_option_label(option_key) {
			const option_item = this.options?.[option_key];
			let option_text = this.optionLabel.toString();

			option_text = option_text.replace('{{option_label}}', option_item?.label);
			option_text = option_text.replace('{{option_label_lower}}', option_item?.label_lower);

			return wp.hooks.applyFilters('shipflex.cart_option.option_label', option_text, option_item);
		},

		handle_cart_option_click() {
			const modal_name = 'cart-option-advanced';
			if (this.$utils.has_key() || this.$root.once_modals?.includes(modal_name)) {
				return;
			}

			this.$root.current_modal = modal_name;
			this.$root.once_modals.push(modal_name);
			jQuery(this.$refs.cart_option_dropdown).blur();
		}
	}
}

export default Cart_Option;