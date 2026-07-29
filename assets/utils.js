let toast_timer = 0;

const Utils = {
	app: null,
	cache_data: {},
	has_key() {
		return wp.hooks.applyFilters('shipflex.has_key', false);
	},

	generate_uuid() {
		return Math.random().toString(36).slice(2, 9);
	},

	set_cache_data(key, value) {
		this.cache_data[key] = value;
	},

	get_cache_data(key) {
		return this.cache_data?.[key];
	},

	set_toast_message(message, type = 'error') {
		if (!message) {
			return;
		}

		this.app.toast_message = message;
		this.app.show_toast_message = true
		this.app.toast_message_type = type;

		clearTimeout(toast_timer)
		toast_timer = setTimeout(() => this.app.show_toast_message = false, 3500)
	},

	highlight_section(section_name) {
		if (!section_name || !section_name?.length) {
			return;
		}

		setTimeout(() => {
			const section_item = jQuery('#shipflex').find(`[data-highlight-section="${section_name}"]`);
			if (!section_item?.length) {
				return;
			}

			jQuery('html, body').stop().animate({ scrollTop: section_item.offset().top - 100 }, 400, 'swing', function () {
				section_item.addClass('section-highlighted');
				setTimeout(() => section_item.removeClass('section-highlighted'), 3000)
			});
		}, 20)
	}
}

export default Utils;