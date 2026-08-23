import Base_Component from '../component/base-component.min.js?v=@@VERSION';

const Hide_Other_Shipping_Methods = {
	extends: Base_Component,
	template: '#shipqora-woocommerce-hide-payment-methods-feature-component',

	props: {
		featureData: {
			default: null,
			type: [null, Object],
		},
	},

	data() {
		return {
			...shipqora_admin?.features?.['hide-payment-methods'],
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
	},

	methods: {
		add_payment_method() {
			if (!Array.isArray(this.payment_methods)) {
				this.payment_methods = Array();
			}

			this.payment_methods.push('');
		},

		delete_payment_method(index_no) {
			this.payment_methods.splice(index_no, 1);
		}
	}
}

export default Hide_Other_Shipping_Methods;