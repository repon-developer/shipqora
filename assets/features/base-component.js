import { Utils } from '../utils.min.js?v=@@VERSION';
import Base_Component from '../component/base-component.min.js?v=@@VERSION';

const { __ } = wp.i18n;

const Feature_Base_Component = {
	extends: Base_Component,
	props: {
		featureData: {
			default: null
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
			this.$emit('duplicate', { ...this.feature_data, id: Utils.generate_uuid(), collapse: false })
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