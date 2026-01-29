# WP Medical Appointments

A minimal WordPress plugin for managing doctors and patient appointments.

## Features

- **Custom Post Types**: Doctors and Appointments
- **Admin Dashboard**: View and manage all appointments with status filtering
- **Frontend Booking Form**: Shortcode-based booking form for patients
- **Secure Form Handling**: Nonce verification, input sanitization
- **Email Notifications**: Admin receives email for new bookings

## Installation

1. Upload the `wp-medical-appointments` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Medical Appointments** in the admin menu

## Usage

### Adding Doctors

1. Navigate to **Medical Appointments > Add New Doctor**
2. Add the doctor's name as the title
3. Add bio/description in the editor
4. Set a featured image (optional)
5. Publish

### Viewing Appointments

1. Navigate to **Medical Appointments > All Appointments**
2. Filter by status: Pending, Confirmed, Completed, Cancelled
3. Click "Edit" to view full details and update status

### Frontend Booking Form

Add this shortcode to any page or post:

```
[medical_booking_form]
```

The form includes:
- Patient name, email, phone
- Doctor selection dropdown
- Date and time picker
- Notes field

## File Structure

```
wp-medical-appointments/
├── wp-medical-appointments.php   # Main plugin file
├── includes/
│   ├── post-types.php            # CPT registration and meta boxes
│   ├── admin-pages.php           # Admin menu and appointment list
│   ├── shortcodes.php            # Frontend booking form
│   └── form-handler.php          # Secure form processing
└── README.md
```

## Hooks & Filters

The plugin uses standard WordPress hooks:

- `init` - Register post types
- `admin_menu` - Register admin pages
- `add_meta_boxes` - Add appointment meta box
- `save_post_appointment` - Save meta data
- `admin_post_*` - Handle form submissions

## Security

- Nonce verification on all forms
- Input sanitization using WordPress functions
- Capability checks for admin actions
- Prepared statements via WordPress APIs

## Requirements

- WordPress 5.0+
- PHP 7.4+

## License

GPL v2 or later
