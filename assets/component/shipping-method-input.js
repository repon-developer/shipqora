import { Utils } from '../global-module.min.js?v=@@VERSION';

const $ = jQuery;
const { __ } = wp.i18n;

const Shipping_Method_Input = {
	template: '#shipflex-shipping-method-input-component',
	props: {
		settings: {
			type: Object,
			default: null,
		}
	},

	emits: ['update', 'delete'],

	data() {
		return {
			loading: true,
			shipping_rate: '',
			shipping_method: '',
			shipping_instances: [],
			id: Utils.generate_uuid(),
			...this.settings
		}
	},

	created() {
		this.load_shipping_instances();
	},

	computed: {
		cache_key() {
			return 'shipping_method_instances_' + this.shipping_method;
		},

		shipping_method_data() {
			const data = JSON.parse(JSON.stringify(this.$data))
			delete data.id;
			delete data.loading;
			delete data.shipping_instances;
			return data;
		},
	},

	watch: {
		shipping_method() {
			this.load_shipping_instances();
		},

		shipping_method_data() {
			this.$emit('update', this.shipping_method_data)
		}
	},

	mounted() {
		// const product_variations = Utils.get_cache_data(this.get_cache_key)
		// if (Array.isArray(product_variations)) {
		// 	this.product_variations = product_variations;
		// }
	},

	updated() {
		console.log(this.$data)
	},



	methods: {
		delete_item() {
			const response = confirm(__('Do you want to delete this shipping line?', 'shipflex'))
			if (response) {
				this.$emit('delete')
			}
		},

		load_shipping_instances() {
			const formData = new FormData();
			formData.append('type', 'shipping_instances')
			formData.append('shipping_method', this.shipping_method)
			formData.append('security', shipflex_admin?.select2.nonce)
			formData.append('action', 'shipflex/get_select2_dropdown_data')

			fetch(shipflex_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				console.log(result)
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong.', 'shipflex'));
				}

				if (false === result.success) {
					throw new Error(result?.data?.message);
				}

				this.shipping_instances = result?.data || [];
				Utils.set_cache_data(this.cache_key, result.data);

			}).catch((e) => Utils.set_toast_message(e.message)).finally(() => {
				this.loading = false;
			})
		}
	},
}

export default Shipping_Method_Input;