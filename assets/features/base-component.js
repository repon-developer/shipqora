import { Utils } from '../utils.min.js?v=@@VERSION';

const Base_Component = {
	props: {
		featureData: {
			default: null
		},

		tierNo: {
			default: 1,
			type: Number
		},

		additionalTier: {
			type: Boolean,
			default: false,
		}
	},

	emits: ['update'],

	data() {
		return {
			collapse: false,
			condition_groups: [],
		}
	},

	computed: {
		feature_settings_data() {
			return JSON.parse(JSON.stringify(this.$data));
		}
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
		//console.log(this.feature_settings_data);
	},

	methods: {
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