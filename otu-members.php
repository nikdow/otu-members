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

require_once plugin_dir_path ( __FILE__ ) . 'memberlist.php';
// require_once plugin_dir_path( __FILE__ ) . 'importAvatars.php';

add_action ( 'user_register', 'register_otu_fields' );

function register_otu_fields( $user_id ) {
    
    $username_bits = explode( '_', $_POST['user_login'] );
    update_user_meta($user_id, 'pmpro_regimental_number', $username_bits[1] );
    
}


add_action( 'show_user_profile', 'add_otu_fields' );
add_action( 'edit_user_profile', 'add_otu_fields' );

function add_otu_fields( $user )
{
    ?>
        <h3>OTU data</h3>

        <table class="form-table">
            
            <tr>
                <th><label for="committee">Committee Member</label></th>
                <td><input style="width: 16px;" type="checkbox" name="committee" <?=get_the_author_meta( 'committee', $user->ID ) ? "checked" : "";?> class="regular-text" />
                <br/>
                Ticked here will show on the home page committee avatars
                </td>
            </tr>
            
            <tr>
                <th><label for="pmpro_regimental_number">Regimental Number</label></th>
                <td><input type="text" name="pmpro_regimental_number" DISABLED value="<?php echo esc_attr(get_the_author_meta( 'pmpro_regimental_number', $user->ID )); ?>" class="regular-text" />
                <br/>
                Warning - changing the regimental number or surname of an existing user will break things.
                </td>
            </tr>
            
            <tr>
                <th>Change surname or Regimental Number</th>
                <td>
                    <b>Change either or both to copy this user's data into a new user record</b><br/>
                    Regimental no: <input type="text" name="new_regimental_number" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_regimental_number', $user->ID )); ?>" class="regular-text" />
                    Surname: <input type="text" name="new_last_name" value="<?php echo esc_attr(get_the_author_meta( 'last_name', $user->ID )); ?>" class="regular-text" />
                    <br/>
                    <button type="button" onClick="copyUser('<?=$user->ID?>')">Change</button>
                    <br/>
                    <span id='copyUserOutput'>This works by making a copy of the user, then deletes the old user while transferring any posts.</span>
                </td>
            </tr>
                        
            <tr>
                <th><label for="pmpro_class">Class</label></th>
                <td><input type="text" name="pmpro_class" value="<?php echo str_replace ( "//", "/", esc_attr(get_the_author_meta( 'pmpro_class', $user->ID ))); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="middlename">Middlename</label></th>
                <td><input type="text" name="middlename" value="<?php echo esc_attr(get_the_author_meta( 'middlename', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="corps">Corps</label></th>
                <td><input type="text" name="corps" value="<?php echo esc_attr(get_the_author_meta( 'corps', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="graduateno">Graduate No</label></th>
                <td><input type="text" name="graduateno" value="<?php echo esc_attr(get_the_author_meta( 'graduateno', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="directing">Directing</label></th>
                <td><input type="text" name="directing" value="<?php echo esc_attr(get_the_author_meta( 'directing', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="vietnam">Vietnam</label></th>
                <td><input type="text" name="vietnam" value="<?php echo esc_attr(get_the_author_meta( 'vietnam', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="industry">Industry</label></th>
                <td><input type="text" name="industry" value="<?php echo esc_attr(get_the_author_meta( 'industry', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="company">Company</label></th>
                <td><input type="text" name="company" value="<?php echo esc_attr(get_the_author_meta( 'company', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="occupation">Occupation</label></th>
                <td><input type="text" name="occupation" value="<?php echo esc_attr(get_the_author_meta( 'occupation', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="interests">Interests</label></th>
                <td><input type="text" name="interests" value="<?php echo esc_attr(get_the_author_meta( 'interests', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th><label for="otuposition">OTU position</label></th>
                <td><input type="text" name="otuposition" value="<?php echo esc_attr(get_the_author_meta( 'otuposition', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="partner">Partner</label></th>
                <td><input type="text" name="partner" value="<?php echo esc_attr(get_the_author_meta( 'partner', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="awards">Awards</label></th>
                <td><input type="text" name="awards" value="<?php echo esc_attr(get_the_author_meta( 'awards', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="comments">Comments</label></th>
                <td><input type="text" name="comments" value="<?php echo esc_attr(get_the_author_meta( 'comments', $user->ID )); ?>" class="regular-text" />
                </td>
            </tr>

            <tr>
                <th><label for="website">Website</label></th>
                <td><input type="text" name="website" value="<?php echo esc_attr(get_the_author_meta( 'website', $user->ID )); ?>" class="regular-text" />
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
                <th><label for="pmpro_bcity">City</label></th>
                <td><input type="text" name="pmpro_bcity" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_bcity', $user->ID )); ?>" class="regular-text" /></td>
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

add_action( 'wp_ajax_CBDWeb_copyUser', 'CBDWeb_copyUser' );

function CBDWeb_copyUser() {
    try {
        $user_id = $_POST['id'];
        $new_regimental_number = $_POST['new_regimental_number'];
        $new_last_name = $_POST['new_last_name'];
        $user = get_user_by( 'id', $user_id );
        $data = array();
        if( $user->last_name === $new_last_name && $user->get( 'pmpro_regimental_number' ) === $new_regimental_number ) {
            $data['message'] = "You haven't changed the regimental number nor the surname - no action taken.";
            echo json_encode( $data );
            die;
        }
        $username = $new_last_name . "_" . $new_regimental_number;
        $password = $new_regimental_number;
        $email = $user->user_email;
        $userdata = array(
            'user_login' => $username,
            'user_pass' => $password,
//            'user_email' => $email,
            'user_nicename' => $username,
            'display_name' => $user->first_name . ' ' . $new_last_name,
            'nickname' => $user->first_name . ' ' . $new_last_name,
            'first_name' => $user->first_name,
            'last_name' => $new_last_name,
            'show_admin_bar_front' => 'false',
            'show_admin_bar_admin' => 'false',
        );
        $new_user_id = wp_insert_user( $userdata );
        
        if ( is_wp_error($new_user_id) ) {
            echo json_encode ( array ( 'message' => $new_user_id->get_error_message() ) );
            die;
        }

        $user_meta = get_user_meta ( $user_id );
        
        global $wpdb;
        $query = "SELECT * FROM $wpdb->pmpro_memberships_users WHERE user_id=$user_id";
        $old_member = $wpdb->get_row( $query );
        if ( $old_member ) {
            $enddate = $old_member->enddate;
            $wpdb->update ( $wpdb->pmpro_memberships_users, 
                    array( 'user_id' => $new_user_id, 'status'=>'active', 'enddate' => $enddate ), 
                    array ( 'user_id' => $user_id ) );
        }
        
        wp_delete_user( $user_id, $new_user_id ); // assign posts to new user id
        
        $meta_keys = array(
            'pmpro_bfirstname',
            'committee',
            'pmpro_class',
            'pmpro_baddress1',
            'pmpro_bstate',
            'pmpro_bcity',
            'pmpro_bzipcode',
            'pmpro_bphone',
            'pmpro_bmobile',
            'pmpro_bbusiness',
            'pmpro_group',
            'pmpro_deceased',
            'pmpro_bemail',
            'pmpro_do_not_contact',
            'googleauthenticator_hidefromuser',
            'wpua_has_gravatar',
            'pmpro_bmiddlename',
            'pmpro_corps',
            'corps',
            'middlename',
            'wp_my0ord_user_level',            
        );
        
//        echo json_encode ( array ( 'user_meta' => $user_meta, 'message' => 'diagnostic' ) );
//        die;
        
        foreach ( $meta_keys as $key ) {
            if(isset($user_meta[ $key ] ) ) {
                update_user_meta( $new_user_id, $key, $user_meta[ $key ][0] );
            }
        }
        
        update_user_meta( $new_user_id, 'pmpro_regimental_number', $new_regimental_number );
        update_user_meta( $new_user_id, 'pmpro_blastname', $new_last_name );
        
        $new_user_id = wp_update_user ( array ( 'ID' => $new_user_id, 'user_email'=> $email ) );
        if ( is_wp_error ( $new_user_id ) ) {
            echo json_encode ( array ( 'message' => $new_user_id->get_error_message() ) );
            die;
        }
        
        echo json_encode ( array ( 'message' => 'Created new user.  The old user has been deleted and their posts copied over.', 
            'id'=>$new_user_id,
            ) );
        die;
    } catch (Exception $e) {
        echo json_encode( array ( 'message' => $e->getMessage() ) );
    }
}

add_action( 'personal_options_update', 'save_otu_fields' );
add_action( 'edit_user_profile_update', 'save_otu_fields' );

function save_otu_fields( $user_id )
{
    if( $_POST['pmpro_regimental_number'] ) 
        update_user_meta( $user_id,'pmpro_regimental_number', sanitize_text_field( $_POST['pmpro_regimental_number'] ) );
    
    update_user_meta ( $user_id, 'pmpro_class', sanitize_text_field( str_replace("//", "/", $_POST['pmpro_class'] ) ) );
    update_user_meta ( $user_id, 'committee', isset ( $_POST['committee'] ) ? "1" : "0" );
    
    update_user_meta ( $user_id, 'middlename', sanitize_text_field( $_POST['middlename'] ) );
    update_user_meta ( $user_id, 'corps', sanitize_text_field( $_POST['corps'] ) );
    update_user_meta ( $user_id, 'graduateno', sanitize_text_field( $_POST['graduateno'] ) );
    update_user_meta ( $user_id, 'directing', sanitize_text_field( $_POST['directing'] ) );
    update_user_meta ( $user_id, 'vietnam', sanitize_text_field( $_POST['vietnam'] ) );
    update_user_meta ( $user_id, 'industry', sanitize_text_field( $_POST['industry'] ) );
    update_user_meta ( $user_id, 'company', sanitize_text_field( $_POST['company'] ) );
    update_user_meta ( $user_id, 'occupation', sanitize_text_field( $_POST['occupation'] ) );
    update_user_meta ( $user_id, 'interests', sanitize_text_field( $_POST['interests'] ) );
    update_user_meta ( $user_id, 'otuposition', sanitize_text_field( $_POST['otuposition'] ) );
    update_user_meta ( $user_id, 'partner', sanitize_text_field( $_POST['partner'] ) );
    update_user_meta ( $user_id, 'awards', sanitize_text_field( $_POST['awards'] ) );
    update_user_meta ( $user_id, 'comments', sanitize_text_field( $_POST['comments'] ) );
    update_user_meta ( $user_id, 'website', sanitize_text_field( $_POST['website'] ) );
    
    $_POST['billing_first_name'] = $_POST['first_name'];
    $_POST['billing_last_name'] = $_POST['last_name'];
    $_POST['billing_address_1'] = $_POST['pmpro_baddress1'];
    $_POST['billing_address_2'] = $_POST['pmpro_baddress2'];
    $_POST['billing_city'] = $_POST['pmpro_bcity'];
    $_POST['billing_postcode'] = $_POST['pmpro_bzipcode'];
    $_POST['billing_state'] = $_POST['pmpro_bstate'];
    $_POST['billing_phone'] = $_POST['pmpro_bphone'];
    $_POST['billing_email'] = $_POST['email'];
    
    $user = get_userdata( $user_id );
    if( in_array('subscriber', $user->roles ) ) {
        // password is regimental number
        if( $_POST['pmpro_regimental_number']) // it's disabled on edit
            wp_set_password( $_POST['pmpro_regimental_number'], $user_id );
        // username is lastname_regimental number
        global $wpdb;
        $wpdb->update($wpdb->users, 
            array('user_login' => $_POST['last_name'] . "_" . $_POST['pmpro_regimental_number'] ),
            array( 'ID'=>$user_id ),
            array( 'user_nicename'=>$_POST['first_name'] . " " . $_POST['last_name'] ),
            array( '%s'),
            array( '%d' ),
            array( '%s' )
        );
    }
    update_user_meta( $user_id, 'pmpro_bemail', sanitize_text_field( $_POST['email'] ) );
    update_user_meta( $user_id,'pmpro_baddress1', sanitize_text_field( $_POST['pmpro_baddress1'] ) );
    update_user_meta( $user_id,'pmpro_baddress2', sanitize_text_field( $_POST['pmpro_baddress2'] ) );
    update_user_meta( $user_id,'pmpro_bcity', sanitize_text_field( $_POST['pmpro_bcity'] ) );
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
/* 
 * admin user list meta columns
 */
function otu_add_user_columns( $defaults ) {
     $defaults['deceased'] = __('Deceased', 'user-column');
     $defaults['do_not_contact'] = __('Do not contact', 'user-column');
     unset($defaults['posts']);
     return $defaults;
}
function otu_add_custom_user_columns($value, $column_name, $id) {
    $user = get_userdata( $id );
    switch ($column_name ) {
        case 'deceased':
            if($user->pmpro_deceased == "1" ) {
                return "Yes";
            } else {
                return "";
            }
            break;
        case 'do_not_contact':
            if ( $user->pmpro_do_not_contact == "1" ) {
                return "Yes";
            } else { 
                return "";
            }
            break;
        default:
            return $value;
    }
}
add_action('manage_users_custom_column', 'otu_add_custom_user_columns', 15, 3);
add_filter('manage_users_columns', 'otu_add_user_columns', 15, 1);

/**
 * login widget
 */
add_action( 'widgets_init', 'login_widget' );

/**
 * Register login widget.
 */
function login_widget() {
	register_widget( 'Otu_login' );
}

class Otu_login extends WP_Widget {

        function __construct() {
		$widget_ops = array('classname' => 'widget_login', 'description' => __( 'login form') );
		parent::__construct('login', __('Login'), $widget_ops);
	}

	function widget( $args, $instance ) {
		extract($args);

		echo $before_widget;
                if ( ! is_user_logged_in() ) { // Display WordPress login form:
                    ?>
                    <h3 class="widget-title">Login</h3>
                    <form name="loginform-custom" id="loginform-custom" onSubmit="loginSubmit(this)" action="<?=site_url()?>/wp-login.php" method="post">
                        <?php do_action( 'login_form' ); // BruteProtect failover ?> 
                        <p class="login-username"><label for="user_login">surname</label>
                            <input type="text" name="partial_log" id="user_login" class="input" size="20"/>
                            <input type="hidden" name="log" />
                        </p>
                        <P class="login-password">
                            <label for="user_pass">Reg No</label>
                            <input type="password" name="pwd" id="user_pass" class="input" size="20"/>
                        </P>
                        <?php // do_action( 'login_form' );?>
                        <p class="login-remember">
                            <label>
                                <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                                Remember Me
                            </label>
                        </p>
                        <p class="login-submit">
                            <input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Log In"/>
                        </p>
                    </form>
                    <?php
                } else { // If logged in:
                    wp_loginout( home_url() ); // Display "Log Out" link.
                    if( current_user_can( 'moderate_comments' ) ) {
                        echo " | ";
                        wp_register('', ''); // Display "Site Admin" link.
                    }
                }
                echo $after_widget;
	}
}

add_action('init', 'register_otu_script' );
function register_otu_script() {
	wp_register_script('script',  plugins_url( 'js/script.js', __FILE__ ), 'jquery');
        wp_enqueue_script('script');
        
}
/*
 * shortcode to insert link to sign up for non-financials
 */
function otu_signup( $atts ){
    ob_start();
	 if ( is_user_logged_in() ) {
             $level = pmpro_getMembershipLevelForUser();
             $url = pmpro_url("checkout", "?level=1");
             if ( $level->ID == 1 ) {
                 $ed = $level->enddate;
                 $edDT = new DateTime();
                 $edDT->setTimestamp($ed);
                 ?>
                 Your membership is paid to <?=$edDT->format('d/m/Y');?>. Memberships fall due on 1st July each year.
                 To renew in advance, <a href="<?=$url?>/">click here</a>.
                 <?php
            } else if ( $level->ID == 2 ){ 
                ?>
                 You are an honorary member, no subscription is payable.
             <?php } else { ?>
                 <a href="<?=$url?>">Renew your membership now</a>.
             <?php }
         } else { ?>
             <a href="<?=site_url()?>/sign-in-to-otu-website/">Please login in order to join or renew your membership of the Officer Training Unit Association</a>.
         <?php }
    return ob_get_clean();
}
add_shortcode( 'otu_signup', 'otu_signup' );

/*
 * prevent member from exercising the lost password form. 
 * Members should not be able to change their password, as it should be set to the regimental number
 */
function remove_lost_your_password($text) 
  {
    return str_replace( array('Lost your password?', 'Lost your password'), '', trim($text, '?') ); 
  }
add_filter( 'gettext', 'remove_lost_your_password'  );

add_filter('allow_password_reset', '__return_false' );