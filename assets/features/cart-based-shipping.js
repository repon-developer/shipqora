import Base_Component from '../component/base-component.min.js?v=@@VERSION';

const Cart_Based_Shipping = {
	extends: Base_Component,
	template: '#shipflex-cart-based-shipping-feature-component',

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
			table_rates_lite: {},
			calculation_type: 'per_unit_or_percentage',
			...shipflex_admin?.features?.['cart-based-shipping'],
			...this.featureData
		}
	},

	mounted() {
		console.log(this.$data)
	},

	computed: {
		component_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		show_calculation_value() {
			return this.calculate_basis == 'fixed_amount' || (this.calculate_basis != 'fixed_amount' && this.calculation_type != 'table_rates')
		},

		calculation_metrics() {
			return shipflex_admin.calculation_metrics;
		},

		calculation_type_label() {
			return this.get_calculation_type_label(this.calculate_basis)
		}
	},

	methods: {
		add_new_table_rates() {
			if (!Array.isArray(this.table_rates_layers)) {
				this.table_rates_layers = []
			}

			this.table_rates_layers.push({ id: this.$utils.generate_uuid() })
		},

		duplicate_table_rates_layer(data, position) {
			this.table_rates_layers.splice(position, 0, data)
		},

		delete_table_rates_layer(index) {
			this.table_rates_layers.splice(index, 1)
		}
	}
}

export default Cart_Based_Shipping;