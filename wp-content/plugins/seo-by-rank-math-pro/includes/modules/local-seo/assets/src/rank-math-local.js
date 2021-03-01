/* global google, MarkerClusterer, navigator */
/**
 * External dependencies
 */
import $ from 'jquery'
import { isUndefined, forEach } from 'lodash'

class LocalMap {
	constructor() {
		this.markers = {}
		this.categorySelector = $( '#rank-math-select-category' )
		this.mapWrapper = $( '.rank-math-local-map' )
		this.storeLocator = $( '#rank-math-local-store-locator' )
		this.directionsWrapper = $( '.rank-math-directions-wrapper' )
		this.address = $( '#rank-math-search-address' )
		this.currentLocation = $( '#rank-math-current-location' )

		this.initMap()
		this.initStoreLocator()
		this.categoryFilter()
		this.detectLocation()
	}

	initMap() {
		if ( ! this.mapWrapper.length ) {
			return false
		}

		$( '.rank-math-local-map' ).each( ( key, value ) => {
			const mapOptions = $( value ).data( 'map-options' )
			if ( ! isUndefined( mapOptions ) ) {
				const id = 'rank-math-local-map-' + key
				$( value ).attr( 'id', id )
				$( value ).removeAttr( 'data-map-options' )
				this.initializeMap( id, mapOptions )
			}
		} )
	}

	initializeMap( id, mapOptions ) {
		const bounds = new google.maps.LatLngBounds()
		const locations = mapOptions.locations
		const setBound = Object.keys( locations ).length > 1
		const mapOptionsData = {
			zoom: parseInt( mapOptions.zoom_level ),
			zoomControl: mapOptions.allow_zoom,
			draggable: mapOptions.allow_dragging,
			mapTypeId: mapOptions.map_style,
		}

		if ( ! setBound ) {
			mapOptionsData.center = {
				lat: Number( locations[ Object.keys( locations )[ 0 ] ].lat ),
				lng: Number( locations[ Object.keys( locations )[ 0 ] ].lng ),
			}
		}

		const map = new google.maps.Map( document.getElementById( id ), mapOptionsData )
		const infowindow = new google.maps.InfoWindow()

		forEach( locations, ( value, key ) => {
			const marker = new google.maps.Marker( {
				position: new google.maps.LatLng( value.lat, value.lng ),
				map,
				type: value.terms,
			} )

			bounds.extend( marker.position )
			locations[ key ].content = value.content

			if ( mapOptions.show_infowindow ) {
				google.maps.event.addListener( marker, 'click', ( ( marker, key ) => {
					return () => {
						infowindow.setContent( locations[ key ].content )
						infowindow.setOptions( { maxWidth: 200 } )
						infowindow.open( map, marker )
					}
				} )( marker, key ) )
			}

			this.markers[ key ] = marker
		} )

		if ( setBound ) {
			map.fitBounds( bounds )
		}

		if ( mapOptions.show_clustering ) {
			this.markerClusterer( map )
		}

		this.getRoute( map )
	}

	markerClusterer( map ) {
		new MarkerClusterer(
			map,
			this.markers,
			{ imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m' }
		)
	}

	categoryFilter() {
		if ( ! this.categorySelector.length ) {
			return false
		}

		this.categorySelector.on( 'change', () => {
			const selectedValue = parseInt( this.categorySelector.val() )
			forEach( this.markers, ( value, key ) => {
				if ( isNaN( selectedValue ) ) {
					this.markers[ key ].setVisible( true )
					return
				}

				if ( isUndefined( value.type ) || -1 === $.inArray( selectedValue, value.type ) ) {
					this.markers[ key ].setVisible( false )
					return
				}

				this.markers[ key ].setVisible( true )
			} )
		} )
	}

	initStoreLocator() {
		if ( ! this.storeLocator.length ) {
			return
		}

		const geocoder = new google.maps.Geocoder()

		let count = 1
		$( '#rank-math-local-store-locator' ).on( 'submit', function( e ) {
			if ( 1 === count ) {
				e.preventDefault()

				const address = $( this ).find( '#rank-math-search-address' ).val()
				geocoder.geocode( { address }, ( results, status ) => {
					if ( 'OK' !== status ) {
						console.error( 'Geocode was not successful for the following reason: ' + status )
						return
					}

					$( this ).find( '#rank-math-lat' ).val( results[ 0 ].geometry.location.lat() )
					$( this ).find( '#rank-math-lng' ).val( results[ 0 ].geometry.location.lng() )

					count++
					$( this ).trigger( 'submit' )
				} )
				return false
			}
		} )
	}

	detectLocation() {
		if ( ! this.currentLocation.length || ! navigator.geolocation ) {
			return
		}

		this.currentLocation.on( 'click', ( e ) => {
			e.preventDefault()

			navigator.geolocation.getCurrentPosition( ( position ) => {
				const pos = {
					lat: parseFloat( position.coords.latitude ),
					lng: parseFloat( position.coords.longitude ),
				}
				const geocoder = new google.maps.Geocoder()
				geocoder.geocode( { location: pos }, ( results, status ) => {
					if ( 'OK' === status ) {
						this.address.val( results[ 0 ].formatted_address )
					}
				} )
			} )

			return false
		} )
	}

	getRoute( map ) {
		if ( ! this.directionsWrapper.length ) {
			return
		}

		this.directionsWrapper.find( '.rank-math-show-route' ).on( 'click', function( e ) {
			e.preventDefault()
			const buttonText = $( this ).data( 'toggle-text' )
			$( this ).data( 'toggle-text', $( this ).text() ).text( buttonText )
			$( this ).parent().toggleClass( 'show' )

			if ( ! $( this ).parent().find( '.rank-math-directions' ).html() ) {
				$( this ).parent().find( 'form' ).trigger( 'submit' )
			}
			return false
		} )

		const routeForm = $( '.rank-math-directions-wrapper' ).find( 'form' )
		const directionsRenderer = new google.maps.DirectionsRenderer()
		const directionsService = new google.maps.DirectionsService()
		directionsRenderer.setMap( map )

		routeForm.on( 'submit', ( e ) => {
			e.preventDefault()

			const directions = $( e.target ).parent().next( '.rank-math-directions' )
			const request = {
				origin: routeForm.find( '#rank-math-origin' ).val(),
				destination: new google.maps.LatLng( $( e.target ).find( '#rank-math-lat' ).val(), $( e.target ).find( '#rank-math-lng' ).val() ),
				travelMode: 'DRIVING',
				unitSystem: google.maps.UnitSystem.IMPERIAL,
			}

			directionsService.route( request, ( response, status ) => {
				if ( 'OK' === status ) {
					directionsRenderer.setDirections( response )
					directions.html( this.getDirectionsData( response ) )
				}
			} )

			return false
		} )
	}

	getDirectionsData( response ) {
		let data = ''
		const myRoute = response.routes[ 0 ].legs[ 0 ]

		data += '<h4>' + myRoute.start_address + '</h4>'
		data += '<ul>'
		forEach( myRoute.steps, ( value ) => {
			data += '<li class="' + value.maneuver + '">' + value.instructions + '</li>'
		} )
		data += '</ul>'
		data += '<h4>' + myRoute.end_address + '</h4>'

		data += '<h5><em>' + response.routes[ 0 ].copyrights + '</em><h5>'
		return data
	}
}

google.maps.event.addDomListener( window, 'load', new LocalMap() )
