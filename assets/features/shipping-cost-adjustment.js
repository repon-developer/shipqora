import { Utils, Feature_Base_Component } from '../global-module.min.js?v=@@VERSION';


const Shipping_Cost_Adjustment = {
	extends: Feature_Base_Component,
	template: '#shipflex-shipping-cost-adjustment-feature-component',

	data() {
		return {
			...shipflex_admin?.features?.['shipping-cost-adjustment'],
			...this.featureData
		}
	},
}

export default Shipping_Cost_Adjustment;