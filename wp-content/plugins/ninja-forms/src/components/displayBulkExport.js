import { Component, unmountComponentAtNode } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	CardFooter,
	Button,
	Spinner,
} from '@wordpress/components';
import {
	DisplayModal,
	triggerBulkExportAction,
	SelectForms,
	SelectDates,
	DisplayNotice,
} from './';

export class DisplayBulkExport extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			forms: false,
			dates: false,
			startDate: false,
			endDate: false,
			startDateTimestamp: false,
			endDateTimeStamp: false,
			isModalOpen: false,
			processing: false,
			exportSuccess: false,
			exportError: false,
			fetchAborted: false
		};
		this.setForms = this.setForms.bind( this );
		this.setDates = this.setDates.bind( this );
		this.closeModal = this.closeModal.bind( this );
		this.openModal = this.openModal.bind( this );
		this.setProcessing = this.setProcessing.bind( this );
		this.setExportSuccess = this.setExportSuccess.bind( this );
		this.setExportError = this.setExportError.bind( this );
		this.abortFetch = this.abortFetch.bind( this );
		this.cancelComp = this.cancelComp.bind(this);
	}

	//Component Ready event
	componentDidMount() {
		//Make sure there is no conflicts between components
		const emailComp = document.getElementById( 'nf-trigger-emails-container' );
		if( emailComp !== null ){
			unmountComponentAtNode(
				document.getElementById( 'nf-trigger-emails-container' )
			);
		}
	}

	//Prepare component to cancel all subscribtions before unmounting 
	componentWillUnmount() {
		this.setForms( false );
		//Make sure all jobs are stopped
		this.abortFetch();
	}

	//State Setters
	setForms( forms ) {
		this.setState( { forms: forms } );
	}
	setDates( dates ) {
		this.setState( { dates } );
		this.setState( { startDate: dates.date[ 0 ] } );
		this.setState( {
			startDateTimestamp: Math.round(
				new Date( this.state.startDate ).getTime() / 1000
			),
		} );
		this.setState( { endDate: dates.date[ 1 ] } );
		this.setState( {
			endDateTimeStamp: Math.round(
				new Date( this.state.endDate ).getTime() / 1000
			),
		} );
	}
	closeModal() {
		this.setState( { isModalOpen: false } );
	}
	openModal() {
		this.setState( { isModalOpen: true } );
	}
	setProcessing( value ) {
		this.setState( { processing: value } );
	}
	setExportSuccess() {
		this.setState( { exportSuccess: true } );
	}
	setExportError() {
		this.setState( { exportError: true } );
	}
	//Abort fetch controller
	abortFetch(){
		this.props.data.fetchController.abort();
		this.setState( { fetchAborted: true } );
	}
	//Cancel emails or Remove Component via cancel button
	cancelComp() {
		//Unmount component
		if(this.state.processing){
			//Close Modal
			this.closeModal();
			//exit Fetch operation
			this.setProcessing( false );
			this.abortFetch();
		} else {
			this.props.data.setClose();
		}
		
	}


	render() {
		const {
			state,
			props,
			cancelComp,
			setForms,
			setDates,
			setProcessing,
			setExportSuccess,
			setExportError,
		} = this;
		const {
			forms,
			dates,
			startDate,
			endDate,
			startDateTimestamp,
			endDateTimeStamp,
			isModalOpen,
			processing,
			exportSuccess,
			exportError,
			fetchAborted
		} = state;

		const { data } = props;
		const globalParams = data.props.globalParams;
		const setClose = data.setClose;
		const signal = data.fetchController.signal;

		//Get Forms Titles
		let formTitles = false;
		if ( forms ) {
			formTitles = [];
			forms.forEach( ( formID ) => {
				const form = globalParams.forms[ formID ];
				const title = ' ' + formID + ': ' + form.formTitle;
				formTitles.push( title );
			} );
		}

		//Props for process
		const actionProps = {
			globalParams,
			forms,
			startDateTimestamp,
			endDateTimeStamp,
			setProcessing,
			setExportSuccess,
			setExportError,
			signal
		};

		return (
			<div
				id="nf-DisplayBulkExport"
				style={ {
					marginTop: '1rem',
					position: 'relative',
					zIndex: '1111',
				} }
			>
				<Card isElevated>
					<CardHeader>
						<h3>{ __( 'Bulk Form Exports', 'ninja-forms' ) }</h3>
					</CardHeader>

					<CardBody>
						<p>{ __( 'Select Forms', 'ninja-forms' ) }</p>
						<SelectForms
							forms={ globalParams.forms }
							setForms={ setForms }
						/>

						<p>{ __( 'Select Dates', 'ninja-forms' ) }</p>
						<SelectDates
							setDates={ setDates }
							dateFormat={ globalParams.dateFormat }
						/>
						<div>
							{ formTitles && (
								<p style={ { margin: '1.2rem 0 0 0' } }>
									{ __( 'Forms Selected: ', 'ninja-forms' ) +
										formTitles }
								</p>
							) }
							{ startDate &&
								__( 'Start Date: ', 'ninja-forms' ) +
									startDate }
							<br />
							{ endDate &&
								__( 'End Date: ', 'ninja-forms' ) + endDate }
						</div>
					</CardBody>

					<CardFooter>
						<div>
							{ forms && dates && !fetchAborted && (
								<Button
									style={ { marginRight: '1rem' } }
									isPrimary
									onClick={ () => {
										triggerBulkExportAction( actionProps );
									} }
								>
									{ __( 'Export', 'ninja-forms' ) }
								</Button>
							) }
							<Button
								isSecondary
								onClick={ () => this.openModal() }
							>
								{ __( 'Cancel', 'ninja-forms' ) }
							</Button>
							{ isModalOpen && (
								<DisplayModal
									title={ __(
										'Cancel Bulk Export?',
										'ninja-forms'
									) }
									actionText={ __( 'Yes', 'ninja-forms' ) }
									action={ cancelComp }
									cancelText={ __( 'No', 'ninja-forms' ) }
									cancel={ () => this.closeModal() }
								/>
							) }

							{ processing && <Spinner /> }

							{ exportError && (
								<DisplayNotice
									status="error"
									isDismissible="false"
									text={ __(
										'Exports failed',
										'ninja-forms'
									) }
								/>
							) }

							{ exportSuccess && (
								<DisplayNotice
									isDismissible="false"
									text={ __(
										'Export Processed',
										'ninja-forms'
									) }
								/>
							) }

							{ fetchAborted && (
								<>
									<DisplayNotice
										isDismissible="false"
										status="error"
										text={ __(
											'Export Aborted',
											'ninja-forms'
										) }
									/>
									<Button
										isSecondary
										onClick={ () => setClose() }
									>
										{ __( 'Reopen to allow new bulk export process', 'ninja-forms' ) }
									</Button>
								</>
							) }
						</div>
					</CardFooter>
				</Card>
			</div>
		);
	}
}

DisplayBulkExport.propTypes = {
	data: PropTypes.object.isRequired,
}