import { Utils } from '../utils.min.js?v=@@VERSION';
import Feature_Base_Component from './base-component.min.js?v=@@VERSION';

const Product_Based_Shipping = {
	extends: Feature_Base_Component,
	template: '#shipflex-product-based-shipping-feature-component',

	data() {
		return {
			calculation_value: '',
			calculation_mode: 'percentage',
			calculate_basis: 'fixed_amount',
			advanced_calculation_tiers: [],
			...shipflex_admin?.features?.['product-based-shipping'],
			...this.featureData
		}
	},

	updated() {
		//console.log(this.$data)
	},

	computed: {
		show_calculation_value() {
			return this.calculate_basis == 'fixed_amount' || (this.calculate_basis != 'fixed_amount' && this.calculation_mode != 'tiered_rates')
		},

		calculation_metrics() {
			return shipflex_admin.calculation_metrics;
		}
	},

	methods: {
		unit_label(text) {
			return this.$root.get_unit_label(this.calculate_basis, text);
		},

		add_shipping_cost_range() {
			this.add_collection('advanced_calculation_tiers', { id: Utils.generate_uuid() })
		}
	}
}

export default Product_Based_Shipping;