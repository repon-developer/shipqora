import { Utils } from '../utils.min.js?v=@@VERSION';
const { __ } = wp.i18n;

const Base_Component = {
	props: {
		draggable: {
			default: true,
			type: Boolean
		},

		tierNo: {
			default: 1,
			type: Number
		},
	},

	data() {
		return { collapse: false }
	},

	computed: {
		...wp.hooks.applyFilters('shipflex.base_component.computed', {}),

		collapse_button_class() {
			return { 'dashicons-arrow-up-alt2': this.collapse, 'dashicons-arrow-down-alt2': !this.collapse }
		}
	},

	methods: {
		...wp.hooks.applyFilters('shipflex.base_component.methods', {}),

		get_calculation_type_label(calculate_basis) {
			return shipflex_admin.calculation_types?.[calculate_basis];
		},

		add_condition_group() {
			if (!Array.isArray(this.condition_groups)) {
				this.condition_groups = []
			}

			this.condition_groups?.push({
				id: Utils.generate_uuid()
			})
		},

		duplicate_condition_group(data, position = 1) {
			if (!Array.isArray(this.condition_groups)) {
				this.condition_groups = []
			}

			this.condition_groups.splice(position, 0, data)
		},

		delete_condition_group(index_no) {
			this.condition_groups.splice(index_no, 1)
		}
	}
}

export default Base_Component;