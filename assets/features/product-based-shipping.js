import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from './base-component.min.js?v=@@VERSION';


const Product_Based_Shipping = {
	extends: Base_Component,
	template: '#shipflex-product-based-shipping-feature-component',

	data() {
		return {
			...shipflex_admin?.features?.['product-based-shipping'],
			...this.featureData
		}
	},

	created() {
		//console.log(shipflex_admin)
	},

	computed: {
		unit_label() {
			return Utils.get_unit_label(this.calculate_by);
		}
	},

	methods: {

	}
}

export default Product_Based_Shipping;