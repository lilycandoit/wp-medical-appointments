<?php
/**
 * Admin Pages
 * Creates the admin menu and appointment listing page
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'wpma_register_admin_menu' );

/**
 * Register admin menu and submenus
 */
function wpma_register_admin_menu() {
    // Main menu
    add_menu_page(
        __( 'Medical Appointments', 'wp-medical-appointments' ),
        __( 'Medical Appointments', 'wp-medical-appointments' ),
        'manage_options',
        'wpma-appointments',
        'wpma_appointments_page',
        'dashicons-calendar-alt',
        30
    );

    // Appointments submenu (same as parent)
    add_submenu_page(
        'wpma-appointments',
        __( 'All Appointments', 'wp-medical-appointments' ),
        __( 'All Appointments', 'wp-medical-appointments' ),
        'manage_options',
        'wpma-appointments',
        'wpma_appointments_page'
    );

    // Doctors submenu - links to CPT
    add_submenu_page(
        'wpma-appointments',
        __( 'Doctors', 'wp-medical-appointments' ),
        __( 'Doctors', 'wp-medical-appointments' ),
        'manage_options',
        'edit.php?post_type=doctor'
    );

    // Add new doctor
    add_submenu_page(
        'wpma-appointments',
        __( 'Add New Doctor', 'wp-medical-appointments' ),
        __( 'Add New Doctor', 'wp-medical-appointments' ),
        'manage_options',
        'post-new.php?post_type=doctor'
    );
}

/**
 * Render the appointments listing page
 */
function wpma_appointments_page() {
    // Handle status filter
    $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

    // Build query args
    $args = array(
        'post_type'      => 'appointment',
        'posts_per_page' => 20,
        'paged'          => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // Add status filter if set
    if ( $status_filter ) {
        $args['meta_query'] = array(
            array(
                'key'   => '_wpma_status',
                'value' => $status_filter,
            ),
        );
    }

    $appointments = new WP_Query( $args );
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Appointments', 'wp-medical-appointments' ); ?></h1>
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=appointment' ) ); ?>" class="page-title-action">
            <?php esc_html_e( 'Add New', 'wp-medical-appointments' ); ?>
        </a>
        <hr class="wp-header-end">

        <!-- Status filter tabs -->
        <ul class="subsubsub">
            <li>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpma-appointments' ) ); ?>"
                   class="<?php echo empty( $status_filter ) ? 'current' : ''; ?>">
                    <?php esc_html_e( 'All', 'wp-medical-appointments' ); ?>
                </a> |
            </li>
            <li>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpma-appointments&status=pending' ) ); ?>"
                   class="<?php echo ( $status_filter === 'pending' ) ? 'current' : ''; ?>">
                    <?php esc_html_e( 'Pending', 'wp-medical-appointments' ); ?>
                </a> |
            </li>
            <li>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpma-appointments&status=confirmed' ) ); ?>"
                   class="<?php echo ( $status_filter === 'confirmed' ) ? 'current' : ''; ?>">
                    <?php esc_html_e( 'Confirmed', 'wp-medical-appointments' ); ?>
                </a> |
            </li>
            <li>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpma-appointments&status=completed' ) ); ?>"
                   class="<?php echo ( $status_filter === 'completed' ) ? 'current' : ''; ?>">
                    <?php esc_html_e( 'Completed', 'wp-medical-appointments' ); ?>
                </a> |
            </li>
            <li>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpma-appointments&status=cancelled' ) ); ?>"
                   class="<?php echo ( $status_filter === 'cancelled' ) ? 'current' : ''; ?>">
                    <?php esc_html_e( 'Cancelled', 'wp-medical-appointments' ); ?>
                </a>
            </li>
        </ul>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Patient', 'wp-medical-appointments' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'wp-medical-appointments' ); ?></th>
                    <th><?php esc_html_e( 'Phone', 'wp-medical-appointments' ); ?></th>
                    <th><?php esc_html_e( 'Doctor', 'wp-medical-appointments' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'wp-medical-appointments' ); ?></th>
                    <th><?php esc_html_e( 'Time', 'wp-medical-appointments' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'wp-medical-appointments' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'wp-medical-appointments' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $appointments->have_posts() ) : ?>
                    <?php while ( $appointments->have_posts() ) : $appointments->the_post(); ?>
                        <?php
                        $post_id       = get_the_ID();
                        $patient_name  = get_post_meta( $post_id, '_wpma_patient_name', true );
                        $patient_email = get_post_meta( $post_id, '_wpma_patient_email', true );
                        $patient_phone = get_post_meta( $post_id, '_wpma_patient_phone', true );
                        $doctor_id     = get_post_meta( $post_id, '_wpma_doctor_id', true );
                        $appt_date     = get_post_meta( $post_id, '_wpma_appointment_date', true );
                        $appt_time     = get_post_meta( $post_id, '_wpma_appointment_time', true );
                        $status        = get_post_meta( $post_id, '_wpma_status', true ) ?: 'pending';
                        $doctor_name   = $doctor_id ? get_the_title( $doctor_id ) : '—';
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $patient_name ); ?></strong></td>
                            <td><?php echo esc_html( $patient_email ); ?></td>
                            <td><?php echo esc_html( $patient_phone ); ?></td>
                            <td><?php echo esc_html( $doctor_name ); ?></td>
                            <td><?php echo esc_html( $appt_date ? date_i18n( get_option( 'date_format' ), strtotime( $appt_date ) ) : '—' ); ?></td>
                            <td><?php echo esc_html( $appt_time ?: '—' ); ?></td>
                            <td>
                                <span class="wpma-status wpma-status-<?php echo esc_attr( $status ); ?>">
                                    <?php echo esc_html( ucfirst( $status ) ); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
                                    <?php esc_html_e( 'Edit', 'wp-medical-appointments' ); ?>
                                </a> |
                                <a href="<?php echo esc_url( get_delete_post_link( $post_id ) ); ?>"
                                   onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'wp-medical-appointments' ); ?>');">
                                    <?php esc_html_e( 'Delete', 'wp-medical-appointments' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8"><?php esc_html_e( 'No appointments found.', 'wp-medical-appointments' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        // Pagination
        $total_pages = $appointments->max_num_pages;
        if ( $total_pages > 1 ) {
            $current_page = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
            echo '<div class="tablenav bottom"><div class="tablenav-pages">';
            echo paginate_links( array(
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'current'   => $current_page,
                'total'     => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ) );
            echo '</div></div>';
        }
        ?>
    </div>

    <style>
        .wpma-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
        }
        .wpma-status-pending { background: #fff3cd; color: #856404; }
        .wpma-status-confirmed { background: #d4edda; color: #155724; }
        .wpma-status-completed { background: #cce5ff; color: #004085; }
        .wpma-status-cancelled { background: #f8d7da; color: #721c24; }
    </style>
    <?php
}

/**
 * Highlight correct menu item when editing CPTs
 */
add_filter( 'parent_file', 'wpma_highlight_menu' );

function wpma_highlight_menu( $parent_file ) {
    global $current_screen;

    if ( in_array( $current_screen->post_type, array( 'doctor', 'appointment' ), true ) ) {
        $parent_file = 'wpma-appointments';
    }

    return $parent_file;
}
