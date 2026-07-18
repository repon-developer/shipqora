import Utils from '../utils.min.js?v=@@VERSION';
const $ = jQuery;
const { __ } = wp.i18n;
let hold_select2_options = [];

const Select2_Dropdown = {
	template: '#shipflex-select2-dropdown',

	props: {
		type: {
			type: String,
			default: 'options'
		},

		initialValue: {
			default: null,
			type: [String, Array, null],
		},

		multiple: {
			type: Boolean,
			default: true
		},

		options: {
			default: null,
			type: [Object, null]
		},

		placeholder: {
			default: '',
			type: [String, null],
		},

		childKey: {
			default: null,
			type: [String, null]
		}
	},

	emits: ['update', 'update-childs', 'onloading'],

	data() {
		return {
			loading: false,
			hold_options: [],
			value: this.initialValue,
		}
	},

	computed: {
		option_groups() {
			const option_group_items = {};
			if ('states' === this.type) {
				Object.entries(wcSettings.countryStates).forEach(([country_code, country_states]) => {
					if (typeof country_states === 'object' && Object.keys(country_states).length && wcSettings.countries?.[country_code]) {
						option_group_items[country_code] = wcSettings.countries?.[country_code];
					}
				})
			}

			return option_group_items;
		},

		has_option_group() {
			return Object.keys(this.option_groups).length > 0;
		},

		option_items() {
			const predefined_options = shipflex_admin?.select2?.options?.[this.type];
			if (typeof predefined_options === 'object') {
				return predefined_options;
			}

			if (this.options && typeof this.options === 'object') {
				return this.options;
			}

			try {
				return JSON.parse(this.options)
			} catch (error) {/*do nothing */ }

			return null;
		},

		is_ajax_based() {
			if (this.has_option_group) {
				return false;
			}

			const option_items = this.option_items;
			return !option_items || typeof option_items !== 'object';
		}
	},

	created() {
		if (this.multiple && (!this.value || !Array.isArray(this.value))) {
			this.value = Array();
		}

		const option_items = this.option_items;
		if (option_items && typeof option_items === 'object') {
			this.hold_options = Object.entries(option_items).map(([id, name]) => ({ id, name }))
		}
	},

	beforeUpdate() {
		this.destroy_select2()
	},

	mounted() {
		this.load_data();
		this.handle_select2_field();
	},

	beforeUnmount() {
		this.destroy_select2()
	},

	updated() {
		this.handle_select2_field();
	},

	watch: {
		type() {
			this.load_data();
		},

		value() {
			this.send_childs();
			this.$emit('update', this.value);
		},

		loading() {
			this.$emit('onloading', this.loading);
		}
	},

	methods: {
		get_cache_key() {
			return 'select2_data_' + this.type + JSON.stringify(this.value || '')
		},

		get_group_options(key) {
			let options = {};
			if ('states' === this.type) {
				options = wcSettings.countryStates[key];
			}

			return options;
		},

		send_childs() {
			if (this.childKey?.length && !Array.isArray(this.value)) {
				const selected_option = hold_select2_options.find(option => option.id == this.value);
				this.$emit('update-childs', selected_option?.[this.childKey] || []);
			}
		},

		load_data() {
			if (!this.is_ajax_based || !this.value || (Array.isArray(this.value) && !this.value?.length)) {
				return;
			}

			this.hold_options = Array();

			const cache_key = this.get_cache_key();
			const cache_data = Utils.get_cache_data(cache_key);
			if (cache_data) {
				return this.hold_options = cache_data;
			}

			this.loading = true;
			const formData = new FormData();

			if (this.multiple) {
				this.value.forEach((value) => { formData.append('values[]', value) })
			} else {
				formData.append('values[]', this.value)
			}

			formData.append('type', this.type)
			formData.append('security', shipflex_admin?.select2.nonce)
			formData.append('action', 'shipflex/get_select2_dropdown_data')

			fetch(shipflex_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong.', 'shipflex'));
				}

				if (false === result.success) {
					throw new Error(result?.data?.message);
				}

				this.hold_options = result.data;
				hold_select2_options = result.data;
				this.send_childs();
				Utils.set_cache_data(cache_key, result.data);

			}).catch((e) => Utils.set_toast_message(e.message)).finally(() => {
				this.loading = false;
				this.handle_select2_field();
			})
		},

		destroy_select2() {
			if ($(this.$refs.select2_dropdown).data('select2')) {
				$(this.$refs.select2_dropdown).select2('destroy');
			}
		},

		handle_select2_field() {
			const self = this;

			if (!this.is_ajax_based) {
				$(this.$refs.select2_dropdown).select2({
					allowClear: true,
					placeholder: self.placeholder,
					dropdownCssClass: 'shipflex-select2-dropdown',
				}).on('change', function () {
					self.value = $(this).val()
				})

				return;
			}

			$(this.$refs.select2_dropdown).select2({
				allowClear: true,
				placeholder: self.placeholder,
				dropdownCssClass: 'shipflex-select2-dropdown',
				ajax: {
					url: shipflex_admin.ajax_url,
					dataType: "json",
					type: "POST",
					delay: 500,
					data: function (params) {
						return {
							type: self.type,
							term: params.term,
							security: shipflex_admin?.select2?.nonce,
							action: 'shipflex/get_select2_dropdown_data'
						}
					},

					transport: function (params, success, failure) {
						const cache_key = JSON.stringify(params.data)
						const cache_data = Utils.get_cache_data(cache_key);
						if (cache_data) {
							return success(cache_data);
						}

						const request = $.ajax(params);
						request.then(data => {
							Utils.set_cache_data(cache_key, data)
							success(data);
						});

						request.fail(failure);

						return request;
					},

					processResults: function (result) {
						hold_select2_options = result.data;

						return {
							results: $.map(result.data, function (item) {
								return {
									text: item.name,
									id: item.id
								}
							})
						};
					}
				}
			}).on('change', function () {
				self.value = $(this).val()
			})
		}
	}
}

export default Select2_Dropdown;