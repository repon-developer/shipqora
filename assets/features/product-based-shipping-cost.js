import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from './base-component.min.js?v=@@VERSION';


const Product_Based_Shipping_Cost = {
	extends: Base_Component,
	template: '#shipflex-product-based-shipping-cost-feature-component',

	data() {
		return {
			...shipflex_admin?.features?.['product-based-shipping-cost'],
			...this.featureData
		}
	},

	created() {
		
	},

	component: {
		
	},

	methods: {
		
	}
}

export default Product_Based_Shipping_Cost;