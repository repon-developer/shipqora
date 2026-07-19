import Utils from '../utils.min.js?v=@@VERSION';


const $ = jQuery;
const { __ } = wp.i18n;

const helper_models = {
	saving: false,
	loading: true,
	once_modals: [],
	toast_message: null,
	current_modal: null,
	show_toast_message: false,
	toast_message_type: 'error',
}

const Adjust_Shipping_Cost = {
	template: '#shipflex-adjust-shipping-cost-feature-component',

	data() {
		return {
			id: 0,
			title: '',
			status: 'development',

			...helper_models,
			
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
		console.log(this.$data)
	},

	updated() {
		//console.log(this.$data);
	},

	methods: {
	}
}

export default Adjust_Shipping_Cost;