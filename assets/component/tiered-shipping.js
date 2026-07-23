import { Utils } from '../utils.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Tiered_Shipping = {
	template: '#shipflex-tiered-shipping-component',
	props: {
		rateData: {
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
		},

		modelKey: {
			default: null,
			type: [null, String]
		},

		deleteWarning: {
			type: String,
			default: __('Do you want to delete this rate?', 'shipflex'),
		},
	},

	data() {
		return {
			...shipflex_admin.tiered_shipping_models,
			...this.rateData
		}
	},

	computed: {
		tier_no() {
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
		duplicate_item() {
			if (!this.modelKey?.length || !Array.isArray(this.$parent[this.modelKey])) {
				return;
			}

			this.$parent[this.modelKey].splice(this.number + 1, 0, {
				...this.$data,
				collapse: false,
				id: Utils.generate_uuid()
			})
		},

		delete_item() {
			if (!this.modelKey?.length || !Array.isArray(this.$parent[this.modelKey])) {
				return;
			}

			const response = confirm(this.deleteWarning)
			if (response) {
				this.$parent[this.modelKey].splice(this.number, 1)
			}
		},

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