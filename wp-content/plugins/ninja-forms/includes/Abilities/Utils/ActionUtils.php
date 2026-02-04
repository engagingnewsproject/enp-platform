<?php
/**
 * Action Utility Functions for Ninja Forms Abilities
 *
 * This file contains all action-related execute callback functions.
 * These functions handle action CRUD operations and management.
 *
 * @package NinjaForms
 * @subpackage Abilities\Utils
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ninja_forms_ability_add_action( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}
	if ( empty( $input['type'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Action type is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];
	$type = sanitize_text_field( $input['type'] );

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Create new action
	$action = Ninja_Forms()->form( $form_id )->action()->get();

	// Set basic properties
	$action->update_setting( 'parent_id', $form_id );
	$action->update_setting( 'type', $type );
	$action->update_setting( 'label', ! empty( $input['label'] ) ? sanitize_text_field( $input['label'] ) : ucfirst( $type ) );
	$action->update_setting( 'active', isset( $input['active'] ) ? (int) $input['active'] : 1 );
	
	// Set type-specific settings based on action type
	if ( $type === 'email' ) {
		if ( ! empty( $input['to'] ) ) {
			$action->update_setting( 'to', sanitize_text_field( $input['to'] ) );
		}
		if ( ! empty( $input['subject'] ) ) {
			$action->update_setting( 'email_subject', sanitize_text_field( $input['subject'] ) );
		}
		if ( ! empty( $input['message'] ) ) {
			$action->update_setting( 'email_message', wp_kses_post( $input['message'] ) );
		}
		if ( ! empty( $input['from_name'] ) ) {
			$action->update_setting( 'from_name', sanitize_text_field( $input['from_name'] ) );
		}
		if ( ! empty( $input['from_address'] ) ) {
			$action->update_setting( 'from_address', sanitize_email( $input['from_address'] ) );
		}
		if ( ! empty( $input['reply_to'] ) ) {
			$action->update_setting( 'reply_to', sanitize_email( $input['reply_to'] ) );
		}
		if ( ! empty( $input['cc'] ) ) {
			$action->update_setting( 'cc', sanitize_text_field( $input['cc'] ) );
		}
		if ( ! empty( $input['bcc'] ) ) {
			$action->update_setting( 'bcc', sanitize_text_field( $input['bcc'] ) );
		}
	} elseif ( $type === 'redirect' ) {
		if ( ! empty( $input['redirect_url'] ) ) {
			$action->update_setting( 'redirect_url', esc_url_raw( $input['redirect_url'] ) );
		}
	} elseif ( $type === 'successmessage' ) {
		if ( ! empty( $input['success_msg'] ) ) {
			$action->update_setting( 'success_msg', wp_kses_post( $input['success_msg'] ) );
		}
	}

	// Save the action
	$action->save();
	$action_id = $action->get_id();

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success'   => true,
		'form_id'   => $form_id,
		'action_id' => $action_id,
		'type'      => $type,
		'message'   => sprintf( __( 'Successfully added %s action (ID: %d) to form %d.', 'ninja-forms' ), $type, $action_id, $form_id ),
	);
}

function ninja_forms_ability_update_action( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}
	if ( empty( $input['action_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Action ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];
	$action_id = (int) $input['action_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get the action
	$action = Ninja_Forms()->form( $form_id )->get_action( $action_id );
	if ( ! $action || ! $action->get_id() || ! $action->get_setting( 'type' ) ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Action with ID %d not found in form %d', 'ninja-forms' ), $action_id, $form_id ),
		);
	}

	$updated_count = 0;

	// Update label if provided
	if ( isset( $input['label'] ) ) {
		$action->update_setting( 'label', sanitize_text_field( $input['label'] ) );
		$updated_count++;
	}

	// Update active status if provided
	if ( isset( $input['active'] ) ) {
		$action->update_setting( 'active', (int) $input['active'] );
		$updated_count++;
	}

	// Update type-specific settings
	$action_type = $action->get_setting( 'type' );

	if ( $action_type === 'email' ) {
		if ( isset( $input['to'] ) ) {
			$action->update_setting( 'to', sanitize_text_field( $input['to'] ) );
			$updated_count++;
		}
		if ( isset( $input['subject'] ) ) {
			$action->update_setting( 'email_subject', sanitize_text_field( $input['subject'] ) );
			$updated_count++;
		}
		if ( isset( $input['message'] ) ) {
			$action->update_setting( 'email_message', wp_kses_post( $input['message'] ) );
			$updated_count++;
		}
		if ( isset( $input['from_name'] ) ) {
			$action->update_setting( 'from_name', sanitize_text_field( $input['from_name'] ) );
			$updated_count++;
		}
		if ( isset( $input['from_address'] ) ) {
			$action->update_setting( 'from_address', sanitize_email( $input['from_address'] ) );
			$updated_count++;
		}
		if ( isset( $input['reply_to'] ) ) {
			$action->update_setting( 'reply_to', sanitize_email( $input['reply_to'] ) );
			$updated_count++;
		}
		if ( isset( $input['cc'] ) ) {
			$action->update_setting( 'cc', sanitize_text_field( $input['cc'] ) );
			$updated_count++;
		}
		if ( isset( $input['bcc'] ) ) {
			$action->update_setting( 'bcc', sanitize_text_field( $input['bcc'] ) );
			$updated_count++;
		}
	} elseif ( $action_type === 'redirect' ) {
		if ( isset( $input['redirect_url'] ) ) {
			$action->update_setting( 'redirect_url', esc_url_raw( $input['redirect_url'] ) );
			$updated_count++;
		}
	} elseif ( $action_type === 'successmessage' ) {
		if ( isset( $input['success_msg'] ) ) {
			$action->update_setting( 'success_msg', wp_kses_post( $input['success_msg'] ) );
			$updated_count++;
		}
	}

	// Save the action
	$action->save();

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) && $form_id ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success'   => true,
		'action_id' => $action_id,
		'form_id'   => $form_id,
		'updated'   => $updated_count,
		'message'   => sprintf( __( 'Successfully updated action (ID: %d). Updated %d setting(s).', 'ninja-forms' ), $action_id, $updated_count ),
	);
}

function ninja_forms_ability_delete_action( $input ) {
	// Validate required inputs
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}
	if ( empty( $input['action_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Action ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];
	$action_id = (int) $input['action_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get the action
	$action = Ninja_Forms()->form( $form_id )->get_action( $action_id );
	if ( ! $action || ! $action->get_id() || ! $action->get_setting( 'type' ) ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Action with ID %d not found in form %d', 'ninja-forms' ), $action_id, $form_id ),
		);
	}
	$action_type = $action->get_setting( 'type' );
	$action_label = $action->get_setting( 'label' );

	// Delete the action
	$action->delete();

	// Clear cache
	if ( class_exists( 'WPN_Helper' ) && $form_id ) {
		WPN_Helper::delete_nf_cache( $form_id );
	}

	return array(
		'success'   => true,
		'action_id' => $action_id,
		'form_id'   => $form_id,
		'message'   => sprintf( __( 'Successfully deleted %s action "%s" (ID: %d) from form %d.', 'ninja-forms' ), $action_type, $action_label, $action_id, $form_id ),
	);
}

function ninja_forms_ability_list_actions( $input ) {
	// Validate required input
	if ( empty( $input['form_id'] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Form ID is required', 'ninja-forms' ),
		);
	}

	$form_id = (int) $input['form_id'];

	// Check if form exists
	$form = Ninja_Forms()->form( $form_id )->get();
	if ( ! $form || ! $form->get_id() ) {
		return array(
			'success' => false,
			'message' => sprintf( __( 'Form with ID %d not found', 'ninja-forms' ), $form_id ),
		);
	}

	// Get all actions for the form
	$actions = Ninja_Forms()->form( $form_id )->get_actions();
	$actions_data = array();

	foreach ( $actions as $action ) {
		$action_data = array(
			'id'     => $action->get_id(),
			'type'   => $action->get_setting( 'type' ),
			'label'  => $action->get_setting( 'label' ),
			'active' => $action->get_setting( 'active' ),
		);

		// Add type-specific settings
		$action_type = $action->get_setting( 'type' );
		if ( $action_type === 'email' ) {
			$action_data['to'] = $action->get_setting( 'to' );
			$action_data['subject'] = $action->get_setting( 'email_subject' );
		} elseif ( $action_type === 'redirect' ) {
			$action_data['redirect_url'] = $action->get_setting( 'redirect_url' );
		} elseif ( $action_type === 'successmessage' ) {
			$action_data['success_msg'] = $action->get_setting( 'success_msg' );
		}

		$actions_data[] = $action_data;
	}

	return array(
		'success' => true,
		'form_id' => $form_id,
		'count'   => count( $actions_data ),
		'actions' => $actions_data,
		'message' => sprintf( __( 'Found %d action(s) for form %d.', 'ninja-forms' ), count( $actions_data ), $form_id ),
	);
}