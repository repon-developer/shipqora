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

		hideHeading: {
			type: Boolean,
			default: true,
		},

		deleteWarning: {
			type: String,
			default: __('Are you sure you want to delete this tier?', 'shipflex'),
		},
	},

	emits: ['update', 'duplicate', 'delete'],

	data() {
		return {
			collapse: false,
			condition_groups: [],
			id: this.$utils.generate_uuid()
		}
	},

	computed: {
		...wp.hooks.applyFilters('shipflex.base_component.computed', {}),

		component_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		collapse_button_class() {
			return { 'dashicons-arrow-up-alt2': this.collapse, 'dashicons-arrow-down-alt2': !this.collapse }
		}
	},

	watch: {
		...wp.hooks.applyFilters('shipflex.base_component.watch', {}),

		component_data: {
			deep: true,
			handler(data) {
				this.$emit('update', data)
			}
		}
	},

	methods: {
		...wp.hooks.applyFilters('shipflex.base_component.methods', {}),

		duplicate_tier() {
			this.$emit('duplicate', { ...this.component_data, id: this.$utils.generate_uuid(), collapse: false })
		},

		delete_tier() {
			const response = confirm(this.deleteWarning)
			if (response) {
				this.$emit('delete')
			}
		},

		get_calculation_type_label(calculate_basis) {
			return shipflex_admin.calculation_types?.[calculate_basis];
		},

		add_condition_group() {
			if (!Array.isArray(this.condition_groups)) {
				this.condition_groups = []
			}

			this.condition_groups?.push({
				id: this.$utils.generate_uuid()
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
		},

		on_order_change(event) {
			const source_element = jQuery(event.from);
			const model_key = source_element.data('model-key')
			if (!this?.[model_key]?.length) {
				return;
			}

			const item = this[model_key].splice(event.oldIndex, 1)[0];
			this[model_key].splice(event.newIndex, 0, item);
		}
	}
}

export default Base_Component;