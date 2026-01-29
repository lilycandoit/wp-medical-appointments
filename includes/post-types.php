<?php
/**
 * Custom Post Types Registration
 * Registers 'doctor' and 'appointment' post types
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'wpma_register_post_types' );

/**
 * Register custom post types
 */
function wpma_register_post_types() {
    // Doctor CPT
    register_post_type( 'doctor', array(
        'labels' => array(
            'name'               => __( 'Doctors', 'wp-medical-appointments' ),
            'singular_name'      => __( 'Doctor', 'wp-medical-appointments' ),
            'add_new'            => __( 'Add New Doctor', 'wp-medical-appointments' ),
            'add_new_item'       => __( 'Add New Doctor', 'wp-medical-appointments' ),
            'edit_item'          => __( 'Edit Doctor', 'wp-medical-appointments' ),
            'view_item'          => __( 'View Doctor', 'wp-medical-appointments' ),
            'all_items'          => __( 'All Doctors', 'wp-medical-appointments' ),
            'search_items'       => __( 'Search Doctors', 'wp-medical-appointments' ),
            'not_found'          => __( 'No doctors found.', 'wp-medical-appointments' ),
        ),
        'public'             => true,
        'show_in_menu'       => false, // We'll add it under our custom menu
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'doctors' ),
        'menu_icon'          => 'dashicons-businessman',
    ) );

    // Appointment CPT
    register_post_type( 'appointment', array(
        'labels' => array(
            'name'               => __( 'Appointments', 'wp-medical-appointments' ),
            'singular_name'      => __( 'Appointment', 'wp-medical-appointments' ),
            'add_new'            => __( 'Add New Appointment', 'wp-medical-appointments' ),
            'add_new_item'       => __( 'Add New Appointment', 'wp-medical-appointments' ),
            'edit_item'          => __( 'Edit Appointment', 'wp-medical-appointments' ),
            'view_item'          => __( 'View Appointment', 'wp-medical-appointments' ),
            'all_items'          => __( 'All Appointments', 'wp-medical-appointments' ),
            'search_items'       => __( 'Search Appointments', 'wp-medical-appointments' ),
            'not_found'          => __( 'No appointments found.', 'wp-medical-appointments' ),
        ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'supports'           => array( 'title' ),
        'capability_type'    => 'post',
    ) );

    // Register meta fields for appointments
    wpma_register_meta_fields();
}

/**
 * Register meta fields for appointment post type
 */
function wpma_register_meta_fields() {
    $meta_fields = array(
        '_wpma_patient_name',
        '_wpma_patient_email',
        '_wpma_patient_phone',
        '_wpma_doctor_id',
        '_wpma_appointment_date',
        '_wpma_appointment_time',
        '_wpma_notes',
        '_wpma_status',
    );

    foreach ( $meta_fields as $field ) {
        register_post_meta( 'appointment', $field, array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'show_in_rest'      => false,
        ) );
    }
}

/**
 * Add meta box for appointment details
 */
add_action( 'add_meta_boxes', 'wpma_add_appointment_meta_box' );

function wpma_add_appointment_meta_box() {
    add_meta_box(
        'wpma_appointment_details',
        __( 'Appointment Details', 'wp-medical-appointments' ),
        'wpma_appointment_meta_box_callback',
        'appointment',
        'normal',
        'high'
    );
}

/**
 * Render appointment meta box
 */
function wpma_appointment_meta_box_callback( $post ) {
    wp_nonce_field( 'wpma_save_appointment', 'wpma_appointment_nonce' );

    $patient_name  = get_post_meta( $post->ID, '_wpma_patient_name', true );
    $patient_email = get_post_meta( $post->ID, '_wpma_patient_email', true );
    $patient_phone = get_post_meta( $post->ID, '_wpma_patient_phone', true );
    $doctor_id     = get_post_meta( $post->ID, '_wpma_doctor_id', true );
    $appt_date     = get_post_meta( $post->ID, '_wpma_appointment_date', true );
    $appt_time     = get_post_meta( $post->ID, '_wpma_appointment_time', true );
    $notes         = get_post_meta( $post->ID, '_wpma_notes', true );
    $status        = get_post_meta( $post->ID, '_wpma_status', true ) ?: 'pending';

    // Get all doctors
    $doctors = get_posts( array(
        'post_type'      => 'doctor',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ) );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="wpma_patient_name"><?php esc_html_e( 'Patient Name', 'wp-medical-appointments' ); ?></label></th>
            <td><input type="text" id="wpma_patient_name" name="wpma_patient_name" value="<?php echo esc_attr( $patient_name ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="wpma_patient_email"><?php esc_html_e( 'Patient Email', 'wp-medical-appointments' ); ?></label></th>
            <td><input type="email" id="wpma_patient_email" name="wpma_patient_email" value="<?php echo esc_attr( $patient_email ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="wpma_patient_phone"><?php esc_html_e( 'Patient Phone', 'wp-medical-appointments' ); ?></label></th>
            <td><input type="tel" id="wpma_patient_phone" name="wpma_patient_phone" value="<?php echo esc_attr( $patient_phone ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="wpma_doctor_id"><?php esc_html_e( 'Doctor', 'wp-medical-appointments' ); ?></label></th>
            <td>
                <select id="wpma_doctor_id" name="wpma_doctor_id">
                    <option value=""><?php esc_html_e( 'Select Doctor', 'wp-medical-appointments' ); ?></option>
                    <?php foreach ( $doctors as $doctor ) : ?>
                        <option value="<?php echo esc_attr( $doctor->ID ); ?>" <?php selected( $doctor_id, $doctor->ID ); ?>>
                            <?php echo esc_html( $doctor->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="wpma_appointment_date"><?php esc_html_e( 'Date', 'wp-medical-appointments' ); ?></label></th>
            <td><input type="date" id="wpma_appointment_date" name="wpma_appointment_date" value="<?php echo esc_attr( $appt_date ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="wpma_appointment_time"><?php esc_html_e( 'Time', 'wp-medical-appointments' ); ?></label></th>
            <td><input type="time" id="wpma_appointment_time" name="wpma_appointment_time" value="<?php echo esc_attr( $appt_time ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="wpma_status"><?php esc_html_e( 'Status', 'wp-medical-appointments' ); ?></label></th>
            <td>
                <select id="wpma_status" name="wpma_status">
                    <option value="pending" <?php selected( $status, 'pending' ); ?>><?php esc_html_e( 'Pending', 'wp-medical-appointments' ); ?></option>
                    <option value="confirmed" <?php selected( $status, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'wp-medical-appointments' ); ?></option>
                    <option value="completed" <?php selected( $status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'wp-medical-appointments' ); ?></option>
                    <option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'wp-medical-appointments' ); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="wpma_notes"><?php esc_html_e( 'Notes', 'wp-medical-appointments' ); ?></label></th>
            <td><textarea id="wpma_notes" name="wpma_notes" rows="4" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td>
        </tr>
    </table>
    <?php
}

/**
 * Save appointment meta box data
 */
add_action( 'save_post_appointment', 'wpma_save_appointment_meta' );

function wpma_save_appointment_meta( $post_id ) {
    // Security checks
    if ( ! isset( $_POST['wpma_appointment_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['wpma_appointment_nonce'], 'wpma_save_appointment' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save fields
    $fields = array(
        'wpma_patient_name'     => '_wpma_patient_name',
        'wpma_patient_email'    => '_wpma_patient_email',
        'wpma_patient_phone'    => '_wpma_patient_phone',
        'wpma_doctor_id'        => '_wpma_doctor_id',
        'wpma_appointment_date' => '_wpma_appointment_date',
        'wpma_appointment_time' => '_wpma_appointment_time',
        'wpma_notes'            => '_wpma_notes',
        'wpma_status'           => '_wpma_status',
    );

    foreach ( $fields as $field => $meta_key ) {
        if ( isset( $_POST[ $field ] ) ) {
            $value = ( $field === 'wpma_patient_email' )
                ? sanitize_email( $_POST[ $field ] )
                : sanitize_text_field( $_POST[ $field ] );
            update_post_meta( $post_id, $meta_key, $value );
        }
    }
}
