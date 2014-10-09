<?php
/*
    function my_additional_schedules($schedules) {
        // interval in seconds
        $schedules['every2min'] = array('interval' => 2*60, 'display' => 'Every two minutes');
        return $schedules;
    }
    add_filter('cron_schedules', 'my_additional_schedules'); */
if ( ! wp_get_schedule ( 'otu_reminder' ) ) {
// wp_clear_scheduled_hook('otu_reminder');
    $dt = new DateTime();
    $dt->setTimezone( new DateTimeZone ( get_option('timezone_string') ) );
    $str = $dt->format( 'Y-m-d 08:00:00' );
    $dt->createFromFormat('Y-m-d H:i:s', $str, new DateTimeZone( get_option('timezone_string' ) ) );
    wp_schedule_event(current_time('timestamp'), 'daily', 'otu_reminder');
}

function otu_reminder() {
    $dt = new DateTime();
    add_option('otu_reminder_sent', $dt->format('Y-m-d H:i') );
    echo 'otu_reminder ' . $dt->format('Y-m-d H:i' );
    global $wpdb;
    $query = "SELECT p.post_date as created, p.ID as ID, e.meta_value as email, s.meta_value as secret FROM $wpdb->posts p "
            . "LEFT JOIN $wpdb->postmeta m ON p.ID=m.post_id AND m.meta_key='reminder_sent' "
            . "LEFT JOIN $wpdb->postmeta e on p.ID=e.post_id AND e.meta_key='fs_signature_email' "
            . "LEFT JOIN $wpdb->postmeta s on p.ID=s.post_id AND s.meta_key='fs_signature_secret' "
            . "WHERE post_status=\"draft\" AND post_type=\"fs_signature\" AND m.meta_value IS NULL";
    $reminders = $wpdb->get_rows ( $query, OBJECT );
    $count = 0;
    $testing = true;
    $template = get_option( 'reminder-template' );
    foreach ( $reminders as $reminder ) {
        $createdDT->createFromFormat('Y-m-d H:i:s', $reminder->created, new DateTimeZone( get_option('timezone_string') ) );
        $todayDT = new DateTime();
        $daysago = $createdDT->diff( $todayDT );
        if ( $daysago > 3 ) {
            $content = str_replace( 
                    array( '{secret}',
                        ),
                    array( $reminder->secret,
                        ),
                    $template );
            $email = $reminder->email;
            if ( $testing ) $email = "nik@cbdweb.net";
            $subject = "Can you confirm your email so we can show your support?";
            if ( $testing ) $subject .= " - " . $email;
            $headers = array();
            $headers[] = 'From: "' . get_option('reminder-sender-name') . '" <' . get_option('reminder-sender-address') . '>';
            $headers[] = "Content-type: text/html";
            wp_mail( $email, $subject, $content, $headers );
            $count++;
            update_post_meta($reminder->ID, 'reminder_sent', 1);
        }
    }
    add_option( 'otu_reminder_count', $count );
}

add_action( 'admin_menu', 'reminder_menu' );

/** Step 1. */
function reminder_menu() {
        add_submenu_page( 'edit.php?post_type=fs_signature', 'Reminder Options', 'Options', 'manage_options', basename(__FILE__), 'reminder_options' );
}

/** Step 3. */
function reminder_options() {
        if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

            // variables for the field and option names 
            $hidden_field_name = 'fs_submit_hidden';
            $options_array = array ( 
                array('opt_name'=>'reminder-sender-name', 'data_field_name'=>'reminder_sender-name', 
                    'opt_label'=>'Reminder sender (common name)', 'field_type'=>'text'),
                array('opt_name'=>'reminder-sender-address', 'data_field_name'=>'reminder-sender-address', 
                    'opt_label'=>'Reminder sender (email address)', 'field_type'=>'email'),
                array('opt_name'=>'reminder-template', 'data_field_name'=>'reminder-template',
                    'opt_label'=>"HTML template for reminders:", 'field_type'=>'textarea' ),
            );

            // See if the user has posted us some information
            // If they did, this hidden field will be set to 'Y'
            if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

                foreach ($options_array as $option_array ) {
                    
                    // Read their posted value
                    $opt_val = stripslashes_deep ( $_POST[ $option_array['data_field_name'] ] );

                    // Save the posted value in the database
                    update_option( $option_array ['opt_name'], $opt_val );
                }

                // Put an settings updated message on the screen

                ?>
                <div class="updated"><p><strong><?php _e('settings saved.' ); ?></strong></p></div>
            <?php }

            // Now display the settings editing screen
            ?>
            <div class="wrap">

            <h2>Reminder Settings</h2>

            <form name="reminder_options" id="reminder_options" method="post" action="">
                <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

                <?php 
                foreach ( $options_array as $option_array ) { 
                    // Read in existing option value from database
                    $opt_val = get_option( $option_array[ 'opt_name' ] );
                    ?>
                    <p><?php _e( $option_array[ 'opt_label' ] );
                        if($option_array[ 'field_type' ] === 'textarea' ) { ?>
                            <textarea name="<?php echo $option_array[ 'data_field_name' ]; ?>"><?php echo $opt_val; ?></textarea>
                        <?php } else { ?>
                            <input type="<?=$option_array[ 'field_type' ]?>" name="<?=$option_array[ 'data_field_name' ]?>" value="<?=$opt_val?>"/>
                        <?php } ?>
                    </p>
                <?php } ?>
                <hr />

                <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
                </p>

            </form>
        </div>
    <?php
}
/*
 * Add link to menu bar for newsletters
 */
add_action( 'admin_bar_menu', 'toolbar_reminder_link', 999 );
function toolbar_reminder_link( $wp_admin_bar ) {
    $args = array ( 
        'id'=>'fs-signatures',
        'title'=>'Signatures',
        'parent'=>'site-name',
        'href'=>get_site_url() . '/wp-admin/edit.php?post_type=fs_signature',
    );

    $wp_admin_bar->add_node( $args );
}