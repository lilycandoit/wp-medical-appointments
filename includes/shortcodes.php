<?php
/**
 * Shortcodes
 * Frontend booking form shortcode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'medical_booking_form', 'wpma_booking_form_shortcode' );

/**
 * Render the booking form
 *
 * Usage: [medical_booking_form]
 */
function wpma_booking_form_shortcode( $atts ) {
    // Get all published doctors
    $doctors = get_posts( array(
        'post_type'      => 'doctor',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );

    // Check for success/error messages in session
    $message      = '';
    $message_type = '';

    if ( isset( $_GET['wpma_booking'] ) ) {
        if ( $_GET['wpma_booking'] === 'success' ) {
            $message      = __( 'Your appointment has been booked successfully. We will contact you to confirm.', 'wp-medical-appointments' );
            $message_type = 'success';
        } elseif ( $_GET['wpma_booking'] === 'error' ) {
            $message      = __( 'There was an error booking your appointment. Please try again.', 'wp-medical-appointments' );
            $message_type = 'error';
        }
    }

    // Start output buffering
    ob_start();
    ?>
    <div class="wpma-booking-form-wrapper">
        <?php if ( $message ) : ?>
            <div class="wpma-message wpma-message-<?php echo esc_attr( $message_type ); ?>">
                <?php echo esc_html( $message ); ?>
            </div>
        <?php endif; ?>

        <?php if ( empty( $doctors ) ) : ?>
            <p><?php esc_html_e( 'No doctors are currently available for booking.', 'wp-medical-appointments' ); ?></p>
        <?php else : ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpma-booking-form">
                <?php wp_nonce_field( 'wpma_booking_submit', 'wpma_booking_nonce' ); ?>
                <input type="hidden" name="action" value="wpma_submit_booking" />

                <div class="wpma-form-row">
                    <label for="wpma_patient_name">
                        <?php esc_html_e( 'Full Name', 'wp-medical-appointments' ); ?> <span class="required">*</span>
                    </label>
                    <input type="text" id="wpma_patient_name" name="wpma_patient_name" required />
                </div>

                <div class="wpma-form-row">
                    <label for="wpma_patient_email">
                        <?php esc_html_e( 'Email Address', 'wp-medical-appointments' ); ?> <span class="required">*</span>
                    </label>
                    <input type="email" id="wpma_patient_email" name="wpma_patient_email" required />
                </div>

                <div class="wpma-form-row">
                    <label for="wpma_patient_phone">
                        <?php esc_html_e( 'Phone Number', 'wp-medical-appointments' ); ?> <span class="required">*</span>
                    </label>
                    <input type="tel" id="wpma_patient_phone" name="wpma_patient_phone" required />
                </div>

                <div class="wpma-form-row">
                    <label for="wpma_doctor_id">
                        <?php esc_html_e( 'Select Doctor', 'wp-medical-appointments' ); ?> <span class="required">*</span>
                    </label>
                    <select id="wpma_doctor_id" name="wpma_doctor_id" required>
                        <option value=""><?php esc_html_e( '— Select a doctor —', 'wp-medical-appointments' ); ?></option>
                        <?php foreach ( $doctors as $doctor ) : ?>
                            <option value="<?php echo esc_attr( $doctor->ID ); ?>">
                                <?php echo esc_html( $doctor->post_title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="wpma-form-row">
                    <label for="wpma_appointment_date">
                        <?php esc_html_e( 'Preferred Date', 'wp-medical-appointments' ); ?> <span class="required">*</span>
                    </label>
                    <input type="date" id="wpma_appointment_date" name="wpma_appointment_date"
                           min="<?php echo esc_attr( date( 'Y-m-d' ) ); ?>" required />
                </div>

                <div class="wpma-form-row">
                    <label for="wpma_appointment_time">
                        <?php esc_html_e( 'Preferred Time', 'wp-medical-appointments' ); ?> <span class="required">*</span>
                    </label>
                    <input type="time" id="wpma_appointment_time" name="wpma_appointment_time" required />
                </div>

                <div class="wpma-form-row">
                    <label for="wpma_notes">
                        <?php esc_html_e( 'Additional Notes', 'wp-medical-appointments' ); ?>
                    </label>
                    <textarea id="wpma_notes" name="wpma_notes" rows="4"></textarea>
                </div>

                <div class="wpma-form-row">
                    <button type="submit" class="wpma-submit-btn">
                        <?php esc_html_e( 'Book Appointment', 'wp-medical-appointments' ); ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <style>
        .wpma-booking-form-wrapper {
            max-width: 500px;
            margin: 0 auto;
        }
        .wpma-booking-form .wpma-form-row {
            margin-bottom: 1rem;
        }
        .wpma-booking-form label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        .wpma-booking-form input[type="text"],
        .wpma-booking-form input[type="email"],
        .wpma-booking-form input[type="tel"],
        .wpma-booking-form input[type="date"],
        .wpma-booking-form input[type="time"],
        .wpma-booking-form select,
        .wpma-booking-form textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        .wpma-booking-form .required {
            color: #dc3545;
        }
        .wpma-submit-btn {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        .wpma-submit-btn:hover {
            background: #005a87;
        }
        .wpma-message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .wpma-message-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .wpma-message-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    <?php
    return ob_get_clean();
}
