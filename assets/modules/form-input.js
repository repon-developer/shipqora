const Input_Color = {
	template: '#shipflex-color-input',

	props: {
		color: {
			default: null
		}
	},

	emits: ['change'],

	data() {
		return {
			color_picker: null,
			current_color: this?.color || null,
		}
	},

	watch: {
		current_color(color_value) {
			this.$emit('change', color_value)
		}
	},

	mounted() {
		const color_swatches = Array(
			'rgba(244, 67, 54, 1)',
			'rgba(233, 30, 99, 0.95)',
			'rgba(156, 39, 176, 0.9)',
			'rgba(103, 58, 183, 0.85)',
			'rgba(63, 81, 181, 0.8)',
			'rgba(33, 150, 243, 0.75)',
			'rgba(3, 169, 244, 0.7)',
			'rgba(0, 188, 212, 0.7)'
		)

		const self = this;
		this.color_picker = Pickr.create({
			el: this.$refs['color-picker'],
			theme: 'monolith',
			appClass: 'shipflex-color-picker',
			swatches: [...new Set(color_swatches)],

			default: self.color,

			components: {
				opacity: true,
				hue: true,
				interaction: {
					hex: true,
					rgba: true,
					input: true,
					clear: true,
					save: false,
				}
			}
		}).on('change', (color, source, instance) => {
			instance.setColor(color.toHEXA().toString())
		}).on('swatchselect', (color, instance) => {
			instance.setColor(color.toHEXA().toString())
		}).on('clear', (instance) => {
			self.current_color = null
		}).on('save', (color) => {
			if (color) {
				self.current_color = color.toHEXA().toString();
			} else {
				self.current_color = null
			}
		})
	},

	unmounted() {
		if (this.color_picker) {
			this.color_picker.destroyAndRemove();
		}
	}
}

const Advanced_Color = {
	template: '#shipflex-advanced-color-input',

	props: {
		settings: {
			default: null,
			type: [null, Object]
		}
	},

	emits: ['updated'],

	data() {
		return {
			angle: '90',
			type: 'solid',
			solid_color: null,
			color_lines: Array(),
			...this.settings
		}
	},

	computed: {
		output_color() {
			const color_result = { background: this.solid_color }
			if (this.type == 'gradient') {
				const values = Array((this.angle || 0) + 'deg');

				this.color_lines.forEach((color_line) => {

					let color_value = '';
					if (color_line?.color) {
						color_value += color_line.color;
					}

					if (color_line?.position?.length > 0) {
						color_value += ' ' + color_line.position + '%';
					}

					if (color_value?.length) {
						values.push(color_value)
					}
				})

				color_result.background = 'linear-gradient(' + values.join(', ') + ')';
			}

			return color_result
		},

		wrapper_class() {
			let classes = 'shipflex-advanced-color-input-' + this.type;
			if (this.opened) {
				classes += ' ' + 'opened';
			}

			return classes;
		}

	},
	updated() {
		this.$emit('updated', this.$data);
	},

	methods: {
		add_color_line() {
			if (!Array.isArray(this.color_lines)) {
				this.color_lines = Array();
			}

			this.color_lines.push({ color: null, position: '' })
		},

		delete_color_line(line_no) {
			this.color_lines.splice(line_no, 1)
		}
	}
}

const Input_Border = {
	template: '#shipflex-border-input-component',
	props: {
		border: {
			type: Object,
			default: null,
		},
	},

	emits: ['on-update'],

	data() {
		return {
			style: 'none',
			width: '1px',
			color: null,
			...this.border
		}
	},

	created() {
		if (!this?.border?.style?.length) {
			this.style = 'none'
		}
	},

	updated() {
		this.$emit('on-update', this.$data);
	}
}

export { Input_Color, Advanced_Color, Input_Border };