(function ($) {
	const { __ } = wp.i18n;

	$('table.shipflex_rewards_table .delete-reward').on('click', function (e) {
		const response = confirm(__('Do you want to delete this reward?', 'shipflex'))
		if (!response) {
			e.preventDefault();
		}
	})

})(jQuery)