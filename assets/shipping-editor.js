const $ = jQuery;
const { __ } = wp.i18n;

const Shipping_Editor = {
	data() {
		return {
			loading: true,
			instance_id: null,
			created_rule: null,
			attached_rules: [],
			creating_rule: false,
		}
	},

	watch: {
		instance_id() {
			this.loading = true;
			this.created_rule = null;
			this.attached_rules = [];

			const formData = new FormData();
			formData.append('instance_id', this.instance_id);
			formData.append('nonce', shipflex_shipping_editor.nonce);
			formData.append('action', 'shipflex/get_attached_rule');

			fetch(shipflex_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong while enable debugging mode', 'shipflex'));
				}

				if (false === result.success) {
					throw new Error(__('Something went wrong while enable debugging mode', 'shipflex'));
				}

				if (Array.isArray(result?.data)) {
					this.attached_rules = result.data;
				}
			}).catch((e) => { console.error(e) }).finally(() => {
				this.loading = false;
			})
		}
	},

	methods: {
		get_status(status) {
			return shipflex_admin.statuses?.[status]?.currently_text
		},

		create_rule() {
			if (!this.instance_id) {
				return;
			}

			this.creating_rule = true;
			const title_fields = ['woocommerce_free_shipping_title', 'woocommerce_flat_rate_title']

			const shipping_method_title = title_fields.map((field_name) => {
				if ($(`[name="${field_name}"]`).length) {
					return $(`[name="${field_name}"]`).val();
				}

				return null;
			}).find((current_title) => current_title && current_title?.length)

			const formData = new FormData();
			formData.append('title', shipping_method_title);
			formData.append('instance_id', this.instance_id);
			formData.append('nonce', shipflex_shipping_editor.nonce);
			formData.append('action', 'shipflex/create_and_attach_rule');

			fetch(shipflex_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong while enable debugging mode', 'shipflex'));
				}

				if (false === result.success) {
					throw new Error(__('Something went wrong while enable debugging mode', 'shipflex'));
				}

				this.created_rule = result.data;

			}).catch((e) => { console.error(e) }).finally(() => {
				this.creating_rule = false;
			})
		}
	}
}


function initialize_shipping_editor(instance_id) {
	if (!jQuery('#shipflex.shipflex-shipping-editor').length) {
		return;
	}

	const Shipping_Editor_App = Vue.createApp(Shipping_Editor)

	const Shipping_Editor_App_Holder = Shipping_Editor_App.mount('#shipflex.shipflex-shipping-editor')
	Shipping_Editor_App_Holder.instance_id = instance_id;
}

initialize_shipping_editor(shipflex_shipping_editor?.instance_id);

jQuery(document.body).on('wc_backbone_modal_loaded', function (event, modal_name) {
	if ('wc-modal-shipping-method-settings' !== modal_name) {
		return;
	}

	const instance_id = $('#shipflex.shipflex-shipping-editor').closest('.wc-modal-shipping-method-settings').data('id');
	initialize_shipping_editor(instance_id)
});