import domReady from '@wordpress/dom-ready';
import {
	unmountComponentAtNode,
	createElement,
	render,
} from '@wordpress/element';
import {
	TriggerEmailActionComponent,
	TriggerBulkExportComponent,
} from './components';
import {
	globalParams,
	triggerEmailActionsParams,
	bulkExportParams,
} from './params';

domReady( function() {
	const selectTopElement =
			document.querySelector( '#bulk-action-selector-top' ) !== null
				? document.querySelector( '#bulk-action-selector-top' )
				: 0,
		selectBottomElement =
			document.querySelector( '#bulk-action-selector-bottom' ) !== null
				? document.querySelector( '#bulk-action-selector-bottom' )
				: 0;

	//Check if a Form Bulk action selector exists
	if ( selectTopElement.length > 0 && selectBottomElement.length > 0 ) {
		//Re-Trigger Email Actions feature
		openComponentOnSelection( selectTopElement, selectBottomElement );
		interceptFormSubmit();
	}

	//Bulk Export Accross Forms feature
	bulkExportAccrossForms();
} );

const generateKey = () => {
    return `nf_${ new Date().getTime() }`;
}

//Open Component during Selection
const openComponentOnSelection = ( selectTopElement, selectBottomElement ) => {
	//Add a change event listener on the selectors
	[ selectTopElement, selectBottomElement ].forEach( ( el ) => {
		const position = el.id.includes( 'top' )
			? jQuery( '.tablenav.top' )
			: jQuery( '.tablenav.bottom' );
		el.addEventListener( 'change', ( event ) => {
			if ( event.target.value === 'trigger-email-action' ) {
				//Open Email Actions component
				triggerEmailAction( position );
			}
		} );
	} );
};

//Intercept Form Submission
const interceptFormSubmit = () => {
	//Intercept submission of bulk actions
	jQuery( '#posts-filter' ).on( 'submit', function( e ) {
		//Check the Form submitter and value of the bulk select field
		const doaction1 =
			jQuery( '#bulk-action-selector-top' )[ 0 ].value ===
				'trigger-email-action' &&
			e.originalEvent.submitter.id === 'doaction';
		const doaction2 =
			jQuery( '#bulk-action-selector-bottom' )[ 0 ].value ===
				'trigger-email-action' &&
			e.originalEvent.submitter.id === 'doaction2';
		if ( doaction1 || doaction2 ) {
			//Stop PHP redirection process
			e.preventDefault();
			const position = doaction1
				? jQuery( '.tablenav.top' )
				: jQuery( '.tablenav.bottom' );
			triggerEmailAction( position );
		}
	} );
};

//Component for trigger Email Action feature
const triggerEmailAction = ( position ) => {
	const compDetect = document.getElementById( 'nf-trigger-emails-container' );
	if ( compDetect !== null ) {
		unmountComponentAtNode( compDetect );
	}
	//Set props
	const params = {
		globalParams,
		triggerEmailActionsParams,
		key: generateKey(),
		fetchController: new AbortController()
	};
	//Create new element on the NF submissions page to trigger Email actions
	const triggerEmailActionsContainer = document.createElement( 'div' );
	triggerEmailActionsContainer.id = 'nf-trigger-emails-container';
	position.after( triggerEmailActionsContainer );
	//Render component in the new element created
	const triggerEmailActionsElement = createElement(
		TriggerEmailActionComponent,
		params
	);
	render(
		triggerEmailActionsElement,
		document.getElementById( 'nf-trigger-emails-container' )
	);
};

//Component for Bulk Export Accross Forms feature
const bulkExportAccrossForms = () => {
	//Set props
	const params = {
		key: generateKey(),
		globalParams,
		bulkExportParams,
	};
	const pos = jQuery( '.tablenav.bottom .actions' );
	const bulkExportContainer = document.createElement( 'div' );
	bulkExportContainer.id = 'nf-bulk-export-container';
	pos.after( bulkExportContainer );

	//Render component in the new element created
	const bulkExportElement = createElement(
		TriggerBulkExportComponent,
		params
	);
	render(
		bulkExportElement,
		document.getElementById( 'nf-bulk-export-container' )
	);
};
