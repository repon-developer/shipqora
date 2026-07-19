import { Utils, Feature_Base_Component } from '../global-module.min.js?v=@@VERSION';


const Adjust_Shipping_Cost = {
	extends: Feature_Base_Component,
	template: '#shipflex-adjust-shipping-cost-feature-component',

	data() {
		return {
			...shipflex_admin?.features?.['adjust-shipping-cost'],
			...this.featureData
		}
	},
}

export default Adjust_Shipping_Cost;