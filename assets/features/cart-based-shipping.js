import Base_Component from '../component/base-component.min.js?v=@@VERSION';

const Cart_Based_Shipping = {
	extends: Base_Component,
	template: '#shipqora-woocommerce-cart-based-shipping-feature-component',

	props: {
		featureData: {
			default: null,
			type: [null, Object],
		},
	},

	data() {
		return {
			calculation_value: '',
			calculate_basis: 'fixed_amount',
			calculation_type: 'per_unit_or_percentage',
			...shipqora_admin?.features?.['cart-based-shipping'],
			...this.featureData
		}
	},

	computed: {
		component_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		show_calculation_value() {
			return this.calculate_basis == 'fixed_amount' || (this.calculate_basis != 'fixed_amount' && this.calculation_type != 'table_rates')
		},

		calculation_metrics() {
			return shipqora_admin.calculation_metrics;
		},

		calculation_type_label() {
			return this.get_calculation_type_label(this.calculate_basis)
		}
	},

	methods: {
		...wp.hooks.applyFilters('shipqora.cart_based_shipping.methods', {}),
	}
}

export default Cart_Based_Shipping;