<?php
/**
 * Shortcodes
 * Frontend shortcodes for booking, doctors list, and user appointments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register all shortcodes
add_shortcode( 'medical_booking_form', 'wpma_booking_form_shortcode' );
add_shortcode( 'doctor_list', 'wpma_doctor_list_shortcode' );
add_shortcode( 'my_appointments', 'wpma_my_appointments_shortcode' );

/**
 * Render the doctor list
 *
 * Usage: [doctor_list]
 * Attributes:
 *   - columns: Number of columns (default: 3)
 *   - limit: Number of doctors to show (default: -1 for all)
 */
function wpma_doctor_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'columns' => 3,
        'limit'   => -1,
    ), $atts, 'doctor_list' );

    $doctors = get_posts( array(
        'post_type'      => 'doctor',
        'posts_per_page' => intval( $atts['limit'] ),
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
    ) );

    if ( empty( $doctors ) ) {
        return '<p>' . esc_html__( 'No doctors found.', 'wp-medical-appointments' ) . '</p>';
    }

    ob_start();
    ?>
    <div class="wpma-doctors-grid" style="--columns: <?php echo esc_attr( $atts['columns'] ); ?>">
        <?php foreach ( $doctors as $doctor ) :
            $specialty = get_post_meta( $doctor->ID, '_wpma_specialty', true );
            $phone = get_post_meta( $doctor->ID, '_wpma_phone', true );
            $email = get_post_meta( $doctor->ID, '_wpma_email', true );
        ?>
            <div class="wpma-doctor-card">
                <div class="wpma-doctor-avatar">
                    <?php if ( has_post_thumbnail( $doctor->ID ) ) : ?>
                        <?php echo get_the_post_thumbnail( $doctor->ID, 'medium' ); ?>
                    <?php else : ?>
                        <div class="wpma-avatar-placeholder">
                            <?php echo esc_html( mb_substr( $doctor->post_title, 0, 2 ) ); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="wpma-doctor-info">
                    <h3 class="wpma-doctor-name"><?php echo esc_html( $doctor->post_title ); ?></h3>
                    <?php if ( $specialty ) : ?>
                        <p class="wpma-doctor-specialty"><?php echo esc_html( $specialty ); ?></p>
                    <?php endif; ?>
                    <?php if ( $doctor->post_content ) : ?>
                        <p class="wpma-doctor-bio"><?php echo esc_html( wp_trim_words( $doctor->post_content, 20 ) ); ?></p>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( home_url( '/book-appointment/' ) ); ?>" class="wpma-book-btn">
                        <?php esc_html_e( 'Book Appointment', 'wp-medical-appointments' ); ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <style>
        .wpma-doctors-grid {
            display: grid;
            grid-template-columns: repeat(var(--columns, 3), 1fr);
            gap: 2rem;
        }
        @media (max-width: 1024px) {
            .wpma-doctors-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .wpma-doctors-grid { grid-template-columns: 1fr; }
        }
        .wpma-doctor-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .wpma-doctor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .wpma-doctor-avatar {
            background: #f0f4f8;
            padding: 1.5rem;
            text-align: center;
        }
        .wpma-doctor-avatar img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto;
        }
        .wpma-avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #0077b6;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto;
            text-transform: uppercase;
        }
        .wpma-doctor-info {
            padding: 1.5rem;
            text-align: center;
        }
        .wpma-doctor-name {
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
            color: #1e293b;
        }
        .wpma-doctor-specialty {
            color: #0077b6;
            font-weight: 500;
            margin: 0 0 0.75rem;
        }
        .wpma-doctor-bio {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0 0 1rem;
        }
        .wpma-book-btn {
            display: inline-block;
            background: #0077b6;
            color: #fff;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .wpma-book-btn:hover {
            background: #005f92;
            color: #fff;
        }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Render user's appointments
 *
 * Usage: [my_appointments]
 */
function wpma_my_appointments_shortcode( $atts ) {
    if ( ! is_user_logged_in() ) {
        return '<div class="wpma-login-required"><p>' .
            esc_html__( 'Please log in to view your appointments.', 'wp-medical-appointments' ) .
            '</p><a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="wpma-login-btn">' .
            esc_html__( 'Log In', 'wp-medical-appointments' ) . '</a></div>';
    }

    $current_user = wp_get_current_user();
    $user_email = $current_user->user_email;

    // Get appointments for this user by email
    $appointments = get_posts( array(
        'post_type'      => 'appointment',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'   => '_wpma_patient_email',
                'value' => $user_email,
            ),
        ),
        'orderby'        => 'meta_value',
        'meta_key'       => '_wpma_appointment_date',
        'order'          => 'DESC',
    ) );

    if ( empty( $appointments ) ) {
        return '<div class="wpma-no-appointments"><p>' .
            esc_html__( 'You have no appointments yet.', 'wp-medical-appointments' ) .
            '</p><a href="' . esc_url( home_url( '/book-appointment/' ) ) . '" class="wpma-book-btn">' .
            esc_html__( 'Book an Appointment', 'wp-medical-appointments' ) . '</a></div>';
    }

    $today = date( 'Y-m-d' );
    $upcoming = array();
    $past = array();

    foreach ( $appointments as $appointment ) {
        $date = get_post_meta( $appointment->ID, '_wpma_appointment_date', true );
        if ( $date >= $today ) {
            $upcoming[] = $appointment;
        } else {
            $past[] = $appointment;
        }
    }

    ob_start();
    ?>
    <div class="wpma-my-appointments">
        <?php if ( ! empty( $upcoming ) ) : ?>
            <div class="wpma-appointments-section">
                <h3><?php esc_html_e( 'Upcoming Appointments', 'wp-medical-appointments' ); ?></h3>
                <?php foreach ( $upcoming as $appointment ) :
                    $date = get_post_meta( $appointment->ID, '_wpma_appointment_date', true );
                    $time = get_post_meta( $appointment->ID, '_wpma_appointment_time', true );
                    $doctor_id = get_post_meta( $appointment->ID, '_wpma_doctor_id', true );
                    $status = get_post_meta( $appointment->ID, '_wpma_status', true );
                    $doctor_name = $doctor_id ? get_the_title( $doctor_id ) : __( 'N/A', 'wp-medical-appointments' );
                ?>
                    <div class="wpma-appointment-card wpma-upcoming">
                        <div class="wpma-appointment-date">
                            <span class="wpma-day"><?php echo esc_html( date( 'd', strtotime( $date ) ) ); ?></span>
                            <span class="wpma-month"><?php echo esc_html( date( 'M', strtotime( $date ) ) ); ?></span>
                        </div>
                        <div class="wpma-appointment-details">
                            <h4><?php echo esc_html( $doctor_name ); ?></h4>
                            <p class="wpma-time"><?php echo esc_html( date( 'g:i A', strtotime( $time ) ) ); ?></p>
                        </div>
                        <div class="wpma-appointment-status">
                            <span class="wpma-status-badge wpma-status-<?php echo esc_attr( $status ); ?>">
                                <?php echo esc_html( ucfirst( $status ) ); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $past ) ) : ?>
            <div class="wpma-appointments-section">
                <h3><?php esc_html_e( 'Past Appointments', 'wp-medical-appointments' ); ?></h3>
                <?php foreach ( array_slice( $past, 0, 5 ) as $appointment ) :
                    $date = get_post_meta( $appointment->ID, '_wpma_appointment_date', true );
                    $time = get_post_meta( $appointment->ID, '_wpma_appointment_time', true );
                    $doctor_id = get_post_meta( $appointment->ID, '_wpma_doctor_id', true );
                    $status = get_post_meta( $appointment->ID, '_wpma_status', true );
                    $doctor_name = $doctor_id ? get_the_title( $doctor_id ) : __( 'N/A', 'wp-medical-appointments' );
                ?>
                    <div class="wpma-appointment-card wpma-past">
                        <div class="wpma-appointment-date">
                            <span class="wpma-day"><?php echo esc_html( date( 'd', strtotime( $date ) ) ); ?></span>
                            <span class="wpma-month"><?php echo esc_html( date( 'M', strtotime( $date ) ) ); ?></span>
                        </div>
                        <div class="wpma-appointment-details">
                            <h4><?php echo esc_html( $doctor_name ); ?></h4>
                            <p class="wpma-time"><?php echo esc_html( date( 'g:i A', strtotime( $time ) ) ); ?></p>
                        </div>
                        <div class="wpma-appointment-status">
                            <span class="wpma-status-badge wpma-status-completed">
                                <?php esc_html_e( 'Completed', 'wp-medical-appointments' ); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .wpma-my-appointments {
            max-width: 800px;
        }
        .wpma-appointments-section {
            margin-bottom: 2rem;
        }
        .wpma-appointments-section h3 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .wpma-appointment-card {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding: 1rem 1.5rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .wpma-appointment-card.wpma-past {
            opacity: 0.7;
        }
        .wpma-appointment-date {
            text-align: center;
            background: #0077b6;
            color: #fff;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            min-width: 60px;
        }
        .wpma-appointment-date .wpma-day {
            display: block;
            font-size: 1.5rem;
            font-weight: bold;
            line-height: 1;
        }
        .wpma-appointment-date .wpma-month {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .wpma-appointment-details {
            flex: 1;
        }
        .wpma-appointment-details h4 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
        }
        .wpma-appointment-details .wpma-time {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }
        .wpma-status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .wpma-status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .wpma-status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }
        .wpma-status-completed {
            background: #e2e8f0;
            color: #475569;
        }
        .wpma-status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }
        .wpma-login-required,
        .wpma-no-appointments {
            text-align: center;
            padding: 3rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        .wpma-login-btn,
        .wpma-no-appointments .wpma-book-btn {
            display: inline-block;
            background: #0077b6;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 1rem;
        }
        .wpma-login-btn:hover,
        .wpma-no-appointments .wpma-book-btn:hover {
            background: #005f92;
            color: #fff;
        }
    </style>
    <?php
    return ob_get_clean();
}

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

    // Check for success/error messages
    $show_success = isset( $_GET['wpma_booking'] ) && $_GET['wpma_booking'] === 'success';
    $show_error = isset( $_GET['wpma_booking'] ) && $_GET['wpma_booking'] === 'error';

    // Start output buffering
    ob_start();
    ?>
    <div class="wpma-booking-form-wrapper">
        <?php if ( $show_success ) : ?>
            <div class="wpma-message wpma-message-success">
                <h3><?php esc_html_e( 'Appointment Booked Successfully!', 'wp-medical-appointments' ); ?></h3>
                <p><?php esc_html_e( 'Thank you for booking with us. We will contact you shortly to confirm your appointment.', 'wp-medical-appointments' ); ?></p>
                <?php if ( is_user_logged_in() ) : ?>
                    <p>
                        <a href="<?php echo esc_url( home_url( '/my-appointments/' ) ); ?>" class="wpma-btn-link">
                            <?php esc_html_e( 'View My Appointments', 'wp-medical-appointments' ); ?> &rarr;
                        </a>
                    </p>
                <?php else : ?>
                    <p class="wpma-login-hint">
                        <?php
                        printf(
                            /* translators: %s: login URL */
                            esc_html__( 'Tip: %s with the same email to view and manage your appointments online.', 'wp-medical-appointments' ),
                            '<a href="' . esc_url( wp_login_url( home_url( '/my-appointments/' ) ) ) . '">' . esc_html__( 'Create an account or log in', 'wp-medical-appointments' ) . '</a>'
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ( $show_error ) : ?>
            <div class="wpma-message wpma-message-error">
                <?php esc_html_e( 'There was an error booking your appointment. Please try again.', 'wp-medical-appointments' ); ?>
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
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        .wpma-message h3 {
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
        }
        .wpma-message p {
            margin: 0 0 0.75rem;
        }
        .wpma-message p:last-child {
            margin-bottom: 0;
        }
        .wpma-message-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .wpma-message-success h3 {
            color: #047857;
        }
        .wpma-btn-link {
            color: #047857;
            font-weight: 600;
            text-decoration: none;
        }
        .wpma-btn-link:hover {
            text-decoration: underline;
        }
        .wpma-login-hint {
            font-size: 0.9rem;
            color: #047857;
            opacity: 0.9;
        }
        .wpma-login-hint a {
            color: #065f46;
            font-weight: 600;
        }
        .wpma-message-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
    <?php
    return ob_get_clean();
}
