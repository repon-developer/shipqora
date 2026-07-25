import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from './base-component.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Shipping_Cost_Range_Tier = {
	extends: Base_Component,
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
			shipping_ranges_errors: [],
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

		range_default_data() {
			return { max: '', type: 'fixed_amount', value: '' }
		},

		range_data() {
			return JSON.parse(JSON.stringify(this.$data))
		},

		calculation_type_label() {
			return this.get_calculation_type_label(this.calculateBasis)
		},
	},

	created() {
		this.shipping_cost_ranges = this.shipping_cost_ranges.map((range_data) => ({
			id: Utils.generate_uuid(),
			...this.range_default_data,
			...range_data
		}))
	},

	watch: {
		range_data: {
			deep: true,
			handler(range_data) {
				delete range_data.shipping_ranges_errors;
				//console.log(range_data)
				this.$emit('update', range_data);
			}
		},

		shipping_cost_ranges: {
			deep: true,
			handler(range_items) {

				const range_erors = range_items.map((range, index) => {
					const errors = Array();

					if (range_items?.length !== (index + 1) && (!range?.max || range?.max == 0)) {
						errors.push('max_empty')
					}

					if (range?.max && range?.max <= this.get_range_minimum(index)) {
						errors.push('max_less_than_min')
					}

					if (!range?.value) {
						errors.push('value')
					}

					return { range_no: index, errors: errors }
				})

				this.shipping_ranges_errors = range_erors.filter((item) => item?.errors?.length > 0)
			}
		}
	},

	methods: {
		error_classes(index) {
			const range_error = this.shipping_ranges_errors.find((item) => item?.range_no == index && item?.errors?.length > 0)

			if (range_error) {
				const error_classes = {
					'range-has-error': true,
				}

				if (range_error?.errors?.includes('max_empty')) {
					error_classes['range-error-max-empty'] = true;
				}

				if (range_error?.errors?.includes('value')) {
					error_classes['range-error-value-empty'] = true;
				}

				if (range_error?.errors?.includes('max_less_than_min')) {
					error_classes['range-error-less-value'] = true;
				}

				return error_classes;
			}

			return null;
		},

		get_prev_range(current_index) {
			return this.shipping_cost_ranges[current_index - 1];
		},

		get_range_minimum(index) {
			let prev_item = this.get_prev_range(index);

			if (prev_item && !prev_item?.max) {
				return this.get_range_minimum(index - 1)
			}

			return prev_item?.max || 0;
		},

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

		add_cost_range() {
			const last_item = this.get_prev_range(this.shipping_cost_ranges?.length - 1);
			if (last_item && !last_item?.max) {
				return alert(__('Please enter "Max" value of the previous range.', 'shipflex'))
			}

			if (last_item && !last_item?.value) {
				return alert(__('Please enter "Rate" of the previous range.', 'shipflex'))
			}

			this.add_collection('shipping_cost_ranges', { id: Utils.generate_uuid(), ...this.range_default_data })
		},

		delete_cost_range(index) {
			const response = confirm(__('Do you want to delete this range?', 'shipflex'));
			if (response) {
				this.delete_collection('shipping_cost_ranges', index)
			}
		}
	}
}

export default Shipping_Cost_Range_Tier;