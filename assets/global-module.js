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

	async generate_key(data_object) {
		const encoder = new TextEncoder();
		const hashBuffer = await crypto.subtle.digest('SHA-256', encoder.encode(JSON.stringify(data_object)));
		const hashArray = Array.from(new Uint8Array(hashBuffer));
		return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
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
	}
}

const Feature_Base_Component = {
	props: {
		featureData: {
			default: null
		},

		lineNumber: {
			default: 1,
			type: Number
		},

		additionalTier: {
			type: Boolean,
			default: false,
		}
	},

	emits: ['update'],

	data() {
		return {
			collapse: false,
			condition_groups: [],
		}
	},

	updated() {
		this.$emit('update', this.$data)
		console.log(this.$data);
	},

	methods: {
		add_condition_group() {
			if (!Array.isArray(this.condition_groups)) {
				this.condition_groups = [];
			}

			this.condition_groups.push({
				id: Utils.generate_uuid()
			})
		},

		delete_condition(index) {
			this.condition_groups.splice(index, 1)
		}
	}
}

export { Utils, Feature_Base_Component };