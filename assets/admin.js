(function ($) {
	const { __ } = wp.i18n;

	$('#shipqora').on('click', 'table.shipqora_rules_table a.delete-rule', function (e) {
		const response = confirm(__('Do you want to delete this rule?', 'shipqora'));
		if (!response) {
			e.preventDefault();
		}
	})

})(jQuery)