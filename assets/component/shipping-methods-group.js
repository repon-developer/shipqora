const $ = jQuery;
const { __ } = wp.i18n;

const Shipping_Method_Input = {
	template: '#shipqora-shipping-method-input-component',
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
		const [zone_id, instance_id] = shippingMethod.split(':');

		return {
			loading: false,
			zone_id: zone_id,
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
			return 'shipping_method_instances_' + this.zone_id;
		},

		chosen_instance_cache_key() {
			return 'shipping_method_chosen_instance_' + this.zone_id + '_' + this.id;
		},

		shipping_method_data() {
			return [this.zone_id, this.instance_id].filter((item) => item?.length).join(':');
		},

		has_shipping_instance() {
			return Object.values(this.shipping_instances)?.length
		}
	},

	watch: {
		zone_id() {
			this.instance_id = '';
			this.load_shipping_instances();
			this.instance_id = this.$utils.get_cache_data(this.chosen_instance_cache_key);
		},

		instance_id(current_instance_id) {
			this.$utils.set_cache_data(this.chosen_instance_cache_key, current_instance_id);
		},

		shipping_method_data(method_data) {
			this.$emit('update', method_data)
		},

		shipping_instances() {
			this.loading = false;

			if (this.instance_id?.length) {
				const existed_item = this.shipping_instances.find((option) => option?.id == this.instance_id)
				if (!existed_item) {
					const option_label = ('pickup_location' == this.zone_id) ? __('[Deleted Location]', 'shipqora') : __('[Deleted shipping method]', 'shipqora');
					this.shipping_instances.push({ id: this.instance_id, name: option_label });
				}
			}

			this.$utils.set_cache_data(this.cache_key, this.shipping_instances);
		}
	},

	methods: {
		delete_item() {
			const response = confirm(__('Do you want to delete this shipping method?', 'shipqora'))
			if (response) {
				this.$emit('delete')
			}
		},

		load_shipping_instances() {
			if (!this.zone_id?.length) {
				return this.shipping_instances = [];
			}

			const cache_data = this.$utils.get_cache_data(this.cache_key);
			if (cache_data) {
				return this.shipping_instances = cache_data;
			}

			this.loading = true;

			const formData = new FormData();
			formData.append('type', 'shipping_instances')
			formData.append('zone_id', this.zone_id)
			formData.append('security', shipqora_admin?.select2.nonce)
			formData.append('action', 'shipqora/get_select2_dropdown_data')

			fetch(shipqora_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong.', 'shipqora'));
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

const Shipping_Methods_Group = {
	template: '#shipqora-shipping-methods-group-component',

	components: {
		'shipping-method-input': Shipping_Method_Input
	},

	props: {
		shippingMethods: {
			default: Array(''),
			type: [Array, null],
		}
	},

	emits: ['update'],

	data() {
		return {
			shipping_methods: this.shippingMethods
		}
	},

	computed: {
		button_class() {
			return {
				'button-small': this.shipping_methods?.length > 0,
				'button-large-dashed': !this.shipping_methods?.length
			}
		}
	},

	watch: {
		shipping_methods: {
			deep: true,
			handler(data) {
				this.$emit('update', data)
			}
		}
	},

	methods: {
		add_shipping_method() {
			this.shipping_methods.push('')
		},

		delete_shipping_method(index) {
			this.shipping_methods.splice(index, 1);
		},
	}
}

export default Shipping_Methods_Group;