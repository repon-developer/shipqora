import { Utils } from '../utils.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Tiered_Shipping = {
	template: '#shipflex-tiered-shipping-component',
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
			...shipflex_admin.tiered_shipping_models,
			...this.shippingData
		}
	},

	computed: {
		variation_no() {
			return this.number + 1;
		},

		metric_label() {
			return this.$root.calculation_metric_label(this.calculateBasis);
		},

		metric_label_lower() {
			return this.$root.calculation_metric_label(this.calculateBasis, 'lowercase');
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

export default Tiered_Shipping;