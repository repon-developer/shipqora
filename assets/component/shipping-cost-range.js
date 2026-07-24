import { Utils } from '../utils.min.js?v=@@VERSION';
import Global_Base_Component from './global-base-component.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Shipping_Cost_Range_Tier = {
	extends: Global_Base_Component,
	template: '#shipflex-shipping-cost-range-component',
	props: {
		rangeData: {
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
			default: __('Do you want to delete this shipping cost range?', 'shipflex'),
		},
	},

	emits: ['update', 'duplicate', 'delete'],

	data() {
		return {
			collapse: false,
			shipping_cost_ranges: [],
			...shipflex_admin.shipping_cost_range_model,
			...this.rangeData
		}
	},

	computed: {
		tier_no() {
			return this.number + 1;
		},

		metric_label_short_lower() {
			return shipflex_admin.calculation_metrics?.[this.calculateBasis]?.short_lower;
		},

		range_data() {
			return JSON.parse(JSON.stringify(this.$data))
		},
	},

	watch: {
		range_data: {
			deep: true,
			handler(range_data) {
				this.$emit('update', range_data);
			}
		}
	},

	methods: {
		duplicate_item() {
			this.$emit('duplicate', {
				...this.range_data,
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

		add_cost_line() {
			this.add_collection('shipping_cost_ranges', {
				id: Utils.generate_uuid()
			})
		}
	}
}

export default Shipping_Cost_Range_Tier;