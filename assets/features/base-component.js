import { Utils } from '../utils.min.js?v=@@VERSION';
const { __ } = wp.i18n;

const Base_Component = {
	props: {
		featureData: {
			default: null
		},

		tierIndex: {
			default: 1,
			type: Number
		},

		additionalTier: {
			type: Boolean,
			default: false,
		},

		deleteWarning: {
			type: String,
			default: __('Do you want to delete this tier?', 'shipflex'),
		},
	},

	emits: ['update', 'duplicate', 'delete'],

	data() {
		return {
			collapse: false,
			condition_groups: [],
			id: Utils.generate_uuid(),
		}
	},

	computed: {
		tier_no() {
			return this.tierIndex + 1;
		},

		feature_settings_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		collapse_button_class() {
			return {
				'dashicons-arrow-up-alt2': this.collapse,
				'dashicons-arrow-down-alt2': !this.collapse,
			}
		},
	},

	watch: {
		feature_settings_data: {
			deep: true,
			handler(data) {
				this.$emit('update', data)
			}
		}
	},

	mounted() {
		//console.log(this.$data);
	},

	updated() {
		//console.log(this.$data);
	},

	methods: {
		duplicate_tier() {
			this.$emit('duplicate', { ...this.feature_settings_data, id: Utils.generate_uuid(), collapse: false }, this.tier_no)
		},

		delete_tier() {
			const response = confirm(this.deleteWarning)
			if (response) {
				this.$emit('delete')
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

export default Base_Component;