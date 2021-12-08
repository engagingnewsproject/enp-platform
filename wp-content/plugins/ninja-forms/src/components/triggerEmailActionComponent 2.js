import { Component, unmountComponentAtNode } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardHeader,
	CardFooter,
	Spinner,
} from '@wordpress/components';
import {
	SelectAction,
	DisplayActionSettings,
	sendEmail,
	DisplayModal,
	DisplayNotice,
	selectScreenData,
} from './';

export class TriggerEmailActionComponent extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			form: false,
			submissionIds: false,
			action: false,
			processing: false,
			sent: -1,
			notSent: [],
			isModalOpen: false,
			emailsProcessed: 0,
			fetchAborted: false
		};
		this.setStateNewValue = this.setStateNewValue.bind(this);
		this.setModalOpen = this.setModalOpen.bind( this )
		this.setProcessing = this.setProcessing.bind( this );
		this.setSent = this.setSent.bind( this );
		this.setNotSent = this.setNotSent.bind( this );
		this.setEmailProcessed = this.setEmailProcessed.bind( this );
		this.cancelComp = this.cancelComp.bind( this );
		this.resetProcessing = this.resetProcessing.bind( this );
		this.abortFetch = this.abortFetch.bind(this);
		this.triggerEmailAction = this.triggerEmailAction.bind(this);		
	}

	//Component Ready event
	componentDidMount() {
		//gather props from screen
		selectScreenData( this.setStateNewValue, this.props );
	}
	//Catch when all submissions have been processed to set a new process state
	componentDidUpdate() {
		//Check process state to catch end of fetch tasks and display data
		if ( this.state.processing ) {
			if (
				typeof this.state.processing !== 'undefined' &&
				this.state.emailsProcessed === this.state.submissionIds.length
			) {
				//Set Process state to false without resetting data yet to have it displayed
				this.setProcessing( false );
			}
		}
	}
	//Prepare component to cancel all subscribtions before unmounting 
	componentWillUnmount() {
		//Make sure all jobs are stopped
		this.abortFetch();
		this.resetProcessing();
	}

	//Global state setter for states with new explicit value
	setStateNewValue( stateName, newValue ) {
		this.setState( { [stateName]: newValue } );
	}
	//Use a setter with an explicit name for the processing state
	setProcessing( newValue ) {
		this.setState( { processing: newValue } );
	}
	setModalOpen( newValue ) {
		this.setState( { isModalOpen: newValue } );
	}

	// Store number of emails sent
	setSent( value ) {
		//Check if reset process or increment
		if( value == "undefined" && value === 0 ){
			this.setState( { sent: -1 } );
		} else {
			this.setState( ( prev ) => { 
				return { sent: prev.sent + 1 } 
			});
		}
	}
	//Store IDs of unsent emails
	setNotSent(submissionId) {
		//Check if reset process or array push
		if(!submissionId){
			this.setState( { notSent: [] } );
		} else {
			const newNotSent = this.state.notSent.concat(submissionId);
			this.setState( { notSent: newNotSent } );
		}
	}
	//Store number of emails processed ( sent or not )
	setEmailProcessed( value ) {
		//Check if reset process or increment
		if( typeof value !== "undefined" && value === 0 ){
			this.setState( { emailsProcessed: 0 } );

		} else {
			this.setState( ( prev ) => { 
				return { emailsProcessed: prev.emailsProcessed + 1 } 
			});
		}
	}
	//Reset all state to default start of resending task
	resetProcessing() {
		this.setProcessing( false );
		this.setEmailProcessed( 0 );
		this.setSent( -1 );
		this.setNotSent( false );
	}
	//Abort fetch controller
	abortFetch(){
		this.props.fetchController.abort();
		this.setState( { fetchAborted: true });
	}

	//Cancel emails or Remove Component via cancel button
	cancelComp() {
		//Unmount component
		if(this.state.processing){
			//Close Modal
			this.setStateNewValue( "isModalOpen", false );
			//exit Fetch operation
			this.setProcessing( false );
			this.abortFetch();
		} else {
			unmountComponentAtNode(
				document.getElementById( 'nf-trigger-emails-container' )
			);
		}
		
	}

	//Trigger email task
	async triggerEmailAction() {
		//Init spinner, remove button and start the proces state management
		this.setProcessing( true );
		this.setSent( 0 );
		//Define data needed to Fetch request
		const { state, props } = this;
		const restUrl = props.globalParams.restUrl;
		const {
			form,
			submissionIds,
			action
		} = state;
		const formID = form.formID;
		const signal = props.fetchController.signal;
		//Props for process
		const actionProps = {
			restUrl,
			action,
			formID,
			setSent: this.setSent,
			setNotSent: this.setNotSent,
			setEmailProcessed: this.setEmailProcessed,
			signal
		};

		//Loop over each submission to trigger the email action
		for ( const [ key, value ] of Object.entries( submissionIds ) ) {
			await sendEmail( actionProps, value )
			.catch( (e) => {
				console.log( 'Email cancelled: ' + e.message );
				this.setNotSent( value );
			});
		}
	
	};


	render() {
		const { state } = this;
		const {
			form,
			submissionIds,
			action,
			processing,
			sent,
			notSent,
			isModalOpen,
			fetchAborted
		} = state;

		return (
			<>
				<Card isElevated>
					<br />
					<br />
					<CardHeader>
						{ form && (
							<SelectAction
								form={ form }
								setAction={ this.setStateNewValue }
							/>
						) }
					</CardHeader>

					<CardBody>
						{ action ? (
							<>
								<h3>
									{ __( 'Action Selected: ', 'ninja-forms' ) }
									{ action.label }
								</h3>
								<DisplayActionSettings value={ action } />
							</>
						) : (
							<p>
								{ __(
									'No Email Action Selected',
									'ninja-forms'
								) }
							</p>
						) }
					</CardBody>

					<CardFooter>
						<div>
							{fetchAborted && (
								<Button
									isPrimary
									onClick={ () =>	this.cancelComp() }
								>
									{ __( 'Reopen to allow resending process', 'ninja-forms' ) }
								</Button>
							)}
							{action && !processing && !fetchAborted && (
								<Button
									isPrimary
									onClick={ () =>
										this.triggerEmailAction()
									}
									style={ { marginRight: '1rem' } }
								>
									{ __( 'Resend ', 'ninja-forms' ) +
										submissionIds.length +
										__( ' emails', 'ninja-forms' ) }
								</Button>
							)}
							{!fetchAborted && 
								<Button
									isSecondary
									onClick={ () => this.setModalOpen( true ) }
								>
									{ __( 'Cancel', 'ninja-forms' ) }
								</Button>
							}
							{ isModalOpen && (
								<DisplayModal
									action={ this.cancelComp }
									title={ __(
										'Cancel Email Action?',
										'ninja-forms'
									) }
									actionText={ __( 'Yes', 'ninja-forms' ) }
									cancel={ this.setModalOpen }
									cancelText={ __( 'No', 'ninja-forms' ) }
								/>
							) }

							{ processing && <Spinner /> }

							{ notSent.length > 0 && (
								<DisplayNotice
									status="error"
									isDismissible="false"
									text={
										__(
											'Emails failed to be sent for: ',
											'ninja-forms'
										) + Object.values( notSent )
									}
								/>
							) }

							{ sent >= 0 && (
								<DisplayNotice
									isDismissible="false"
									text={
										sent +
										' / ' +
										submissionIds.length +
										__( ' emails sent', 'ninja-forms' )
									}
								/>
							) }
						</div>
					</CardFooter>
				</Card>
			</>
		);
	}
}

TriggerEmailActionComponent.propTypes = {
	globalParams: PropTypes.object.isRequired,
	triggerEmailActionsParams: PropTypes.object.isRequired,
	fetchController:  PropTypes.object.isRequired
}