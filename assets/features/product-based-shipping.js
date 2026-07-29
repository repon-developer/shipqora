import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from '../component/base-component.min.js?v=@@VERSION';

const Product_Based_Shipping = {
	extends: Base_Component,
	template: '#shipflex-product-based-shipping-feature-component',

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
			shipping_cost_ranges: [],
			calculation_type: 'per_unit_or_percentage',
			...shipflex_admin?.features?.['product-based-shipping'],
			...this.featureData
		}
	},

	computed: {
		component_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		show_calculation_value() {
			return this.calculate_basis == 'fixed_amount' || (this.calculate_basis != 'fixed_amount' && this.calculation_type != 'advanced_calculation')
		},

		calculation_metrics() {
			return shipflex_admin.calculation_metrics;
		},

		calculation_type_label() {
			return this.get_calculation_type_label(this.calculate_basis)
		}
	},

	methods: {
		add_shipping_cost_range() {
			if (!Array.isArray(this.shipping_cost_ranges)) {
				this.shipping_cost_ranges = []
			}

			this.shipping_cost_ranges.push({ id: Utils.generate_uuid() })
		},

		duplicate_shipping_cost_range(data, position) {
			this.shipping_cost_ranges.splice(position, 0, data)
		},

		delete_shipping_cost_range(index) {
			this.shipping_cost_ranges.splice(index, 1)
		}
	}
}

export default Product_Based_Shipping;