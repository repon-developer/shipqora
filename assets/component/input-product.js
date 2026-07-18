import Utils from '../utils.min.js?v=@@VERSION';

const $ = jQuery;
const { __ } = wp.i18n;

const Input_Product = {
	template: '#shipflex-product-input-component',
	props: {
		product: {
			type: Object,
			default: null,
		},

		productType: {
			default: 'products',
			type: [String, null]
		},

		defaultOptionLabel: {
			type: String,
			default: __('All variations', 'shipflex')
		},

		hideDefaultOption: {
			default: false,
			type: Boolean
		},

		draggable: {
			default: false,
			type: Boolean
		},

		hideTools: {
			default: false,
			type: Boolean
		}
	},

	emits: ['update', 'delete'],

	data() {
		return {
			loading: false,
			product_id: '',
			product_variations: [],
			hold_products_variaton: {},
			variation_id: this.defaultOptionValue,
			...this.product
		}
	},

	computed: {
		select2_type() {
			if (['products', 'variable_products'].includes(this.productType)) {
				return this.productType;
			}

			return 'products';
		},

		get_cache_key() {
			return 'product_variations_' + this.product_id;
		},

		product_data() {
			const data = JSON.parse(JSON.stringify(this.$data))
			delete data.loading;
			delete data.product_variations;
			delete data.hold_products_variaton;
			return data;
		},
	},

	created() {
		if (this.product_id && this.variation_id) {
			this.hold_products_variaton[this.product_id] = this.variation_id;
		}

		this.$emit('update', this.product_data)
	},

	mounted() {
		const product_variations = Utils.get_cache_data(this.get_cache_key)
		if (Array.isArray(product_variations)) {
			this.product_variations = product_variations;
		}
	},

	updated() {
		this.$emit('update', this.product_data)
	},

	watch: {
		product_id() {
			if (this.hold_products_variaton?.[this.product_id]) {
				this.variation_id = this.hold_products_variaton[this.product_id];
			} else {
				this.variation_id = 0;
			}

			if (this.hideDefaultOption) {
				const has_variations = this.product_variations?.find((variation) => variation?.id == this.variation_id)
				if (!has_variations) {
					const first_variation = this.product_variations?.[0];
					if (typeof first_variation === 'object') {
						this.variation_id = first_variation.id.toString();
					}
				}
			}
		},

		variation_id(new_variation_id) {
			this.hold_products_variaton[this.product_id] = new_variation_id;
		},

		product_variations(variations) {
			Utils.set_cache_data(this.get_cache_key, variations);
		}
	},

	methods: {
		delete_item() {
			const response = confirm(__('Do you want to delete this product?', 'shipflex'))
			if (response) {
				this.$emit('delete')
			}
		}
	},
}

export default Input_Product;