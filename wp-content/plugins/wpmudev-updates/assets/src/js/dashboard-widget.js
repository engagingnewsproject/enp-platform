import '../scss/dashboard-widget.scss';
import React from 'react';
import ReactDOM from 'react-dom';
import domReady from '@wordpress/dom-ready';
import WidgetBody from './widget/widget-body'
import ErrorNotice from './widget/components/error-notice'

/* global wdp_analytics_ajax */
domReady(function () {
	// Analytics widget element.
	let analyticsWidget = document.getElementById('wpmudui-analytics-app')

	if ( analyticsWidget !== null ) {
		/**
		 * Check if we have analytics data available.
		 *
		 * @since 4.11.6
		 * @return {boolean}
		 */
		const hasData = () => {
			return wdp_analytics_ajax.current_data
				&& wdp_analytics_ajax.overall_data
		}

		/**
		 * Check if at least one metric is available.
		 *
		 * Unique pageviews alone can not be treated as a metric.
		 *
		 * @since 4.11.6
		 * @return {boolean}
		 */
		const hasMetrics = () => {
			// Available metrics.
			const metrics = wdpI18n.metrics.length > 0 ? wdpI18n.metrics : []
			// Remove unwanted metrics.
			return metrics.filter((metric) => 'unique_pageviews' !== metric.key).length > 0
		}

		const Widget = <div className="wpmudui-analytics">
			<div className="wpmudui-tabs">
				{hasData() && hasMetrics()
					? <WidgetBody/>
					: <ErrorNotice/>
				}
			</div>
		</div>;

		ReactDOM.render(
			Widget,
			document.getElementById('wpmudui-analytics-app')
		);
	}
});
