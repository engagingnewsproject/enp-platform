/* global WPHB_Admin */
/* global google */

/**
 * Internal dependencies
 */
import { getLink } from '../utils/helpers';

( function( $ ) {
	WPHB_Admin.uptime = {
		module: 'uptime',
		$dataRangeSelector: null,
		chartData: null,
		downtimeChartData: null,
		timer: null,
		$spinner: null,
		dataRange: null,
		dateFormat: 'MMM d',
		init() {
			this.$spinner = $( '.spinner' );
			this.$dataRangeSelector = $( '#wphb-uptime-data-range' );
			this.chartData = $( '#uptime-chart-json' ).val();
			this.downtimeChartData = $( '#downtime-chart-json' ).val();
			this.$disableUptime = $( '#wphb-disable-uptime' );
			this.dataRange = this.getUrlParameter( 'data-range' );

			this.$dataRangeSelector.on( 'change', function() {
				window.location.href = $( this )
					.find( ':selected' )
					.data( 'url' );
			} );

			const self = this;

			if ( 'undefined' !== typeof google ) {
				google.charts.load( 'current', {
					packages: [ 'corechart', 'timeline' ],
				} );
			}

			this.$disableUptime.on( 'click', function( e ) {
				e.preventDefault();
				self.$spinner.css( 'visibility', 'visible' );
				const value = $( this ).is( ':checked' );
				if ( value && self.timer ) {
					clearTimeout( self.timer );
					self.$spinner.css( 'visibility', 'hidden' );
				} else {
					// you have 3 seconds to change your mind
					self.timer = setTimeout( function() {
						location.href = getLink( 'disableUptime' );
					}, 3000 );
				}
			} );

			/* If data range has been selected change the tab urls to retain the chosen range */
			if ( undefined !== this.dataRange ) {
				$( '.wrap-wphb-uptime .wphb-tab a' ).each( function() {
					this.href += '&data-range=' + self.dataRange;
				} );
			}

			if ( 'day' === this.dataRange ) {
				this.dateFormat = 'h:mma';
			}

			if ( null !== document.getElementById( 'uptime-chart' ) ) {
				google.charts.setOnLoadCallback( () =>
					this.drawResponseTimeChart()
				);
			}
			if ( null !== document.getElementById( 'downtime-chart' ) ) {
				google.charts.setOnLoadCallback( () =>
					this.drawDowntimeChart()
				);
			}

			/* Re-check Uptime status */
			$( '#uptime-re-check-status' ).on( 'click', function( e ) {
				e.preventDefault();
				location.reload();
			} );
		},

		drawResponseTimeChart() {
			const data = new google.visualization.DataTable();
			data.addColumn( 'datetime', 'Day' );
			data.addColumn( 'number', 'Response Time (ms)' );
			data.addColumn( {
				type: 'string',
				role: 'tooltip',
				p: { html: true },
			} );
			const chartArray = JSON.parse( this.chartData );
			for ( let i = 0; i < chartArray.length; i++ ) {
				chartArray[ i ][ 0 ] = new Date( chartArray[ i ][ 0 ] );
				chartArray[ i ][ 1 ] = Math.round( chartArray[ i ][ 1 ] );
				chartArray[ i ][ 2 ] = this.createUptimeTooltip(
					chartArray[ i ][ 0 ],
					chartArray[ i ][ 1 ]
				);

				/* brings the graph below the x axis */
				if ( Math.round( chartArray[ i ][ 1 ] ) === 0 ) {
					chartArray[ i ][ 1 ] = -100;
				}
			}

			data.addRows( chartArray );

			const options = {
				chartArea: {
					left: 80,
					top: 20,
					width: '90%',
					height: '90%',
				},
				colors: [ '#24ADE5' ],
				curveType: 'function',
				/*interpolateNulls: true,*/
				legend: { position: 'none' },
				vAxis: {
					format: '#### ms',
					gridlines: { count: 5 },
					minorGridlines: { count: 0 },
					viewWindow: { min: 0 } /* don't display negative values */,
				},
				hAxis: {
					format: this.dateFormat,
					minorGridlines: { count: 0 },
				},
				tooltip: { isHtml: true },
				series: {
					0: { axis: 'Resp' },
				},
				axes: {
					y: {
						Resp: { label: 'Response Time (ms)' },
					},
				},
			};

			const chart = new google.visualization.AreaChart(
				document.getElementById( 'uptime-chart' )
			);
			chart.draw( data, options );

			$( window ).resize( function() {
				chart.draw( data, options );
			} );
		},

		drawDowntimeChart() {
			const container = document.getElementById( 'downtime-chart' );
			const chart = new google.visualization.Timeline( container );
			const dataTable = new google.visualization.DataTable();
			dataTable.addColumn( { type: 'string' } );
			dataTable.addColumn( { type: 'string', id: 'Status' } );
			dataTable.addColumn( {
				type: 'string',
				role: 'tooltip',
				p: { html: true },
			} );
			dataTable.addColumn( { type: 'datetime', id: 'Start Period' } );
			dataTable.addColumn( { type: 'datetime', id: 'End Period' } );
			const chartArray = JSON.parse( this.downtimeChartData );
			for ( let i = 0; i < chartArray.length; i++ ) {
				chartArray[ i ][ 3 ] = new Date( chartArray[ i ][ 3 ] );
				chartArray[ i ][ 4 ] = new Date( chartArray[ i ][ 4 ] );
			}
			dataTable.addRows( chartArray );
			const colors = [];
			const colorMap = {
				// should contain a map of category -> color for every category
				Down: '#FF6D6D',
				Unknown: '#F8F8F8',
				Up: '#D1F1EA',
			};
			for ( let i = 0; i < dataTable.getNumberOfRows(); i++ ) {
				colors.push( colorMap[ dataTable.getValue( i, 1 ) ] );
			}
			const options = {
				timeline: {
					showBarLabels: false,
					showRowLabels: false,
					barLabelStyle: {
						fontSize: 33,
					},
					avoidOverlappingGridLines: false,
				},
				hAxis: {
					format: this.dateFormat,
				},
				colors,
				height: 170,
			};
			const origColors = [];
			google.visualization.events.addListener(
				chart,
				'ready',
				function() {
					const bars = container.getElementsByTagName( 'rect' );
					Array.prototype.forEach.call( bars, function( bar ) {
						if ( parseFloat( bar.getAttribute( 'x' ) ) > 0 ) {
							origColors.push( bar.getAttribute( 'fill' ) );
						}
					} );
				}
			);
			google.visualization.events.addListener(
				chart,
				'onmouseover',
				function( e ) {
					// set original color
					const bars = container.getElementsByTagName( 'rect' );
					bars[ bars.length - 1 ].setAttribute(
						'fill',
						origColors[ e.row ]
					);
					const width = bars[ bars.length - 1 ].getAttribute(
						'width'
					);
					if ( width > 3 ) {
						bars[ bars.length - 1 ].setAttribute(
							'width',
							width - 1 + 'px'
						);
					}
				}
			);
			chart.draw( dataTable, options );

			$( window ).resize( function() {
				chart.draw( dataTable, options );
			} );
		},

		createUptimeTooltip( date, responseTime ) {
			const formattedDate = this.formatTooltipDate( date );
			return (
				'<span class="response-time-tooltip">' +
				responseTime +
				'ms</span>' +
				'<span class="uptime-date-tooltip">' +
				formattedDate +
				'</span>'
			);
		},

		formatTooltipDate( date ) {
			const monthNames = [
				'Jan',
				'Feb',
				'Mar',
				'Apr',
				'May',
				'Jun',
				'Jul',
				'Aug',
				'Sep',
				'Oct',
				'Nov',
				'Dec',
			];

			const day = date.getDate();
			const monthIndex = date.getMonth();
			const hh = date.getHours();
			let h = hh;
			const minutes =
				( date.getMinutes() < 10 ? '0' : '' ) + date.getMinutes();
			let dd = 'AM';
			if ( h >= 12 ) {
				h = hh - 12;
				dd = 'PM';
			}
			if ( h === 0 ) {
				h = 12;
			}
			return (
				monthNames[ monthIndex ] +
				' ' +
				day +
				' @ ' +
				h +
				':' +
				minutes +
				dd
			);
		},

		getUrlParameter: function getUrlParameter( sParam ) {
			const sPageURL = decodeURIComponent(
					window.location.search.substring( 1 )
				),
				sURLVariables = sPageURL.split( '&' );
			let sParameterName, i;

			for ( i = 0; i < sURLVariables.length; i++ ) {
				sParameterName = sURLVariables[ i ].split( '=' );

				if ( sParameterName[ 0 ] === sParam ) {
					return sParameterName[ 1 ] === undefined
						? true
						: sParameterName[ 1 ];
				}
			}
		},
	};
} )( jQuery );
