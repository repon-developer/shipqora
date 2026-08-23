import Base_Component from '../component/base-component.min.js?v=@@VERSION';

const Hide_Other_Shipping_Methods = {
	extends: Base_Component,
	template: '#shipqora-woocommerce-hide-other-shipping-methods-feature-component',

	props: {
		featureData: {
			default: null,
			type: [null, Object],
		},
	},

	data() {
		return {
			shipping_methods: [],
			...shipqora_admin?.features?.['hide-other-shipping-methods'],
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
		}
	}
}

export default Hide_Other_Shipping_Methods;