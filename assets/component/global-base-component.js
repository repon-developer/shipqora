import { Utils } from '../utils.min.js?v=@@VERSION';
const { __ } = wp.i18n;

const Global_Base_Component = {

	computed: {
		...wp.hooks.applyFilters('shipflex.global_base_component.computed', {}),


	},

	methods: {
		...wp.hooks.applyFilters('shipflex.global_base_component.methods', {}),

		get_unit_label(unit_key, text, default_value = null) {
			let unit_label = default_value || __('unit', 'shipflex');
			if (shipflex_admin?.unit_labels?.[unit_key]?.length) {
				unit_label = shipflex_admin.unit_labels[unit_key];
			}

			if (unit_key == 'quantity') {
				text = text.replace('unit_label:upper_case', unit_label);
			}

			text = text.replace('unit_label:lower_case', unit_label.toLowerCase());
			text = text.replace('unit_label:upper_case', unit_label.toUpperCase());
			return text.replace('unit_label', unit_label)
		},

		add_collection(model_keys, default_value = {}) {
			if (!model_keys || !model_keys?.length) {
				throw new Error("Please pass 'Model Key' at first argument");
			}

			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				collections = []
			}

			collections?.push(default_value)
		},

		duplicate_collection(model_keys, data, position = 1) {
			if (!model_keys || !model_keys?.length) {
				throw new Error("Please pass 'Model Key' at first argument");
			}

			let collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				collections = []
			}

			collections.splice(position, 0, data)
		},

		delete_collection(model_keys, index_no) {
			const collections = model_keys.split('.').reduce((obj, key) => obj?.[key], this);
			if (!Array.isArray(collections)) {
				return;
			}

			collections.splice(index_no, 1)
		},

		add_condition_group() {
			this.add_collection('condition_groups', { id: Utils.generate_uuid() })
		},

		duplicate_condition_group(index_no) {
			this.delete_collection('condition_groups', index_no);
		},

		delete_condition_group(index_no) {
			this.delete_collection('condition_groups', index_no)
		}
	}
}

export default Global_Base_Component;