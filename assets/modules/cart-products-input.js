import Utils from '../utils.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Cart_Products_Input = {
	template: '#shipflex-cart-products-input',
	props: {
		settings: {
			required: true
		},

		firstOptionLabel: {
			type: String,
			default: __('of the cart item', 'shipflex')
		},

		optionLabel: {
			type: String,
			default: __('of the cart item in the selected {{option_label}}', 'shipflex')
		},
	},

	emits: ['change'],

	data() {
		return {
			based_on: '',
			operator: 'any_in_list',
			...shipflex_admin.cart_products_models,
			...this.settings
		}
	},

	computed: {
		options() {
			return shipflex_admin.cart_products_options;
		},

		cart_option_data() {
			const cart_option_data = JSON.parse(JSON.stringify(this.$data));
			delete cart_option_data.loading;
			return cart_option_data;
		},

		hide_operator() {
			if (!this.based_on?.length || this.based_on == 'of_the_cart') {
				return true;
			}

			const option_settings = shipflex_admin.cart_products_options?.[this.based_on];
			if (true === option_settings?.hide_operator) {
				return true;
			}

			return false;
		}
	},

	watch: {
		cart_option_data(cart_option_data) {
			this.$emit('change', cart_option_data)
		},
	},

	methods: {
		get_value(model_key) {
			return this?.[model_key];
		},

		set_value(value, model_key) {
			this[model_key] = value;
		},

		is_operator_disabled(key) {
			return this.options?.[this.based_on]?.disabled_operators?.includes(key);
		},

		get_option_label(option_key) {
			const option_item = this.options?.[option_key];
			let option_text = this.optionLabel.toString();
			return option_text.replace('{{option_label}}', option_item?.label_lower);
		},

		handle_cart_option_click() {
			const modal_name = 'cart-product-option-advanced';
			if (Utils.has_key() || this.$root.once_modals_shown?.includes(modal_name)) {
				return;
			}

			this.$root.current_modal = modal_name;
			this.$root.once_modals_shown.push(modal_name);
			jQuery(this.$refs.cart_option_dropdown).blur();
		}
	}
}

export default Cart_Products_Input;