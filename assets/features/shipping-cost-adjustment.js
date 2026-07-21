import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from './base-component.min.js?v=@@VERSION';

const Shipping_Cost_Adjustment = {
	extends: Base_Component,
	template: '#shipflex-shipping-cost-adjustment-feature-component',

	data() {
		return {
			...shipflex_admin?.features?.['shipping-cost-adjustment'],
			...this.featureData
		}
	},
}

export default Shipping_Cost_Adjustment;