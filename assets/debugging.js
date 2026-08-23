(function ($) {

	function update_debugging_mode_settings(setting_key, value) {
		const formData = new FormData();
		formData.append('nonce', shipqora.debugging_nonce);
		formData.append('action', 'shipqora/update_debugging_mode');

		formData.append(setting_key, value);

		fetch(shipqora.ajax_url, {
			method: 'POST',
			body: formData
		}).then(async (response) => {
			const result = await response.json();
			if (typeof result !== 'object' || !response.ok) {
				return;
			}

			if (false === result.success) {
				return;
			}

		}).catch((e) => { })
	}

	$('#shipqora-woocommerce-debugging-box').on('click', '.title-bar', function () {
		$(this).closest('#shipqora-woocommerce-debugging-box').toggleClass('collapse');
		update_debugging_mode_settings('collapse', $('#shipqora-woocommerce-debugging-box').hasClass('collapse'));
	})

	$('#shipqora-woocommerce-debugging-box').on('click', '.shipqora-woocommerce-position-button', function (event) {
		event.preventDefault();

		$(this).closest('#shipqora-woocommerce-debugging-box').toggleClass('left');
		const new_position = $(this).closest('#shipqora-woocommerce-debugging-box').hasClass('left') ? 'left' : 'right';

		update_debugging_mode_settings('position', new_position);
	})

	$('#shipqora-woocommerce-debugging-box').on('click', '.shipqora-woocommerce-disable-button', function (event) {
		event.preventDefault();
		$('#shipqora-woocommerce-debugging-box').remove();
		update_debugging_mode_settings('enable_debugging', false);
	})

})(jQuery)