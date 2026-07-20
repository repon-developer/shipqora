import { Utils } from '../global-module.min.js?v=@@VERSION';

const $ = jQuery;
const { __ } = wp.i18n;

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
			type: [Array, null]
		},

		placeholder: {
			default: '',
			type: [String, null],
		},

		isLoading: {
			default: false,
			type: Boolean
		}
	},

	emits: ['update', 'onloading'],

	data() {
		return {
			option_items: [],
			loading: this.isLoading,
			value: this.initialValue,
		}
	},

	computed: {
		cache_key() {
			return 'select2_data_' + this.type + JSON.stringify(this.value || '')
		},

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

		predefined_options() {
			if (Array.isArray(this.options)) {
				return this.options;
			}

			let option_data = {}
			if ('countries' == this.type) {
				option_data = wcSettings?.countries;
			}

			if (typeof shipflex_admin?.select2?.options?.[this.type] === 'object') {
				option_data = shipflex_admin.select2.options[this.type];
			}

			if (this.option?.length && typeof this.options === 'string') {
				try {
					option_data = JSON.parse(this.options)
				} catch (error) {/*do nothing */ }
			}

			if (typeof option_data === 'object') {
				return Object?.keys(option_data)?.map((key) => ({ id: key, name: option_data[key] }))
			}

			return false;
		},

		is_ajax_based() {
			return !(this.has_option_group || false !== this.predefined_options)
		},

		select_option_items() {
			if (false !== this.predefined_options) {
				return this.predefined_options;
			}

			return this.option_items;
		}
	},

	watch: {
		type() {
			this.load_data();
		},

		value() {
			const selected_option = this.option_items.find(option => option.id == this.value);
			this.$emit('update', this.value, selected_option?.sub_options);
		},

		loading() {
			this.$emit('onloading', this.loading);
		},

		isLoading(loading_state) {
			this.loading = loading_state
		},

		option_items() {
			Utils.set_cache_data(cache_key, result.data);
		}
	},

	created() {
		if (this.multiple && (!this.value || !Array.isArray(this.value))) {
			this.value = Array();
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

	methods: {
		get_group_options(key) {
			let options = {};
			if ('states' === this.type) {
				options = wcSettings.countryStates[key];
			}

			return options;
		},

		load_data() {
			if (!this.is_ajax_based || !this.value || (Array.isArray(this.value) && !this.value?.length)) {
				return;
			}

			this.option_items = Array();

			const cache_data = Utils.get_cache_data(this.cache_key);
			if (cache_data) {
				return this.option_items = cache_data;
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

				this.option_items = result.data;

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
						return {
							results: $.map(result.data, (item) => ({ text: item.name, id: item.id }))
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