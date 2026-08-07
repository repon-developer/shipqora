const $ = jQuery;
const { __ } = wp.i18n;

let ajax_response_data = null;

const Select2_Dropdown = {
	template: '#shipqora-select2-dropdown',

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
			type: [Array, null, Object]
		},

		placeholder: {
			default: '',
			type: [String, null],
		},

		isLoading: {
			default: false,
			type: Boolean
		},
		enableGroup: {
			type: Boolean,
			default: false,
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

		predefined_options() {
			if ('states' == this.type) {
				const option_items = Object.entries(wcSettings.countryStates).map(([country_code, country_states]) => {
					const option_item = { id: country_code, name: wcSettings.countries?.[country_code] }
					if (typeof country_states === 'object' && Object.keys(country_states).length) {
						option_item.sub_options = Object.entries(country_states).map(([id, name]) => ({ id, name }))
					}

					return option_item;
				})

				return option_items;
			}

			if ('countries' == this.type) {
				return Object.entries(wcSettings?.countries).map(([id, name]) => ({ id, name }))
			}

			if (typeof shipqora_admin?.select2?.options?.[this.type] === 'object') {
				return Object.entries(shipqora_admin.select2.options[this.type]).map(([id, name]) => ({ id, name }))
			}

			if ('shipping_instances' == this.type && typeof this.options == 'object') {
				return this.options.map((item) => {
					item.sub_options = Object.entries(item.instances).map(([id, name]) => ({ id, name }))
					return item;
				})
			}

			if (Array.isArray(this.options)) {
				return this.options;
			}

			return false;
		},

		select_option_items() {
			if (false !== this.predefined_options) {
				return this.predefined_options;
			}

			return this.option_items;
		},

		has_option_group() {
			if ('states' == this.type || 'shipping_instances' == this.type) {
				return true;
			}

			const has_sub_option = this.select_option_items.find((item) => Array.isArray(item?.sub_options))
			return typeof has_sub_option !== 'undefined' && this.enableGroup == true;
		},

		is_ajax_based() {
			return false === this.predefined_options
		},
	},

	watch: {
		type() {
			this.load_data();
		},

		value() {
			const selected_option = ajax_response_data?.find(option => option.id == this.value);
			this.$emit('update', this.value, selected_option?.sub_options);
		},

		loading() {
			this.$emit('onloading', this.loading);
		},

		isLoading(loading_state) {
			this.loading = loading_state
		},

		select_option_items(values) {
			this.$utils.set_cache_data(this.cache_key, values);
			const selected_option = ajax_response_data?.find(option => option.id == this.value);
			this.$emit('update', this.value, selected_option?.sub_options);
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
		load_data() {
			if (!this.is_ajax_based || !this.value || (Array.isArray(this.value) && !this.value?.length)) {
				return;
			}

			this.option_items = Array();

			const cache_data = this.$utils.get_cache_data(this.cache_key);
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
			formData.append('security', shipqora_admin?.select2.nonce)
			formData.append('action', 'shipqora/get_select2_dropdown_data')

			fetch(shipqora_admin.ajax_url, {
				method: 'POST',
				body: formData
			}).then(async (response) => {
				const result = await response.json();
				if (typeof result !== 'object' || !response.ok) {
					throw new Error(__('Something went wrong.', 'shipqora'));
				}

				if (false === result.success) {
					throw new Error(result?.data?.message);
				}

				this.option_items = result.data;
				ajax_response_data = this.option_items

			}).catch((e) => this.$utils.set_toast_message(e.message)).finally(() => {
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
					dropdownCssClass: 'shipqora-select2-dropdown',
					matchefffffffffffr: function (params, data) {
						const search_terms = params?.term?.toLowerCase();
						if (search_terms?.length) {
							if (Array.isArray(data.children)) {
								const children = data.children.map((item) => {
									const keywords = item.text.split(' ').map((keyword) => keyword.toLowerCase().trim());
									keywords.push(item.id)
									item.keywords = keywords;
									return item;
								}).filter((child_item) => {
									const results = child_item.keywords.filter((keyword) => keyword.indexOf(search_terms) >= 0)
									return results.length > 0
								})

								const modifiedData = $.extend({}, data, true);
								modifiedData.children = children;
								return modifiedData;
							} else {
								const keywords = data?.text?.split(' ')?.map((keyword) => keyword.toLowerCase().trim());
								if (Array.isArray(keywords)) {
									keywords.push(data.id)
									const results = keywords.filter((keyword) => keyword.indexOf(search_terms) >= 0)
									if (results?.length > 0) {
										return data
									}
								}

								return null
							}
						}

						return data;
					}
				}).on('change', function () {
					self.value = $(this).val()
				})

				return;
			}

			$(this.$refs.select2_dropdown).select2({
				allowClear: true,
				placeholder: self.placeholder,
				dropdownCssClass: 'shipqora-select2-dropdown',
				ajax: {
					url: shipqora_admin.ajax_url,
					dataType: "json",
					type: "POST",
					delay: 500,
					data: function (params) {
						return {
							type: self.type,
							term: params.term,
							security: shipqora_admin?.select2?.nonce,
							action: 'shipqora/get_select2_dropdown_data'
						}
					},

					transport: function (params, success, failure) {
						const cache_key = JSON.stringify(params.data)
						const cache_data = self.$utils.get_cache_data(cache_key);
						if (cache_data) {
							return success(cache_data);
						}

						const request = $.ajax(params);
						request.then(async result => {
							self.$utils.set_cache_data(cache_key, result)
							ajax_response_data = result.data;
							success(result);
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