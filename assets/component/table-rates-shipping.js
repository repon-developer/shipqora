import Base_Component from './base-component.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Table_Rates_Shipping = {
	extends: Base_Component,
	template: '#shipflex-table-rates-shipping-component',
	props: {
		tableRateData: {
			default: null,
			type: [null, Object]
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

	data() {
		return {
			collapse: false,
			shipping_rates: [],
			shipping_rates_errors: [],
			...shipflex_admin.table_rates_shipping_model,
			...this.tableRateData
		}
	},

	computed: {
		metric_label_short_lower() {
			return shipflex_admin.calculation_metrics?.[this.calculateBasis]?.short_lower;
		},

		shipping_rate_default_data() {
			return { max: '', type: 'fixed_amount', value: '' }
		},

		table_rates_data() {
			return JSON.parse(JSON.stringify(this.$data))
		},

		calculation_type_label() {
			return this.get_calculation_type_label(this.calculateBasis)
		},

		drag_button_classes() {
			return {'button-drag': true, 'button-drag-table-rates-layer': true}
		}
	},

	created() {
		this.shipping_rates = this.shipping_rates.map((range_data) => ({
			id: this.$utils.generate_uuid(),
			...this.shipping_rate_default_data,
			...range_data
		}))
	},

	watch: {
		table_rates_data: {
			deep: true,
			handler(range_data) {
				delete range_data.shipping_rates_errors;
				this.$emit('update', range_data);
			}
		},

		shipping_rates: {
			deep: true,
			handler(shipping_rate_lines) {

				const range_erors = shipping_rate_lines.map((range, index) => {
					const errors = Array();

					if (shipping_rate_lines?.length !== (index + 1) && (!range?.max || range?.max == 0)) {
						errors.push('max_empty')
					}

					if (range?.max && range?.max <= this.get_shipping_rate_minimum(index)) {
						errors.push('max_less_than_min')
					}

					if (!range?.value) {
						errors.push('value')
					}

					return { range_no: index, errors: errors }
				})

				this.shipping_rates_errors = range_erors.filter((item) => item?.errors?.length > 0)
			}
		}
	},

	methods: {
		error_classes(index) {
			const range_error = this.shipping_rates_errors.find((item) => item?.range_no == index && item?.errors?.length > 0)

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

		get_prev_rate(current_index) {
			return this.shipping_rates[current_index - 1];
		},

		get_shipping_rate_minimum(index) {
			let prev_item = this.get_prev_rate(index);

			if (prev_item && !prev_item?.max) {
				return this.get_shipping_rate_minimum(index - 1)
			}

			return prev_item?.max || 0;
		},

		add_shipping_rate() {
			if (!Array.isArray(this.shipping_rates)) {
				this.shipping_rates = []
			}

			const last_item = this.get_prev_rate(this.shipping_rates?.length - 1);
			if (last_item && !last_item?.max) {
				return alert(__('Please enter "Max" value of the previous range.', 'shipflex'))
			}

			if (last_item && !last_item?.value) {
				return alert(__('Please enter "Rate" of the previous range.', 'shipflex'))
			}

			this.shipping_rates.push({ id: this.$utils.generate_uuid(), ...this.shipping_rate_default_data })
		},

		delete_shipping_rate(index) {
			const response = confirm(__('Do you want to delete this range?', 'shipflex'));
			if (response) {
				this.shipping_rates.splice(index, 1)
			}
		}
	}
}

export default Table_Rates_Shipping;