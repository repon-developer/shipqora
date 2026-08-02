(function ($) {

	function update_debugging_mode_settings(setting_key, value) {
		const formData = new FormData();
		formData.append('nonce', shipflex.debugging_nonce);
		formData.append('action', 'shipflex/update_debugging_mode');

		formData.append(setting_key, value);

		fetch(shipflex.ajax_url, {
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

	$('#shipflex-debugging-box').on('click', '.title-bar', function () {
		$(this).closest('#shipflex-debugging-box').toggleClass('collapse');
		update_debugging_mode_settings('collapse', $('#shipflex-debugging-box').hasClass('collapse'));
	})

	$('#shipflex-debugging-box').on('click', '.shipflex-position-button', function (event) {
		event.preventDefault();

		$(this).closest('#shipflex-debugging-box').toggleClass('left');
		const new_position = $(this).closest('#shipflex-debugging-box').hasClass('left') ? 'left' : 'right';

		update_debugging_mode_settings('position', new_position);
	})

	$('#shipflex-debugging-box').on('click', '.shipflex-disable-button', function (event) {
		event.preventDefault();
		$('#shipflex-debugging-box').remove();
		update_debugging_mode_settings('enable_debugging', false);
	})

})(jQuery)