import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from './base-component.min.js?v=@@VERSION';

const Product_Based_Shipping = {
	extends: Base_Component,
	template: '#shipflex-product-based-shipping-feature-component',

	data() {
		return {
			calculation_value: '',
			calculate_basis: 'fixed_amount',
			calculation_mode: 'percentage',
			...shipflex_admin?.features?.['product-based-shipping'],
			...this.featureData
		}
	},

	created() {
		//console.log(shipflex_admin)
	},

	computed: {
		show_calculation_value() {
			return this.calculate_basis == 'fixed_amount' || (this.calculate_basis != 'fixed_amount' && this.calculation_mode != 'tiered_calculation')
		}
	},

	watch: {
		calculate_basis(value) {
			if (value == 'subtotal' && this.calculation_mode == 'per_unit') {
				this.calculation_mode = 'percentage';
			}

			if (value !== 'subtotal' && this.calculation_mode == 'percentage') {
				this.calculation_mode = 'per_unit';
			}
		}
	},

	methods: {
		unit_label(text) {
			return this.$root.get_unit_label(this.calculate_basis, text);
		},

		add_tiered_rate_rule() {
			if (!Array.isArray(this.tiered_rate_rules)) {
				this.tiered_rate_rules = [];
			}

			this.tiered_rate_rules.push({ id: Utils.generate_uuid() })
		}
	}
}

export default Product_Based_Shipping;