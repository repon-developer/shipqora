const $ = jQuery;
const { __ } = wp.i18n;

const Shipping_Method_Input = {
	template: '#shipflex-shipping-method-input-component',
	props: {
		shippingMethod: {
			type: [String, null],
			default: null,
		},
		draggable: {
			default: true,
			type: Boolean
		}
	},

	emits: ['update', 'delete'],

	data() {
		const shippingMethod = (typeof this.shippingMethod === 'string') ? this.shippingMethod : '';
		const [method_id, instance_id] = shippingMethod.split(':');

		return {
			loading: false,
			method_id: method_id,
			instance_id: instance_id,
			shipping_instances: [],
			id: this.$utils.generate_uuid(),
		}
	},
	created() {
		this.load_shipping_instances();
	},

	computed: {
		cache_key() {
			return 'shipping_method_instances_' + this.method_id;
		},

		chosen_instance_cache_key() {
			return 'shipping_method_chosen_instance_' + this.method_id + '_' + this.id;
		},

		registered_shipping_methods() {
			return shipflex_admin?.shipping_methods;
		},

		shipping_method_data() {
			return [this.method_id, this.instance_id].filter((item) => item?.length).join(':');
		},

		has_shipping_instance() {
			return Object.values(this.shipping_instances)?.length
		}
	},

	watch: {
		method_id() {
			this.load_shipping_instances();
			this.instance_id = this.$utils.get_cache_data(this.chosen_instance_cache_key);
		},

		instance_id(current_instance_id) {
			this.$utils.set_cache_data(this.chosen_instance_cache_key, current_instance_id);
		},

		shipping_method_data(method_data) {
			this.$emit('update', method_data)
		},

		shipping_instances(shipping_instances) {
			this.loading = false;

			// if (this.instance_id) {
			// 	const existed = shipping_instances.find((item) => item.id == this.instance_id)
			// 	if (!existed) {
			// 		this.shipping_instances.push({ id: this.instance_id, name: __('[Deleted] - Remove it', 'shipflex') })
			// 	}
			// }

			this.$utils.set_cache_data(this.cache_key, shipping_instances);
		}
	},

	methods: {
		delete_item() {
			const response = confirm(__('Do you want to delete this shipping method?', 'shipflex'))
			if (response) {
				this.$emit('delete')
			}
		},

		load_shipping_instances() {
			if (!this.method_id?.length) {
				return this.shipping_instances = [];
			}

			const cache_data = this.$utils.get_cache_data(this.cache_key);
			if (cache_data) {
				return this.shipping_instances = cache_data;
			}

			this.loading = true;

			const formData = new FormData();
			formData.append('type', 'shipping_instances')
			formData.append('shipping_method', this.method_id)
			formData.append('security', shipflex_admin?.select2.nonce)
			formData.append('action', 'shipflex/get_select2_dropdown_data')

			fetch(shipflex_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong.', 'shipflex'));
				}

				if (false === result.success) {
					throw new Error(result?.data?.message);
				}

				this.shipping_instances = result?.data || [];

			}).catch((e) => this.$utils.set_toast_message(e.message)).finally(() => {
				this.loading = false;
			})
		}
	},
}

export default Shipping_Method_Input;