import { Utils, Feature_Base_Component } from '../global-module.min.js?v=@@VERSION';


const Hide_Other_Shipping_Methods = {
	extends: Feature_Base_Component,
	template: '#shipflex-hide-other-shipping-methods-feature-component',

	data() {
		return {
			...shipflex_admin?.features?.['hide-other-shipping-methods'],
			...this.featureData
		}
	},
}

export default Hide_Other_Shipping_Methods;