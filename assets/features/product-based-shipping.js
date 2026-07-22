import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from './base-component.min.js?v=@@VERSION';

const Product_Based_Shipping = {
	extends: Base_Component,
	template: '#shipflex-product-based-shipping-feature-component',

	data() {
		return {
			calculation_value: '',
			calculate_basis: 'flat_rate',
			calculation_method: 'percentage',
			...shipflex_admin?.features?.['product-based-shipping'],
			...this.featureData
		}
	},

	created() {
		//console.log(shipflex_admin)
	},

	computed: {
		show_calculation_value() {
			return this.calculate_basis == 'flat_rate' || (this.calculate_basis != 'flat_rate' && this.calculation_method != 'advanced_calculation')
		}
	},

	watch: {
		calculate_basis(value) {
			if (value == 'subtotal' && this.calculation_method == 'per_unit') {
				this.calculation_method = 'percentage';
			}

			if (value !== 'subtotal' && this.calculation_method == 'percentage') {
				this.calculation_method = 'per_unit';
			}
		}
	},

	methods: {
		unit_label(text) {
			return this.$root.get_unit_label(this.calculate_basis, text);
		},

		add_advanced_shipping_layer() {
			if (!Array.isArray(this.advanced_calculation_layers)) {
				this.advanced_calculation_layers = [];
			}

			this.advanced_calculation_layers.push({ id: Utils.generate_uuid() })
		}
	}
}

export default Product_Based_Shipping;