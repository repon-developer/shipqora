import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from './base-component.min.js?v=@@VERSION';


const Product_Based_Shipping = {
	extends: Base_Component,
	template: '#shipflex-product-based-shipping-feature-component',

	data() {
		return {
			calculation_value: '',
			calculate_by: 'subtotal',
			calculation_method: 'flat_rate',
			...shipflex_admin?.features?.['product-based-shipping'],
			...this.featureData
		}
	},

	created() {
		//console.log(shipflex_admin)
	},

	computed: {

	},

	watch: {
		calculate_by(value) {
			if ((value !== 'subtotal' && this.calculation_method == 'percentage') || (value === 'subtotal' && this.calculation_method == 'per_unit')) {
				this.calculation_method = 'flat_rate';
			}
		}
	},

	methods: {
		unit_label(text) {
			return this.$root.get_unit_label(this.calculate_by, text);
		}
	}
}

export default Product_Based_Shipping;