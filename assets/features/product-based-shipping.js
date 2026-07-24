import { Utils } from '../utils.min.js?v=@@VERSION';
import Feature_Base_Component from './base-component.min.js?v=@@VERSION';

const Product_Based_Shipping = {
	extends: Feature_Base_Component,
	template: '#shipflex-product-based-shipping-feature-component',

	data() {
		return {
			tiered_rates: [],
			calculation_value: '',
			calculate_basis: 'fixed_amount',
			calculation_mode: 'percentage',
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

		add_tiered_rate() {
			if (!Array.isArray(this.tiered_rates)) {
				this.tiered_rates = [];
			}

			this.tiered_rates.push({ id: Utils.generate_uuid() })
		},

		duplicate_tiered_rate(rate_data, position) {
			this.tiered_rates.splice(position, 0, rate_data)
		},

		delete_tiered_rate(index_no) {
			this.tiered_rates.splice(index_no, 1)
		}
	}
}

export default Product_Based_Shipping;