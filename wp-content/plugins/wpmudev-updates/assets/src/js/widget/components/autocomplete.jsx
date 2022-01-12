/* global wdpI18n */
import React from 'react'
import eventBus from './../helpers/event-bus'
import ajaxRequest from './../helpers/request'

export default class ContentOverview extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			loading: false,
			currentFilter: '',
			currentOption: 'pageviews'
		}

		this.input = React.createRef();

		// Bind events.
		this.handleChange = this.handleChange.bind(this)
	}

	/**
	 * After component is rendered.
	 *
	 * @since 4.11.6
	 */
	componentDidMount() {
		// Init auto complete.
		this.initAutocomplete()

		/**
		 * Handle analytics filter change.
		 *
		 * @param {object} data Data.
		 * @since 4.11.6
		 */
		eventBus.on('AnalyticsApplyFilter', (data) => {
				if ('autocomplete' !== data.type) {
					this.setState({
						currentFilter: data.label,
					})
					this.initAutocomplete()
				}
			}
		);
	}

	/**
	 * Set new value on filter value change.
	 *
	 * @param {object} prevProps Previous props.
	 * @param {object} prevState Previous state.
	 *
	 * @since 4.11.6
	 */
	componentDidUpdate(prevProps, prevState) {
		if (prevState.currentFilter !== this.state.currentFilter) {
			jQuery(this.input.current).val(this.state.currentFilter)
		}
	}

	/**
	 * Initialize autocomplete.
	 *
	 * Initialize jQuery autocomplete with posts, authors and sites
	 * data source.
	 *
	 * @since 4.11.6
	 */
	initAutocomplete() {
		const self = this
		jQuery(self.input.current).autocomplete({
			minLength: 2,
			source: this.props.source,
			select: async function (e, ui) {
				jQuery('.wpmudui-search-form .wpmudui-icon').remove();
				jQuery(self.input.current).before('<span class="wpmudui-icon wpmudui-icon-loader wpmudui-loading"></span>')
				.css('text-indent', '10px');

				// Get filtered stats.
				let response = await self.getFilteredStats({
					filter: ui.item.filter,
					type: ui.item.type,
				})

				// Process response.
				self.processResponse(response, {
					type: ui.item.type,
					filter: ui.item.filter,
				})

				e.preventDefault();
			}
		})
		.autocomplete('widget')
		.addClass('wpmudui-autocomplete-list');
	}

	/**
	 * Handle table row click event.
	 *
	 * Send an wp-ajax request and get the filtered content
	 * for the applied filters.
	 *
	 * @param {object} data Row data.
	 * @since 4.11.6
	 * @return {object}
	 */
	async getFilteredStats(data) {
		let response = {
			success: false,
			data: {}
		}
		await ajaxRequest(
			'wdp-analytics',
			{
				type: 'filtered',
				filter_type: data.type,
				filter_value: data.filter,
				range: this.props.period,
			}
		).then(json => {
			response = json
		})

		return response
	}

	/**
	 * Process the ajax request response.
	 *
	 * Show and hide icons based on the response.
	 *
	 * @param {object} response Response data.
	 * @param {object} filter Filter data.
	 * @since 4.11.6
	 */
	processResponse(response, filter) {
		let self = this
		// If request was success remove loader.
		if (response.success) {
			// Dispatch filter apply event.
			eventBus.dispatch('AnalyticsApplyFilter', {
				stats: response.data,
				type: 'autocomplete',
				filter: filter
			});

			// Hide loading icon.
			jQuery('.wpmudui-search-form .wpmudui-icon').fadeOut(400, function () {
				jQuery(self.input.current).css('text-indent', '0px');
			});
		} else {
			// Remove existing icon.
			jQuery('.wpmudui-search-form .wpmudui-icon').remove();
			// Show warning icon.
			jQuery(self.input.current)
			.before('<span class="wpmudui-icon dashicons dashicons-warning"></span>')
			.css('text-indent', '10px');
			// Hide warning icon.
			jQuery('.wpmudui-search-form .wpmudui-icon').fadeOut(2000, function () {
				jQuery(self.input.current).css('text-indent', '0px');
			});
		}
	}

	/**
	 * Handle change event.
	 *
	 * This is used only for clearing filter.
	 *
	 * @param {object} ev Event
	 * @since 4.11.6
	 */
	handleChange(ev) {
		this.setState({
			currentFilter: ev.target.value
		})

		if (ev.target.value === '') {
			// Dispatch event.
			eventBus.dispatch('AnalyticsFilterClear', {});
		}
	}

	render() {
		return (
			<>
				<label
					className="wpmudui-label"
					htmlFor="wpmudui-analytics-search"
				>{wdpI18n.labels.data_for}
				</label>
				<input
					type="search"
					size="1"
					placeholder="Full Site"
					id="wpmudui-analytics-search"
					className="wpmudui-input wpmudui-autocomplete"
					ref={this.input}
					onChange={this.handleChange}
				/>
			</>
		);
	}
}