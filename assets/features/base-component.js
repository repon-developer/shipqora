import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from '../component/base-component.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Feature_Base_Component = {
	extends: Base_Component,
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

		feature_data() {
			return JSON.parse(JSON.stringify(this.$data));
		},

		calculation_metrics() {
			return shipflex_admin.calculation_metrics
		}
	},

	watch: {
		feature_data: {
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
			this.$emit('duplicate', { ...this.feature_data, id: Utils.generate_uuid(), collapse: false }, this.tier_no)
		},

		delete_tier() {
			const response = confirm(this.deleteWarning)
			if (response) {
				this.$emit('delete')
			}
		},
	}
}

export default Feature_Base_Component;