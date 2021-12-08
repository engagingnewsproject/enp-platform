import React from 'react';
import HeaderRow from './rows/header'
import eventBus from './../helpers/event-bus';
import ajaxRequest from './../helpers/request';
import PeriodSelector from './../components/period-selector';
import MetricSelector from './../components/metric-selector';
import TablePagination from './../components/table-pagination';

export default class ListContent extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			currentMetric: '',
			currentPage: 1,
			list: [],
			perPage: 10,
			loading: false,
		}

		// Bind events.
		this.handleRowClick = this.handleRowClick.bind(this);
		this.handlePaginate = this.handlePaginate.bind(this);
		this.handleMetricChange = this.handleMetricChange.bind(this);
	}

	/**
	 * Setup required properties.
	 *
	 * @since 4.11.4
	 */
	componentDidMount() {
		let metrics = this.props.metrics;

		// Set current metric to first one.
		if (metrics.length > 0) {
			this.setState({
				currentMetric: metrics[0].key,
			})
		}

		// Paginate.
		this.paginate(0, this.state.perPage)
	}

	/**
	 * Paginate if required after update.
	 *
	 * @param {object} prevProps Previous props.
	 * @since 4.11.6
	 */
	componentDidUpdate(prevProps) {
		// Paginate if list changed.
		if (this.props.list !== prevProps.list) {
			this.setState({
				currentPage: 1
			})
			this.paginate(0, this.state.perPage)
		}
	}

	/**
	 * Handle metric item change event.
	 *
	 * @param {string} metric Metric key.
	 * @since 4.11.4
	 */
	handleMetricChange(metric) {
		this.setState({
			currentMetric: metric
		})
	}

	/**
	 * Set paginated list of items.
	 *
	 * @param {int} start Start position.
	 * @param {int} end End position.
	 * @since 4.11.6
	 */
	paginate(start, end) {
		// Set pagination.
		if (this.props.list.length > 0) {
			this.setState({
				list: this.props.list.slice(start, end),
			})
		}
	}

	/**
	 * Handle pagination event.
	 *
	 * Set current page when page navigation happens.
	 *
	 * @param {object} data Page data.
	 * @since 4.11.4
	 */
	handlePaginate(data) {
		this.setState({
			currentPage: data.page
		})

		// Paginate.
		this.paginate(data.start, data.end)
	}

	/**
	 * Handle table row click event.
	 *
	 * Send an wp-ajax request and get the filtered content
	 * for the applied filters.
	 *
	 * @param {object} data Row data.
	 * @since 4.11.4
	 */
	async handleRowClick(data) {
		// Set loader.
		this.setState({
			loading: data.name
		})

		await ajaxRequest(
			'wdp-analytics',
			{
				type: 'filtered',
				filter_type: data.type,
				filter_value: data.filter,
				range: this.props.period.current,
			}
		).then(response => {
			if (response.success) {
				// Dispatch filter apply event.
				eventBus.dispatch('AnalyticsApplyFilter', {
					stats: response.data,
					label: data.label,
					type: 'list',
					filter: {
						type: data.type,
						filter: data.filter,
					}
				});
			}
		});

		// Remove loader.
		this.setState({
			loading: ''
		})
	}

	/**
	 * Nothing to render as this is base component.
	 *
	 * @return {*}
	 * @since 4.11.4
	 */
	render() {
		const metrics = this.props.metrics;
		const ListRow = this.props.row;

		return (
			<div
				data-pane={this.props.type}
				className={`${this.props.active ? 'wpmudui-tab-current ' : ''}wpmudui-tab-content`}
			>
				<div className="wpmudui-search-form">
					<PeriodSelector
						type={this.props.type}
						period={this.props.period}
					/>

					<MetricSelector
						type="posts"
						metrics={metrics}
						onChange={this.handleMetricChange}
					/>
				</div>

				<div className="wpmudui-table-flushed">
					<table className="wpmudui-table">
						<thead>
						<HeaderRow
							title={this.props.title}
							metrics={metrics}
							currentMetric={this.state.currentMetric}
						/>
						</thead>

						<tbody className="wpmudui-table-sortable">
						{this.state.list.map((item) =>
							<ListRow
								key={item.name}
								item={item}
								metrics={metrics}
								current={this.state.currentMetric}
								onClick={this.handleRowClick}
								loading={this.state.loading === item.name}
							/>
						)}
						</tbody>
					</table>
					{this.props.list.length > this.state.perPage &&
					<TablePagination
						type={this.props.type}
						total={this.props.list.length}
						page={this.state.currentPage}
						perPage={this.state.perPage}
						paginate={this.handlePaginate}
					/>
					}
				</div>
			</div>
		);
	}
}