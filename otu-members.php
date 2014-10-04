<?php
/**
 * Plugin Name: OTU Members
 * Plugin URI: http://www.cbdweb.net
 * Description: Add extra fields for member records
 * Version: 1.0
 * Author: Nik Dow, CBDWeb
 * Author URI: http://www.cbdweb.net
 *
 */
defined('ABSPATH') or die("No script kiddies please!");

add_action( 'show_user_profile', 'add_otu_fields' );
add_action( 'edit_user_profile', 'add_otu_fields' );

function add_otu_fields( $user )
{
    ?>
        <h3>OTU data</h3>

        <table class="form-table">
            <tr>
                <th><label for="pmpro_regimental_number">Regimental Number</label></th>
                <td><input type="text" name="pmpro_regimental_number" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_regimental_number', $user->ID )); ?>" class="regular-text" />
                <br/>
                Warning - changing the regimental number or surname of an existing user will break things.
                <br/>
                Preferable to create a new user, then delete the old user and transfer their posts.
                </td>
            </tr>

            <tr>
                <th><label for="pmpro_baddress1">Address 1</label></th>
                <td><input type="text" name="pmpro_baddress1" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_baddress1', $user->ID )); ?>" class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="pmpro_baddress2">Address 2</label></th>
                <td><input type="text" name="pmpro_baddress2" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_baddress2', $user->ID )); ?>" class="regular-text" /></td>
            </tr>
            
            <tr>
                <th><label for="bpmpro_bcity">City</label></th>
                <td><input type="text" name="bpmpro_bcity" value="<?php echo esc_attr(get_the_author_meta( 'bpmpro_bcity', $user->ID )); ?>" class="regular-text" /></td>
            </tr>
            
            <tr>
                <th><label for="pmpro_bstate">State</label></th>
                <td><input type="text" name="pmpro_bstate" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_bstate', $user->ID )); ?>" class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="pmpro_bzipcode">Postcode</label></th>
                <td><input type="text" name="pmpro_bzipcode" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_bzipcode', $user->ID )); ?>" class="regular-text" /></td>
            </tr>

            <tr>
                <th><label for="pmpro_bphone">Home phone</label></th>
                <td><input type="text" name="pmpro_bphone" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_bphone', $user->ID )); ?>" class="regular-text" /></td>
            </tr>
                
            <tr>
                <th><label for="pmpro_bmobile">Mobile phone</label></th>
                <td><input type="text" name="pmpro_bmobile" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_bmobile', $user->ID )); ?>" class="regular-text" /></td>
            </tr>
                
            <tr>
                <th><label for="pmpro_bbusiness">Business phone</label></th>
                <td><input type="text" name="pmpro_bbusiness" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_bbusiness', $user->ID )); ?>" class="regular-text" /></td>
            </tr>
                
            <tr>
                <th><label for="pmpro_deceased">Deceased</label></th>
                <td><input type="checkbox" name="pmpro_deceased" value="1" <?php echo (get_the_author_meta( 'pmpro_deceased', $user->ID )==1 ? "checked" : ""); ?> /></td>
            </tr>
            
            <tr>
                <th><label for="pmpro_do_not_contact">Do not contact</label></th>
                <td><input type="checkbox" name="pmpro_do_not_contact" value="1" <?php echo (get_the_author_meta( 'pmpro_do_not_contact', $user->ID )==1 ? "checked" : ""); ?> /></td>
            </tr>
           
        </table>
    <?php
}

add_action( 'personal_options_update', 'save_otu_fields' );
add_action( 'edit_user_profile_update', 'save_otu_fields' );

function save_otu_fields( $user_id )
{
    update_user_meta( $user_id,'pmpro_regimental_number', sanitize_text_field( $_POST['pmpro_regimental_number'] ) );
    
    $user = get_userdata( $user_id );
    if( in_array('subscriber', $user->roles ) ) {
        // password is regimental number
        $hash = wp_hash_password( $_POST['pmpro_regimental_number'] );
        wp_update_user( array('ID'=>$user_id, 'user_pass'=>$hash, 'user_login' ) );
        // username is lastname_regimental number
        global $wpdb;
        $wpdb->update($wpdb->users, 
            array('user_login' => $_POST['last_name'] . "_" . $_POST['pmpro_regimental_number'] ),
            array( 'ID'=>$user_id ),
            array( '%s'),
            array( '%d' )
        );
    }
    update_user_meta( $user_id,'pmpro_baddress1', sanitize_text_field( $_POST['pmpro_baddress1'] ) );
    update_user_meta( $user_id,'pmpro_baddress2', sanitize_text_field( $_POST['pmpro_baddress2'] ) );
    update_user_meta( $user_id,'bpmpro_bcity', sanitize_text_field( $_POST['bpmpro_bcity'] ) );
    update_user_meta( $user_id,'pmpro_bstate', sanitize_text_field( $_POST['pmpro_bstate'] ) );
    update_user_meta( $user_id,'pmpro_bzipcode', sanitize_text_field( $_POST['pmpro_bzipcode'] ) );
    update_user_meta( $user_id,'pmpro_bphone', sanitize_text_field( $_POST['pmpro_bphone'] ) );
    update_user_meta( $user_id,'pmpro_bmobile', sanitize_text_field( $_POST['pmpro_bmobile'] ) );
    update_user_meta( $user_id,'pmpro_bbusiness', sanitize_text_field( $_POST['pmpro_bbusiness'] ) );
    if(isset($_POST['pmpro_deceased'])) {
        update_user_meta( $user_id,'pmpro_deceased', 1 );
    } else {
        update_user_meta( $user_id,'pmpro_deceased', 0 );
    }
    if(isset($_POST['pmpro_do_not_contact'])) {
        update_user_meta( $user_id,'pmpro_do_not_contact', 1 );
    } else {
        update_user_meta( $user_id,'pmpro_do_not_contact', 0 );
    }
}

function add_user_columns( $defaults ) {
     $defaults['deceased'] = __('Deceased', 'user-column');
     $defaults['do_not_contact'] = __('Do not contact', 'user-column');
     return $defaults;
}
function add_custom_user_columns($value, $column_name, $id) {
    $user = get_userdata( $id );
    switch ($column_name ) {
        case 'deceased':
            if($user->pmpro_deceased ) {
                return "<i class='fa fa-heart fa-1x pull-left'>";
            }         
            break;
        case 'do_not_contact':
            if ( $user->pmpro_do_not_contact ) {
                return "<i class='fa fa-heart pull-left'>";
            }
            break;
    }
}
add_action('manage_users_custom_column', 'add_custom_user_columns', 15, 3);
add_filter('manage_users_columns', 'add_user_columns', 15, 1);