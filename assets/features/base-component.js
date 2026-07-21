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

	mounted() {
		console.log(this.$data);
	},

	updated() {
		this.$emit('update', this.$data)
		console.log(this.$data);
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

		delete_condition(index) {
			this.condition_groups.splice(index, 1)
		},

		get_add_group_button_class() {
			return {
				'button-full-width': !this.condition_groups?.length,
				'button-large-dashed': !this.condition_groups?.length,
			}
		}
	}
}

export default Base_Component;