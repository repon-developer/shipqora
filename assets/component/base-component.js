const { __ } = wp.i18n;

const Base_Component = {
	props: {
		draggable: {
			default: true,
			type: Boolean
		},

		layerNo: {
			default: 1,
			type: Number
		},

		hideHeading: {
			type: Boolean,
			default: true,
		},

		hideActions: {
			default: Array(),
			type: [null, Array],
		},

		deleteWarning: {
			type: String,
			default: __('Are you sure you want to delete this item?', 'shipqora-woocommerce'),
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
		...wp.hooks.applyFilters('shipqora.base_component.computed', {}),

		component_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		collapse_button_class() {
			return { 'dashicons-arrow-up-alt2': this.collapse, 'dashicons-arrow-down-alt2': !this.collapse }
		},

		drag_button_classes() {
			return { 'button-drag': true, 'button-drag-feature': true }
		}
	},

	created() {
		this.$emit('update', this.component_data)
	},

	watch: {
		...wp.hooks.applyFilters('shipqora.base_component.watch', {}),

		component_data: {
			deep: true,
			handler(data) {
				this.$emit('update', data)
			}
		}
	},

	methods: {
		...wp.hooks.applyFilters('shipqora.base_component.methods', {}),

		hide_action(action_key) {
			return this.hideActions?.includes(action_key) === true;
		},

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
			return shipqora_admin.calculation_types?.[calculate_basis];
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
	}
}

export default Base_Component;