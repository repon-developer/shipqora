import Utils from '../utils.min.js?v=@@VERSION';


const $ = jQuery;
const { __ } = wp.i18n;



const Feature_Base_Component = {
	props: {
		featureData: {
			default: null
		},

		lineNumber: {
			default: 1,
			type: Number
		},

		additionalTier: {
			type: Boolean,
			default: false,
		}
	},

	data() {
		return {
			collapse: false,
			condition_groups: [],
		}
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
		}
	}
}



const Adjust_Shipping_Cost = {
	extends: Feature_Base_Component,
	template: '#shipflex-adjust-shipping-cost-feature-component',

	data() {
		return {
			...shipflex_admin?.features?.['adjust-shipping-cost'],
			...this.featureData
		}
	},

	created() {

	},

	computed: {

	},

	watch: {

	},

	mounted() {


		console.log(shipflex_admin)
		console.log(this.$data);
	},

	updated() {
		console.log(this.$data);
	},

	methods: {
	}
}

export default Adjust_Shipping_Cost;