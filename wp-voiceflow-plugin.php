<?php
/**
 * Plugin Name: VF2WP - Simple Voiceflow Integration by TESSA AI
 * Description: Integrate Voiceflow AI chatbots into WordPress effortlessly with VF2WP by TESSA, enhancing user engagement and customer support without coding.
 * Version: 1.0.0
 * Author: Tessa.tech
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define constants for file paths.
define( 'VOICEFLOW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'VOICEFLOW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Activation hook.
 */
function voiceflow_activation() {
    add_option( 'voiceflow_needs_registration', true );
    add_option( 'voiceflow_project_id', '' );
    add_option( 'voiceflow_section_id', '' );
}
register_activation_hook( __FILE__, 'voiceflow_activation' );

/**
 * Add admin pages.
 */
function voiceflow_add_admin_pages() {
    // Register the main settings page for the Voiceflow registration.
    add_options_page(
        __( 'Voiceflow Settings', 'text-domain' ),
        __( 'VF2WP', 'text-domain' ),
        'manage_options',
        'voiceflow_settings',
        'voiceflow_render_settings_page'
    );
}
add_action( 'admin_menu', 'voiceflow_add_admin_pages' );

/**
 * Register settings, sections, and fields.
 */
function voiceflow_register_settings() {
    register_setting( 'voiceflow_settings_group', 'voiceflow_project_id', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
    register_setting( 'voiceflow_settings_group', 'voiceflow_section_id', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );

    add_settings_section(
        'voiceflow_main', // ID.
        __( 'Main Settings', 'text-domain' ), // Title.
        null, // Callback function (optional).
        'voiceflow_settings' // Page.
    );

    add_settings_field(
        'voiceflow_project_id', // ID.
        __( 'Project ID', 'text-domain' ), // Title.
        'voiceflow_project_id_field_callback', // Callback function.
        'voiceflow_settings', // Page.
        'voiceflow_main' // Section.
    );

    add_settings_field(
        'voiceflow_section_id', // ID.
        __( 'Section ID', 'text-domain' ), // Title.
        'voiceflow_section_id_field_callback', // Callback function.
        'voiceflow_settings', // Page.
        'voiceflow_main' // Section.
    );
}
add_action( 'admin_init', 'voiceflow_register_settings' );

/**
 * Render settings page.
 */
function voiceflow_render_settings_page() {
    if ( get_option( 'voiceflow_needs_registration', true ) ) {
        // Show registration form.
        voiceflow_show_registration_form();
    } else {
        // Show settings form.
        voiceflow_show_plugin_setting_form();
    }
}

/**
 * Display registration form.
 */
function voiceflow_show_registration_form() {
    $errors   = array();
    $formData = array(
        'full_name'      => '',
        'title_position' => '',
        'email'          => '',
        'phone'          => '',
        'company_name'   => '',
    );

    if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
        foreach ( $formData as $key => $value ) {
            if ( isset( $_POST[ $key ] ) ) {
                $formData[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
            }
        }

        // Validation checks.
        if ( ! filter_var( $formData['email'], FILTER_VALIDATE_EMAIL ) ) {
            $errors[] = __( 'Invalid email address.', 'text-domain' );
        }
        if ( ! preg_match( '/^[0-9]*$/', $formData['phone'] ) ) {
            $errors[] = __( 'Phone number should contain only digits.', 'text-domain' );
        }
        if ( empty( $formData['full_name'] ) || empty( $formData['email'] ) || empty( $formData['company_name'] ) ) {
            $errors[] = __( 'Please fill all required fields.', 'text-domain' );
        }

        if ( count( $errors ) === 0 ) {
            // Save the data.
            foreach ( $formData as $key => $value ) {
                update_option( 'voiceflow_' . $key, $value );
            }
            update_option( 'voiceflow_needs_registration', false );

            // Prepare email content.
            $email_content = __( 'New Registration Details:', 'text-domain' ) . "\n";
            foreach ( $formData as $key => $value ) {
                $email_content .= ucfirst( str_replace( '_', ' ', $key ) ) . ': ' . $value . "\n";
            }
            $email_content .= 'Website URL: ' . get_site_url() . "\n";

            // Send data to your email.
            $recipients = 'sourabh@tessa.tech, sales@tessa.tech, kevincallen@tessa.tech';
            wp_mail( $recipients, __( 'New VF2WP Plugin Registration', 'text-domain' ), $email_content );

            // Refresh the page to show the settings form.
            echo '<script>location.reload();</script>';
            exit; // Ensure no further code is executed.
        }
    }

    // Display the form.
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Setup Voiceflow in WordPress', 'text-domain' ); ?></h1>
        <h2 class="description"><?php esc_html_e( 'Welcome! Please register your plugin to enable Voiceflow integration. Fields marked with * are required.', 'text-domain' ); ?></h2>
        <br />
        <h2><?php esc_html_e( 'Registrant Contact Information', 'text-domain' ); ?></h2>
        <?php if ( ! empty( $errors ) ) : ?>
            <div class="error">
                <?php foreach ( $errors as $error ) : ?>
                    <p><?php echo esc_html( $error ); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="full_name"><?php esc_html_e( 'Full Name *', 'text-domain' ); ?></label></th>
                    <td><input name="full_name" type="text" id="full_name" placeholder="<?php esc_attr_e( 'Please enter full name', 'text-domain' ); ?>" value="<?php echo esc_attr( $formData['full_name'] ); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="title_position"><?php esc_html_e( 'Title/Position', 'text-domain' ); ?></label></th>
                    <td><input name="title_position" type="text" id="title_position" placeholder="<?php esc_attr_e( 'Please enter your Title/Position in your company', 'text-domain' ); ?>" value="<?php echo esc_attr( $formData['title_position'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="email"><?php esc_html_e( 'Email *', 'text-domain' ); ?></label></th>
                    <td><input name="email" type="email" id="email" placeholder="<?php esc_attr_e( 'Please enter your email', 'text-domain' ); ?>" value="<?php echo esc_attr( $formData['email'] ); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="phone"><?php esc_html_e( 'Phone', 'text-domain' ); ?></label></th>
                    <td><input name="phone" type="tel" id="phone" placeholder="<?php esc_attr_e( 'Please enter your phone', 'text-domain' ); ?>" value="<?php echo esc_attr( $formData['phone'] ); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="company_name"><?php esc_html_e( 'Company Name *', 'text-domain' ); ?></label></th>
                    <td><input name="company_name" type="text" id="company_name" placeholder="<?php esc_attr_e( 'Please enter your company name', 'text-domain' ); ?>" value="<?php echo esc_attr( $formData['company_name'] ); ?>" class="regular-text" required></td>
                </tr>
            </table>
            <?php submit_button( __( 'Register', 'text-domain' ) ); ?>
        </form>
    </div>
    <?php
}

/**
 * Display settings form.
 */
function voiceflow_show_plugin_setting_form() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Setup Voiceflow in WordPress', 'text-domain' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'voiceflow_settings_group' ); // Register a settings group.
            do_settings_sections( 'voiceflow_settings' ); // Where the sections and fields are displayed.
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Callback for the Project ID field.
 */
function voiceflow_project_id_field_callback() {
    $project_id = get_option( 'voiceflow_project_id', '' );
    echo '<input type="text" id="voiceflow_project_id" name="voiceflow_project_id" placeholder="' . esc_attr__( 'Project ID', 'text-domain' ) . '" value="' . esc_attr( $project_id ) . '" />' .
         '<p>' . esc_html__( 'The Voiceflow project ID is an alphanumeric string provided in the Voiceflow agent\'s Settings, under General, and in the section, Metadata. It is also in the URL, e.g., https://creator.voiceflow.com/project/[YOUR_PROJECT_ID]/settings/general.', 'text-domain' ) . '</p>';
}

/**
 * Callback for the Section ID field.
 */
function voiceflow_section_id_field_callback() {
    $section_id = get_option( 'voiceflow_section_id', '' );
    echo '<input type="text" id="voiceflow_section_id" name="voiceflow_section_id" placeholder="' . esc_attr__( 'Section ID', 'text-domain' ) . '" value="' . esc_attr( $section_id ) . '" />' .
         '<p>' . esc_html__( 'The section ID is an alphanumeric string that identifies the ID of a section/div where you want to display the Voiceflow AI embed chatbox. Example: #divID. Only one ID can be added.', 'text-domain' ) . '</p>';
}

/**
 * Enqueue script.
 */
function voiceflow_enqueue_script() {
    if ( ! get_option( 'voiceflow_needs_registration', false ) ) {
        $project_id = get_option( 'voiceflow_project_id', '' );
        $section_id = get_option( 'voiceflow_section_id', '' );

        if ( $project_id ) {
            $inline_script = "
                (function(d, t) {
                    // Function to generate a unique ID for the user.
                    function generateUniqueId() {
                        if (!localStorage.getItem('voiceflowUserID')) {
                            const randomStr = Math.random().toString(36).substring(2, 8);
                            const dateTimeStr = new Date().toISOString().replace(/[-:]/g, '').replace(/\\.\\d+/g, '');
                            const uniqueID = randomStr + dateTimeStr;
                            localStorage.setItem('voiceflowUserID', uniqueID);
                        }
                        return localStorage.getItem('voiceflowUserID');
                    }

                    // Use the unique ID in the endpoint URL.
                    const userUniqueId = generateUniqueId();

                    var v = d.createElement(t), s = d.getElementsByTagName(t)[0];
                    v.onload = function() {
                        var targetElement = document.getElementById('{$section_id}');
                        if (targetElement) {
                            window.voiceflow.chat.load({
                                verify: { projectID: '{$project_id}' },
                                url: 'https://general-runtime.voiceflow.com',
                                versionID: 'production',
                                autostart: true,
                                userID: userUniqueId,
                                render: {
                                    mode: 'embedded',
                                    target: targetElement,
                                }
                            });
                        } else {
                            window.voiceflow.chat.load({
                                verify: { projectID: '{$project_id}' },
                                url: 'https://general-runtime.voiceflow.com',
                                versionID: 'production',
                                autostart: true,
                                userID: userUniqueId
                            });
                        }
                    };
                    v.src = 'https://cdn.voiceflow.com/widget/bundle.mjs';
                    v.type = 'text/javascript';
                    s.parentNode.insertBefore(v, s);
                })(document, 'script');
            ";

            wp_register_script( 'voiceflow-placeholder-script', '', [], false, true ); // Register an empty script with in_footer set to true.
            wp_enqueue_script( 'voiceflow-placeholder-script' ); // Enqueue the empty script.
            wp_add_inline_script( 'voiceflow-placeholder-script', $inline_script ); // Attach the inline script to the enqueued script.
        }
    }
}
add_action( 'wp_enqueue_scripts', 'voiceflow_enqueue_script' );

/**
 * Deactivation hook.
 */
function voiceflow_deactivation() {
    // No action needed on deactivation.
}
register_deactivation_hook( __FILE__, 'voiceflow_deactivation' );

/**
 * Uninstall cleanup function.
 */
function voiceflow_uninstall_cleanup() {
    // Delete plugin options.
    delete_option( 'voiceflow_project_id' );
    delete_option( 'voiceflow_section_id' );
    delete_option( 'voiceflow_needs_registration' );

    // Array of form keys to delete.
    $form_keys = array(
        'full_name',
        'title_position',
        'email',
        'phone',
        'company_name'
    );

    // Delete each option in the form keys array.
    foreach ( $form_keys as $key ) {
        delete_option( 'voiceflow_' . $key );
    }
}
register_uninstall_hook( __FILE__, 'voiceflow_uninstall_cleanup' );

?>