import { Utils } from '../utils.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Advanced_Shipping = {
	template: '#shipflex-advanced-calculation-component',
	props: {
		shippingData: {
			default: null,
			type: [null, Object]
		},

		number: {
			default: 0,
			type: Number,
		},

		calculateBasis: {
			default: null,
			type: [null, String]
		}
	},

	emits: ['update'],

	data() {
		return {
			...shipflex_admin.advanced_shipping_cost_models,
			...this.shippingData
		}
	},

	computed: {
		variation_no() {
			return this.number + 1;
		}
	},

	watch: {
		calculateBasis(value) {
			if ((value == 'subtotal' && this.sdfsf == 'cost_per_unit') || (value != 'subtotal' && this.sdfsf == 'percentage')) {
				this.sdfsf = 'flat_rate';
			}
		}
	},

	methods: {

	}
}

export default Advanced_Shipping;