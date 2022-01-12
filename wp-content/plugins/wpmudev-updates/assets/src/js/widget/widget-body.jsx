/* global wdp_analytics_ajax, wdpI18n */
import React from 'react';
import PagesRow from './views/rows/pages'
import SitesRow from './views/rows/sites'
import eventBus from './helpers/event-bus'
import ajaxRequest from './helpers/request'
import AuthorsRow from './views/rows/authors'
import ContentList from './views/content-list'
import HeaderTabs from './components/header-tabs'
import ContentOverview from './views/content-overview'

export default class WidgetBody extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			overall: wdp_analytics_ajax.overall_data,
			overview: wdp_analytics_ajax.current_data,
			pages: wdp_analytics_ajax.pages,
			authors: wdp_analytics_ajax.authors,
			sites: wdp_analytics_ajax.sites,
			autocomplete: wdp_analytics_ajax.autocomplete,
			currentTab: 'overview',
			currentPeriod: 7,
			currentFilter: '',
			currentFilterType: '',
			periodLoading: false,
			period: {
				current: 7,
				loading: false
			}
		}
	}

	componentDidMount() {
		/**
		 * Handle autocomplete clear event.
		 *
		 * @param {object} data Data.
		 * @since 4.11.4
		 */
		eventBus.on('AnalyticsFilterClear', (data) =>
			this.setState({
				overview: this.state.overall,
				currentFilter: '',
				currentFilterType: '',
			})
		);

		/**
		 * Handle period change event.
		 *
		 * @param {string} period
		 * @since 4.11.4
		 */
		eventBus.on('AnalyticsPeriodChange', (data) => this.getPeriodStats(data.period));

		/**
		 * Handle analytics filter change.
		 *
		 * @param {object} data Data.
		 * @since 4.11.4
		 */
		eventBus.on('AnalyticsApplyFilter', (data) => {
				if ('autocomplete' === data.type) {
					this.setState({
						overview: data.stats,
						currentFilter: data.filter.filter,
						currentFilterType: data.filter.type
					})
				} else {
					this.setState({
						currentTab: 'overview',
						overview: data.stats,
						currentFilter: data.filter.filter,
						currentFilterType: data.filter.type
					})
				}
			}
		);
	}

	componentWillUnmount() {
		// Remove custom events.
		eventBus.remove('AnalyticsPeriodChange');
		eventBus.remove('AnalyticsApplyFilter');
		eventBus.remove('AnalyticsFilterClear');
	}

	/**
	 * Get overall stats for period changes.
	 *
	 * Make sure to update all stats. Keep all active
	 * filters.
	 *
	 * @param {int} period Period.
	 * @since 4.11.4
	 */
	async getPeriodStats(period) {
		this.setState({
			period: {
				...this.state.period,
				loading: true
			}
		})

		await ajaxRequest(
			'wdp-analytics',
			{
				type: 'full',
				range: period,
				filter_value: this.state.currentFilter,
				filter_type: this.state.currentFilterType
			}
		).then(response => {
			if (response.success) {
				this.setState({
					period: {
						...this.state.period,
						current: period
					},
					overview: response.data.current_data,
					overall: response.data.overall_data,
					pages: response.data.pages,
					authors: response.data.authors,
					sites: response.data.sites,
					autocomplete: response.data.autocomplete
				})
			}
		});

		this.setState({
			period: {
				...this.state.period,
				loading: false
			}
		})
	}

	/**
	 * Get available tabs for header.
	 *
	 * @since 4.11.5
	 * @return {{overview: string, pages: string, sites: string, authors: string}}
	 */
	getTabs() {
		let items = wdpI18n.tabs;

		Object.keys(items).forEach((tab) => {
			if ('overview' !== tab && this.state[tab].length <= 0) {
				delete items[tab]
			}
		})

		return items;
	}

	render() {
		return (
			<>
				<HeaderTabs
					tabs={this.getTabs()}
					current={this.state.currentTab}
					onChange={(tab) => this.setState({
						currentTab: tab
					})}
				/>

				<div
					className="wpmudui-analytics-content"
					data-panes
				>
					<ContentOverview
						metrics={wdpI18n.metrics}
						data={this.state.overview}
						currentTab={this.state.currentTab}
						autocomplete={this.state.autocomplete}
						period={this.state.period}
						isFiltered={this.state.currentFilter !== ''}
						filterType={this.state.currentFilterType}
					/>
					{this.state.pages.length > 0 &&
					<ContentList
						type="pages"
						title={wdpI18n.labels.page_post}
						metrics={wdpI18n.metrics}
						list={this.state.pages}
						active={'pages' === this.state.currentTab}
						row={PagesRow}
						period={this.state.period}
					/>
					}
					{this.state.authors.length > 0 &&
					<ContentList
						type="authors"
						title={wdpI18n.labels.author}
						metrics={wdpI18n.metrics}
						list={this.state.authors}
						active={'authors' === this.state.currentTab}
						row={AuthorsRow}
						period={this.state.period}
					/>}
					{this.state.sites.length > 0 &&
					<ContentList
						type="sites"
						title={wdpI18n.labels.site_domain}
						metrics={wdpI18n.metrics}
						list={this.state.sites}
						active={'sites' === this.state.currentTab}
						row={SitesRow}
						period={this.state.period}
					/>
					}
				</div>
			</>
		);
	}
}