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
			if ((value == 'subtotal' && this.shipping_cost_type == 'cost_per_unit') || (value != 'subtotal' && this.shipping_cost_type == 'percentage')) {
				this.shipping_cost_type = 'fixed_cost';
			}
		}
	},

	methods: {
		add_condition_group() {
			if (!Array.isArray(this.condition_groups)) {
				this.condition_groups = [];
			}

			this.condition_groups.push({
				id: Utils.generate_uuid()
			})
		},

		delete_condition_group(index) {
			this.condition_groups.splice(index, 1)
		}
	}
}

export default Tiered_Shipping;