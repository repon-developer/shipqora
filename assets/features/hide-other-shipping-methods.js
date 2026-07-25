import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from '../component/base-component.min.js?v=@@VERSION';


const Hide_Other_Shipping_Methods = {
	extends: Base_Component,
	template: '#shipflex-hide-other-shipping-methods-feature-component',

	featureData: {
		default: null,
		type: [null, Object],
	},

	data() {
		return {
			shipping_methods: [],
			...shipflex_admin?.features?.['hide-other-shipping-methods'],
			...this.featureData
		}
	},

	created() {
		if (!Array.isArray(this.shipping_methods)) {
			this.shipping_methods = [];
		}
	},

	component: {
		component_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		add_shipping_method_button_class() {
			return {
				'button-small': this.shipping_methods?.length > 0,
				'button-large-dashed': !shipping_methods?.length
			}
		}
	},

	methods: {
		add_shipping_method() {
			this.shipping_methods.push('')
		},

		delete_shipping_method(index) {
			this.shipping_methods.splice(index, 1);
		}
	}
}

export default Hide_Other_Shipping_Methods;