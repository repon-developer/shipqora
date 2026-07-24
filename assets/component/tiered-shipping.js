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

		deleteWarning: {
			type: String,
			default: __('Do you want to delete this rate?', 'shipflex'),
		},
	},

	emits: ['update', 'duplicate', 'delete'],

	data() {
		return {
			collapse: false,
			...shipflex_admin.tiered_shipping_models,
			...this.rateData
		}
	},

	computed: {
		tier_no() {
			return this.number + 1;
		},

		metric_label() {
			return 'metric_labelsssssss'; // this.$root.calculation_metric_label(this.calculateBasis);
		},

		metric_label_lower() {
			return 'metric_label_lowersssssssss' // this.$root.calculation_metric_label(this.calculateBasis, 'lowercase');
		},

		rate_data() {
			return JSON.stringify(this.$data)
		},

		collapse_button_class() {
			return { 'dashicons-arrow-up-alt2': this.collapse, 'dashicons-arrow-down-alt2': !this.collapse }
		},
	},

	watch: {
		calculateBasis(value) {
			if ((value == 'subtotal' && this.shipping_cost_type == 'cost_per_unit') || (value != 'subtotal' && this.shipping_cost_type == 'percentage')) {
				this.shipping_cost_type = 'fixed_cost';
			}
		},

		rate_data(string_data) {
			this.$emit('update', JSON.parse(string_data));
		}
	},

	methods: {
		unit_label(text) {
			return this.$root.get_unit_label(this.calculateBasis, text);
		},

		duplicate_item() {
			this.$emit('duplicate', {
				...this.$data,
				collapse: false,
				id: Utils.generate_uuid()
			});
		},

		delete_item() {
			const response = confirm(this.deleteWarning)
			if (response) {
				this.$emit('delete');
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