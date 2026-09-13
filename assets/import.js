(function ($) {


	const Shipping_Import = {
		data() {
			return {
				importing: false,
			}
		},


		methods: {
			import_plugin_data(plugin_slug) {
				const formData = new FormData();
				formData.append('plugin', plugin_slug);
				formData.append('nonce', shipqora_import.nonce);
				formData.append('action', 'shipqora/import_third_party_plugin');

				fetch(shipqora_import.ajax_url, {
					method: 'POST',
					body: formData
				}).then(async (response) => {
					const result = await response.json();
					if (typeof result !== 'object' || !response.ok) {
						throw new Error('Something went wrong');
					}

					if (false === result.success) {
						throw new Error('Something went wrong while enable debugging mode');
					}



				}).catch((e) => { console.error(e) }).finally(() => {
					this.importing = false;
				})
			}
		}
	}

	if ($('#shipqora-import').length) {
		Vue.createApp(Shipping_Import).mount('#shipqora-import')
	}

})(jQuery)