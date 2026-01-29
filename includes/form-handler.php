<?php
/**
 * Form Handler
 * Handles frontend booking form submissions securely
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle form submission via admin-post.php
add_action( 'admin_post_wpma_submit_booking', 'wpma_handle_booking_submission' );
add_action( 'admin_post_nopriv_wpma_submit_booking', 'wpma_handle_booking_submission' );

/**
 * Process the booking form submission
 */
function wpma_handle_booking_submission() {
    // Get the referer URL for redirect
    $redirect_url = wp_get_referer();
    if ( ! $redirect_url ) {
        $redirect_url = home_url();
    }

    // Verify nonce
    if ( ! isset( $_POST['wpma_booking_nonce'] ) ||
         ! wp_verify_nonce( $_POST['wpma_booking_nonce'], 'wpma_booking_submit' ) ) {
        wp_safe_redirect( add_query_arg( 'wpma_booking', 'error', $redirect_url ) );
        exit;
    }

    // Validate required fields
    $required_fields = array(
        'wpma_patient_name',
        'wpma_patient_email',
        'wpma_patient_phone',
        'wpma_doctor_id',
        'wpma_appointment_date',
        'wpma_appointment_time',
    );

    foreach ( $required_fields as $field ) {
        if ( empty( $_POST[ $field ] ) ) {
            wp_safe_redirect( add_query_arg( 'wpma_booking', 'error', $redirect_url ) );
            exit;
        }
    }

    // Sanitize input data
    $patient_name  = sanitize_text_field( $_POST['wpma_patient_name'] );
    $patient_email = sanitize_email( $_POST['wpma_patient_email'] );
    $patient_phone = sanitize_text_field( $_POST['wpma_patient_phone'] );
    $doctor_id     = absint( $_POST['wpma_doctor_id'] );
    $appt_date     = sanitize_text_field( $_POST['wpma_appointment_date'] );
    $appt_time     = sanitize_text_field( $_POST['wpma_appointment_time'] );
    $notes         = isset( $_POST['wpma_notes'] ) ? sanitize_textarea_field( $_POST['wpma_notes'] ) : '';

    // Validate email format
    if ( ! is_email( $patient_email ) ) {
        wp_safe_redirect( add_query_arg( 'wpma_booking', 'error', $redirect_url ) );
        exit;
    }

    // Validate doctor exists
    $doctor = get_post( $doctor_id );
    if ( ! $doctor || $doctor->post_type !== 'doctor' ) {
        wp_safe_redirect( add_query_arg( 'wpma_booking', 'error', $redirect_url ) );
        exit;
    }

    // Validate date is not in the past
    if ( strtotime( $appt_date ) < strtotime( 'today' ) ) {
        wp_safe_redirect( add_query_arg( 'wpma_booking', 'error', $redirect_url ) );
        exit;
    }

    // Create the appointment post
    $appointment_title = sprintf(
        '%s - %s (%s)',
        $patient_name,
        get_the_title( $doctor_id ),
        $appt_date
    );

    $appointment_id = wp_insert_post( array(
        'post_type'   => 'appointment',
        'post_title'  => $appointment_title,
        'post_status' => 'publish',
    ) );

    if ( is_wp_error( $appointment_id ) ) {
        wp_safe_redirect( add_query_arg( 'wpma_booking', 'error', $redirect_url ) );
        exit;
    }

    // Save appointment meta data
    update_post_meta( $appointment_id, '_wpma_patient_name', $patient_name );
    update_post_meta( $appointment_id, '_wpma_patient_email', $patient_email );
    update_post_meta( $appointment_id, '_wpma_patient_phone', $patient_phone );
    update_post_meta( $appointment_id, '_wpma_doctor_id', $doctor_id );
    update_post_meta( $appointment_id, '_wpma_appointment_date', $appt_date );
    update_post_meta( $appointment_id, '_wpma_appointment_time', $appt_time );
    update_post_meta( $appointment_id, '_wpma_notes', $notes );
    update_post_meta( $appointment_id, '_wpma_status', 'pending' );

    // Optional: Send notification email to admin
    wpma_send_admin_notification( $appointment_id );

    // Redirect with success message
    wp_safe_redirect( add_query_arg( 'wpma_booking', 'success', $redirect_url ) );
    exit;
}

/**
 * Send email notification to admin when new appointment is booked
 */
function wpma_send_admin_notification( $appointment_id ) {
    $admin_email   = get_option( 'admin_email' );
    $patient_name  = get_post_meta( $appointment_id, '_wpma_patient_name', true );
    $patient_email = get_post_meta( $appointment_id, '_wpma_patient_email', true );
    $patient_phone = get_post_meta( $appointment_id, '_wpma_patient_phone', true );
    $doctor_id     = get_post_meta( $appointment_id, '_wpma_doctor_id', true );
    $appt_date     = get_post_meta( $appointment_id, '_wpma_appointment_date', true );
    $appt_time     = get_post_meta( $appointment_id, '_wpma_appointment_time', true );
    $notes         = get_post_meta( $appointment_id, '_wpma_notes', true );

    $doctor_name = $doctor_id ? get_the_title( $doctor_id ) : 'N/A';

    $subject = sprintf(
        __( '[%s] New Appointment Booking', 'wp-medical-appointments' ),
        get_bloginfo( 'name' )
    );

    $message = sprintf(
        __( "A new appointment has been booked.\n\nPatient: %s\nEmail: %s\nPhone: %s\nDoctor: %s\nDate: %s\nTime: %s\nNotes: %s\n\nView appointment: %s", 'wp-medical-appointments' ),
        $patient_name,
        $patient_email,
        $patient_phone,
        $doctor_name,
        $appt_date,
        $appt_time,
        $notes ?: 'None',
        admin_url( 'post.php?post=' . $appointment_id . '&action=edit' )
    );

    wp_mail( $admin_email, $subject, $message );
}
