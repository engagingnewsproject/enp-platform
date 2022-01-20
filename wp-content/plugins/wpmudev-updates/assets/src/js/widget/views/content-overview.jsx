/* global wdp_analytics_ajax */
import React from 'react'
import moment from 'moment'
import ChartOptions from './overview/chart-options'
import ChartContent from './overview/chart-content'
import Autocomplete from './../components/autocomplete'
import PeriodSelector from './../components/period-selector'

export default class ContentOverview extends React.Component {
	constructor(props) {
		super(props);

		this.state = {
			loading: false,
			currentOption: this.resetCurrentMetric(),
		}

		// Register change event.
		this.handleOptionChange = this.handleOptionChange.bind(this);
	}

	/**
	 * After mounting the component.
	 *
	 * @since 4.11.6
	 */
	componentDidMount() {
		this.initMomentLocale();
	}

	/**
	 * After mounting the component.
	 *
	 * @since 4.11.6
	 */
	componentDidUpdate(prevProps) {
		if (prevProps.filterType !== this.props.filterType) {
			this.setState({
				currentOption: this.resetCurrentMetric()
			})
		}
	}

	/**
	 * Reset current metrics to first one.
	 *
	 * @since 4.11.7
	 */
	resetCurrentMetric() {
		const filterMetrics = this.filterMetrics()

		let currentOption = filterMetrics.length > 0 ? filterMetrics[0].key : ''
		// Do not show visit time for page stats.
		if (this.props.isFiltered && 'page' === this.props.filterType) {
			if (filterMetrics.filter(metric => metric.key === 'page_time').length > 0) {
				currentOption = 'page_time'
			}
		}

		return currentOption
	}

	/**
	 * Filter metrics based on the filter.
	 *
	 * @since 4.11.7
	 */
	filterMetrics() {
		// Remove unwanted metrics.
		let skipMetrics = ['unique_pageviews']
		// Do not show exit rate on full site view.
		if (!this.props.isFiltered && !wdp_analytics_ajax.subsite_flag) {
			skipMetrics.push('exit_rate')
		}
		// Do not show visit time for page stats.
		if (this.props.isFiltered && 'page' === this.props.filterType) {
			skipMetrics.push('visit_time')
		} else {
			skipMetrics.push('page_time')
		}

		return this.props.metrics.filter((metric) => !skipMetrics.includes(metric.key))
	}

	/**
	 * Setup locale for moment js to use it in chart.
	 *
	 * @since 4.11.6
	 */
	initMomentLocale() {
		moment.updateLocale(wdp_analytics_ajax.locale_settings.locale, {
			// Inherit anything missing from the default locale.
			parentLocale: moment.locale(),
			monthsShort: wdp_analytics_ajax.locale_settings.monthsShort,
			weekdays: wdp_analytics_ajax.locale_settings.weekdays
		});

		// Set the locale.
		moment.locale(wdp_analytics_ajax.locale_settings.locale);
	}

	/**
	 * Handle option change event.
	 *
	 * @param {string} option
	 * @since 4.11.4
	 */
	handleOptionChange(option) {
		this.setState({
			currentOption: option
		});
	}

	render() {
		let totals = {};
		let metrics = this.props.metrics;
		let skipMetrics = ['unique_pageviews']
		if (typeof this.props.data.totals !== 'undefined') {
			totals = this.props.data.totals;
		}

		// Do not show exit rate on full site view.
		if (!this.props.isFiltered && !wdp_analytics_ajax.subsite_flag) {
			skipMetrics.push('exit_rate');
		}

		// Remove unwanted metrics.
		metrics = metrics.filter(metric => !skipMetrics.includes(metric.key));

		return (
			<div
				data-pane="overview"
				className={`${this.props.currentTab === 'overview' ? 'wpmudui-tab-current ' : ''}wpmudui-tab-content`}
			>
				<div className="wpmudui-search-form">
					<PeriodSelector
						type="overview"
						period={this.props.period}
					/>

					<Autocomplete
						period={this.props.period.current}
						source={this.props.autocomplete}
					/>
				</div>

				<ChartContent
					data={this.props.data}
					current={this.state.currentOption}
				/>
				<ChartOptions
					totals={totals}
					options={metrics}
					current={this.state.currentOption}
					handleClick={this.handleOptionChange}
				/>
			</div>
		);
	}
}