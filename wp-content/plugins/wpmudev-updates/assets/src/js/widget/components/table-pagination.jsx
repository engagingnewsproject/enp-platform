/* global wdpI18n */
import React from 'react'

export default class TablePagination extends React.Component {
	constructor(props) {
		super(props);
		this.state = {
			numPages: Math.ceil(this.props.total / this.props.perPage)
		}

		// Setup events.
		this.navigateTo = this.navigateTo.bind(this);
		this.nextPageClick = this.nextPageClick.bind(this);
		this.prevPageClick = this.prevPageClick.bind(this);
	}

	/**
	 * Handle next page click event.
	 *
	 * Set current page to next page after validation.
	 *
	 * @since 4.11.6
	 */
	nextPageClick() {
		let page = this.props.page + 1;

		// Continue only if valid.
		if (page < 1 || page > this.state.numPages) {
			return;
		}

		// Dispatch paginate event.
		this.dispatchPaginate(page)
	}

	/**
	 * Handle previous page click event.
	 *
	 * Set current page to previous page after validation.
	 *
	 * @since 4.11.6
	 */
	prevPageClick() {
		let page = this.props.page - 1;

		// Continue only if valid.
		if (page < 1 || page > this.state.numPages) {
			return;
		}

		// Dispatch paginate event.
		this.dispatchPaginate(page)
	}

	/**
	 * Handle manual pagination event.
	 *
	 * When the page number is manually set, process pagination.
	 *
	 * @param {object} ev Event.
	 * @since 4.11.6
	 */
	navigateTo(ev) {
		let page = ev.target.value;

		// Continue only if valid.
		if (page < 1 || page > this.state.numPages) {
			return;
		}

		// Dispatch paginate event.
		this.dispatchPaginate(page)
	}

	/**
	 * Dispatch pagination change event.
	 *
	 * @param {number} page Page no.
	 * @since 4.11.6
	 */
	dispatchPaginate(page) {
		// Dispatch paginate event.
		this.props.paginate({
			page: page,
			type: this.props.type,
			total: this.props.total,
			pages: this.state.numPages,
			start: (page - 1) * this.props.perPage,
			end: page * this.props.perPage
		});
	}

	render() {
		return (
			<div
				className="wpmudui-pagination wpmudui-pagination-wrapper"
				data-current-page="1"
			>
				<label
					htmlFor={`wpmdui-pagination-search-${this.props.type}`}
					className="wpmudui-label"
				>{wdpI18n.labels.goto}
				</label>
				<input
					type="number"
					placeholder="1"
					min="1"
					max={this.state.numPages}
					id={`wpmdui-pagination-search-${this.props.type}`}
					className="wpmudui-input wpmudui-goto-page"
					onChange={this.navigateTo}
				/>
				<div className="wpmudui-navigation">
					<label className="wpmudui-label">
						<span className="wpmudui-start-row">
							{(this.props.page - 1) * this.props.perPage + 1}
						</span>&nbsp;-&nbsp;
						<span className="wpmudui-end-row">
							{this.props.page * this.props.perPage}
						</span>&nbsp;
						{wdpI18n.labels.of} {this.props.total}
					</label>

					<button
						className="wpmudui-button wpmudui-button-icon wpmudui-page-prev"
						disabled={this.props.page <= 1}
						onClick={this.prevPageClick}
					>
						<i className="wpmudui-icon-chevron-left" aria-hidden="true">
						</i>
					</button>
					<button
						className="wpmudui-button wpmudui-button-icon wpmudui-page-next"
						disabled={this.props.page >= this.state.numPages}
						onClick={this.nextPageClick}
					>
						<i className="wpmudui-icon-chevron-right" aria-hidden="true">
						</i>
					</button>
				</div>
			</div>
		);
	}
}