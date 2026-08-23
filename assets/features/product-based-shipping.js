import Cart_Based_Shipping from './cart-based-shipping.min.js?v=@@VERSION';

const Product_Based_Shipping = {
	extends: Cart_Based_Shipping,
	template: '#shipqora-woocommerce-product-based-shipping-feature-component',

	props: {
		featureData: {
			default: null,
			type: [null, Object],
		},
	},

	data() {
		return {
			...shipqora_admin?.features?.['product-based-shipping'],
			...this.featureData
		}
	},
}

export default Product_Based_Shipping;