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

	$('#shipqora-debugging-box').on('click', '.title-bar', function () {
		$(this).closest('#shipqora-debugging-box').toggleClass('collapse');
		update_debugging_mode_settings('collapse', $('#shipqora-debugging-box').hasClass('collapse'));
	})

	$('#shipqora-debugging-box').on('click', '.shipqora-position-button', function (event) {
		event.preventDefault();

		$(this).closest('#shipqora-debugging-box').toggleClass('left');
		const new_position = $(this).closest('#shipqora-debugging-box').hasClass('left') ? 'left' : 'right';

		update_debugging_mode_settings('position', new_position);
	})

	$('#shipqora-debugging-box').on('click', '.shipqora-disable-button', function (event) {
		event.preventDefault();
		$('#shipqora-debugging-box').remove();
		update_debugging_mode_settings('enable_debugging', false);
	})

})(jQuery)