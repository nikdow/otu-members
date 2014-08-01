<?php
/**
 * Plugin Name: Freestyle Cyclists Signatures
 * Plugin URI: http://www.cbdweb.net
 * Description: Online petition including email confirmation and admin approval
 * Version: 1.0
 * Author: Nik Dow, CBDWeb
 * License: GPL2
 */
defined('ABSPATH') or die("No script kiddies please!");
/*
 * Signatures - custom post type and facilities to support the big petition
 */
function signature_register_js() {	// support for signup form, which appears on two pages and in a popup
    wp_register_script('signature', plugins_url( 'js/signature.js' , __FILE__ ), 'jquery');
    wp_enqueue_script('signature');
    wp_register_style('fs-signature-styles', plugins_url( 'css/style.css', __FILE__ ) );
    wp_enqueue_style('fs-signature-styles');
}
add_action('wp_enqueue_scripts', 'signature_register_js');
/*
 * Signatures AJAX calls
 */
// request to edit an existing signature from page /confirm without a secret key in the query_string
add_action( 'wp_ajax_reconfirmSignature', 'fs_reconfirmSignature' );
add_action( 'wp_ajax_nopriv_reconfirmSignature', 'fs_reconfirmSignature' );

function fs_reconfirmSignature() {
    $email = $_POST['email'];
    if (
        ! isset( $_POST['fs_nonce'] ) 
        || ! wp_verify_nonce( $_POST['fs_nonce'], 'fs_reconfirm_sig' )
    ) {

       echo json_encode( array( 'error'=>'Sorry, your nonce did not verify.' ) );
       die;
    }

    global $wpdb;
    $query = $wpdb->prepare( // find the custom post for this sig
        "SELECT p.ID FROM " . $wpdb->posts . " p LEFT JOIN " . $wpdb->postmeta . " m ON m.post_id=p.ID "
            . "WHERE m.meta_key='fs_signature_email' AND m.meta_value=%s AND (p.post_status='private' OR p.post_status='draft')",
            $email );
    
    $id = $wpdb->get_col( $query );
    $post = get_post( $id[0], "OBJECT" );
    
    if( ! $post || ! ( $post->post_status === "private" || $post->post_status === "draft" ) ) {
        echo json_encode ( array( 'error'=>'We couldn\'t find that email address. We would hate to lose contact with you, so please <a href="mailto:info@freestylecyclists.org">email us</a> so we can help sort it out.' ) );
        die;
    }
    $secret = generateRandomString();
    update_post_meta( $id[0], 'fs_signature_secret', $secret );
    
    $subject = "Hello from Freestyle Cyclists";
    $headers = array();
    $headers[] = 'From: Freestyle Cyclists <info@freestylecyclists.org>';
    $headers[] = "Content-type: text/html";
    $message = "<P>Thanks for visiting Freestyle Cyclists.</P>";
    $message .= "<P>Below is a link you can use to update the details on the petition for bicycle helmet law reform.</p>";
    $message .= "<P><a href='http://www.freestylecyclists.org/confirm?secret=" . $secret . "'>Click here to update your details on Freestyle Cyclists</a></P>";
    wp_mail( $email, $subject, $message, $headers );
    
    echo json_encode ( array( 'success' => 'We have sent you a new email, you can click on the link there to update your details.' ) );
    die;
}
/*
 * Signature - confirming a signature or updating it
 */
add_action( 'wp_ajax_confirmSignature', 'fs_confirmSignature' );
add_action( 'wp_ajax_nopriv_confirmSignature', 'fs_confirmSignature' );

function fs_confirmSignature() {
    // fields edited by signatory:
    $fs_signature_public = $_POST['fs_signature_public'];
    $fs_signature_newsletter = $_POST['fs_signature_newsletter'];
    $fs_signature_country = $_POST['fs_signature_country'];
    $fs_signature_state = $_POST['fs_signature_state'];
    $excerpt = $_POST['excerpt'];
    $id = $_POST['id'];
    $secretkey = $_POST['secretkey'];
    if (
        ! isset( $_POST['fs_nonce'] ) 
        || ! wp_verify_nonce( $_POST['fs_nonce'], 'fs_confirm_sig_' . $id )
    ) {

       echo json_encode( array( 'error'=>'Sorry, your nonce did not verify.' ) );
       die;
    }
    
    $custom = get_post_custom( $id );
    if ( $secretkey !== $custom [ 'fs_signature_secret' ][0] ) {
        echo json_encode ( array ( 'error'=>'To edit or confirm a signature, you must click on a link in an email from us. You can only save once, using the link, after which you need to get a new email.'
            . '<br\><a href="' . get_site_url() . '/confirm">Click here to get a new email link</a>.' ) );
        die;
    }
    
    $post = get_post( $id, "OBJECT" );
    if( ! $post ) {
        echo json_encode ( array( 'error'=>'Something has gone wrong and we weren\'t able to find and confirm your signature. We would hate to lose contact with you, so please <a href="mailto:info@freestylecyclists.org">email us</a> so we can help sort it out.' ) );
        die;
    }
    
    update_post_meta( $id, 'fs_signature_public', $fs_signature_public );
    update_post_meta( $id, 'fs_signature_newsletter', $fs_signature_newsletter );
    update_post_meta( $id, 'fs_signature_country', $fs_signature_country );
    update_post_meta( $id, 'fs_signature_state', $fs_signature_state );
    delete_post_meta( $id, 'fs_signature_secret' );
    $post->post_excerpt = $excerpt;
    $post->post_status = "private"; // status=draft when first create, changes to private when email address is confirmed
    wp_update_post ( $post );
    echo json_encode ( array( 'success' => 'Thanks for updating your details.' ) );
    die;
}
/*
 * AJAX call to add a new signature
 */
add_action( 'wp_ajax_newSignature', 'fs_newSignature' );
add_action( 'wp_ajax_nopriv_newSignature', 'fs_newSignature' );

function fs_newSignature() {
    $title = $_POST['title'];
    $fs_signature_public = $_POST['fs_signature_public'];
    $fs_signature_email = $_POST['fs_signature_email'];
    $fs_signature_newsletter = $_POST['fs_signature_newsletter'];
    $fs_signature_country = $_POST['fs_signature_country'];
    $fs_signature_state = $_POST['fs_signature_state'];
    $areYouThere = $_POST['areYouThere'];
    $excerpt = $_POST['excerpt'];
    if (
        ! isset( $_POST['fs_nonce'] ) 
        || ! wp_verify_nonce( $_POST['fs_nonce'], 'fs_new_sig' ) 
    ) {

       echo json_encode( array( 'error'=>'Sorry, your nonce did not verify.' ) );
       die;
    }
    
    if($fs_signature_email==="") {
        echo json_encode( array( 'error'=>'Please supply an email address' ) );
        die;
    }
    global $wpdb;
    // check to see this email isn't already on the petition
    $query = $wpdb->prepare('SELECT p.post_status as `status` FROM ' . $wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' m ON p.ID=m.post_id WHERE m.meta_key="fs_signature_email" AND m.meta_value="%s"', $fs_signature_email );
    $results = $wpdb->get_results( $query );
    $found = false;
    foreach( $results as $row ) {
        if( $row->status === 'private' || $row->status === 'draft' ) $found = true; // for this check we ignore drafts, so people can try again
    }
    if($found) {
        $link = esc_url( get_permalink( get_page_by_title( 'Confirm' ) ) );
        $hasQS = strrpos ( $link, "?" ) !== false;
        echo json_encode( array('error'=>'That email address is already registered<br/>'
            . '<a href=\'' . $link . ( $hasQS ? '&' : '?') . 'email=' . $fs_signature_email . '\'>Click here to get an email to update your entry' ) );
        die;
    }
    if( $areYouThere !== "y" ) {
        echo json_encode( array('error'=>'Please tick the box to show you are not a robot') );
        die;
    }
    if( $title==="" ) {
        $fs_signature_public = false;
    }
    if( $fs_signature_country==="" ) {
        echo json_encode( array('error'=>'Please select your country') );
        die;
    }
        
    $post_id = wp_insert_post(array(
            'post_title'=>$title,
            'post_status'=>'draft',
            'post_type'=>'fs_signature',
            'ping_status'=>false,
            'post_excerpt'=>$excerpt,
            'comment_status'=>'closed',
        ),
        true
    );
    if(is_wp_error($post_id)) {
        echo json_encode( array( 'error'=>$post_id->get_error_message() ) );
        die;
    }
    update_post_meta($post_id, "fs_signature_country", $fs_signature_country );
    if($fs_signature_country==="AU") {
        update_post_meta($post_id, "fs_signature_state", $fs_signature_state );
    }
    update_post_meta($post_id, "fs_signature_email", $fs_signature_email );
    update_post_meta($post_id, "fs_signature_public", $fs_signature_public );
    update_post_meta($post_id, "fs_signature_newsletter", $fs_signature_newsletter );
    
    if(isset($_COOKIE['referrer'])) {
        $referrer = $_COOKIE['referrer'];
    } else {
        $referrer = $_SERVER['HTTP_REFERER'];
    }
    if(strpos($referrer, 'freestylecyclists.org') !== false ) $referrer = "";
    if($referrer) update_post_meta ( $post_id, "fs_signature_referrer", substr( $referrer, 0, 255 ) );
    $secret = generateRandomString();
    update_post_meta ( $post_id, "fs_signature_secret", $secret );

    $campaign = "";
    if( isset($_GET['campaign']) ) {
        $campaign = $_GET['campaign'];
    } else if(isset($_COOKIE['campaign'])) {
        $campaign = $_COOKIE['campaign'];
    }
    if($campaign) update_post_meta( $post_id, "fs_signature_campaign", substr( $campaign, 0, 255 ) );
    
    $subject = "Confirm your support for Bicycle Helmet Law reform on Freestyle Cyclists";
    $headers = array();
    $headers[] = 'From: Freestyle Cyclists <info@freestylecyclists.org>';
    $headers[] = "Content-type: text/html";
    $message = "<P>Thanks for signing up to support bicycle helmet law reform at Freestyle Cyclists.</P>";
    $message .= "<P>For your signature to count, we ask you to click on the link below, so we know your email address is genuine.</p>";
    $message .= "<P>Don't worry, we don't give your email address to anyone, and you decide whether to receive emails from us in future.</P>";
    $message .= "<P><a href='http://www.freestylecyclists.org/confirm?secret=" . $secret . "'>Click here to verify your email and make your signature count</a></P>";
    wp_mail( $fs_signature_email, $subject, $message, $headers );
    
    echo json_encode( array( 'success'=>'You have successfully registered your support. Look for an email from us and click on the link to confirm your email address - until then we can\'t count you.' ) );
    die();
}
/*
 * Create custom post type to store signatures
 */
add_action( 'init', 'create_fs_signature' );
function create_fs_signature() {
	$labels = array(
        'name' => _x('Signatures', 'post type general name'),
        'singular_name' => _x('Signature', 'post type singular name'),
        'add_new' => _x('Add New', 'events'),
        'add_new_item' => __('Add New Signature'),
        'edit_item' => __('Edit Signature'),
        'new_item' => __('New Signature'),
        'view_item' => __('View Signature'),
        'search_items' => __('Search Signatures'),
        'not_found' =>  __('No signatures found'),
        'not_found_in_trash' => __('No signatures found in Trash'),
        'parent_item_colon' => '',
    );
    register_post_type( 'fs_signature',
        array(
            'label'=>__('Signatures'),
            'labels' => $labels,
            'description' => 'Each post is one sign-up to the helmet law repeal online petition.',
            'public' => true,
            'can_export' => true,
            'exclude_from_search' => true,
            'has_archive' => true,
            'show_ui' => true,
            'capability_type' => 'post',
            'menu_icon' => "dashicons-welcome-write-blog",
            'hierarchical' => false,
            'rewrite' => false,
            'supports'=> array('title', 'excerpt' ) ,
            'show_in_nav_menus' => true,
            )
    );
}
/*
 * specify columns in admin view of signatures custom post listing
 */
add_filter ( "manage_edit-fs_signature_columns", "fs_signature_edit_columns" );
add_action ( "manage_posts_custom_column", "fs_signature_custom_columns" );
function fs_signature_edit_columns($columns) {
    $columns = array(
        "cb" => "<input type=\"checkbox\" />",
        "title" => "Name",
        "fs_col_public" => "Show name",
        "fs_col_country" => "Country",
        "fs_col_state" => "State",
        "fs_col_registered" => "Registered",
        "fs_col_campaign" => "Campaign",
        "fs_col_referrer" => "Referrer",
        "fs_col_moderate" => "Moderated",
        "comment" => "Comment",
    );
    return $columns;
}
function fs_signature_custom_columns($column) {
    global $post;
    $custom = get_post_custom();
    switch ( $column ) {
        case "title":
            echo $post->post_title;
            break;
        case "fs_col_public":
            echo ( $custom["fs_signature_public"][0] === "y" ? "Yes" : "" );
            break;
        case "fs_col_country":
            echo $custom["fs_signature_country"][0];
            break;
        case "fs_col_state":
            echo ( $custom["fs_signature_country"][0]==="AU" ? $custom["fs_signature_state"][0] : "&nbsp;" );
            break;
        case "fs_col_registered":
            if($post->post_status==="private") {
                echo $custom["fs_signature_registered"][0];
            } else {
                echo "&nbsp;";
            }
            break;
        case "fs_col_campaign":
            echo $custom["fs_signature_campaign"][0];
            break;
        case "fs_col_referrer":
            preg_match( '@^(?:http([s]*)://)?([^/]+)@i', $custom["fs_signature_referrer"][0], $matches );
            $host = $matches[2];
            if( $host ) {
                echo "<a href='" . $custom["fs_signature_referrer"][0] . "' target='_blank'>http" . $matches[1] . "://" . $host . "</a>";
            } else {
                echo "&nbsp;";
            }
            break;
        case "fs_col_moderate":
            echo $custom["fs_signature_moderate"][0];
            break;
        case "comment":
            echo substr( $post->post_excerpt, 0, 100 );
            break;
    }
}
/*
 * Add fields for admin to edit signature custom post
 */
add_action( 'admin_init', 'fs_signature_create' );
function fs_signature_create() {
    add_meta_box('fs_signature_meta', 'Signature', 'fs_signature_meta', 'fs_signature' );
}
function fs_signature_meta() {
    global $post;
    $custom = get_post_custom( $post->ID );
    $meta_country = $custom['fs_signature_country'][0];
    $meta_state = $custom['fs_signature_state'][0];
    $meta_registered = $custom['fs_signature_registered'][0];
    $meta_email = $custom['fs_signature_email'][0];
    $meta_public = $custom['fs_signature_public'][0];
    $meta_campaign = $custom['fs_signature_campaign'][0];
    $meta_referrer = $custom['fs_signature_referrer'][0];
    $meta_moderate = $custom['fs_signature_moderate'][0];
    $meta_newsletter = $custom['fs_signature_newsletter'][0];
    
    echo '<input type="hidden" name="fs-signature-nonce" id="fs-signature-nonce" value="' .
        wp_create_nonce( 'fs-signature-nonce' ) . '" />';
    ?>
    <div class="fs-meta">
        <ul>
            <li><label>Comment moderated?</label><input name="fs_signature_moderate" value="<?php echo $meta_moderate; ?>" /> (enter "y" or blank)</li>
            <li><label>Country</label>
                <select name="fs_signature_country">
                    <option value="">Please select</option>
                    <?php 
                    $fs_country = fs_country();
                    foreach($fs_country as $ab => $title ) { ?>
                        <option value="<?=$ab;?>"<?php echo ($meta_country===$ab ? " selected='selected'" : "") ;?>><?php echo $title;?></option>
                    <?php } ?>
                </select>
            </li>
            <li><label>State</label>
                <select name="fs_signature_state">
                <option value="">Please select</option>
                <?php 
                $fs_states = fs_states();
                foreach($fs_states as $ab => $title ) { ?>
                    <option value="<?=$ab;?>"<?php echo ($meta_state===$ab ? " selected='selected'" : "") ;?>><?php echo $title;?></option>
                <?php } ?>
                </select>
            </li>
            <li><label>Newsletter</label>
                <input type="radio" value="" name="fs_signature_newsletter" <?=($meta_newsletter==="" ? "checked" : "")?>/>Never 
            </li>
            <li>
                <label>&nbsp;</label>
                <input type="radio" value="y" name="fs_signature_newsletter" <?=($meta_newsletter==="y" ? "checked" : "")?>/>Occasionally: When something important is happening
            </li>
            <li>
                <label>&nbsp;</label>
                <input type="radio" value="m" name="fs_signature_newsletter" <?=($meta_newsletter==="m" ? "checked" : "")?>/>More often: Keep me updated
            </li>
            <li><label>Email</label><input name="fs_signature_email" value="<?php echo $meta_email; ?>" /></li>
            <li><label>Show name</label><input name="fs_signature_public" value="<?php echo $meta_public; ?>" /> (enter "y" or blank)</li>
            <li><label>Registered</label><input name="fs_signature_registered" value="<?php echo $meta_registered; ?>" />(yyyy-mm-dd)</li>
            <li><label>Campaign</label><input name="fs_signature_campaign" value="<?php echo $meta_campaign; ?>" /></li>
            <li><label>Referrer</label><input name="fs_signature_referrer" value="<?php echo $meta_referrer; ?>" /></li>
        </ul>
    </div>
    <?php    
}

add_action ('save_post', 'save_fs_signature');
 
function save_fs_signature(){
 
    global $post;

    // - still require nonce

    if ( !wp_verify_nonce( $_POST['fs-signature-nonce'], 'fs-signature-nonce' )) {
        return $post->ID;
    }

    if ( !current_user_can( 'edit_post', $post->ID ))
        return $post->ID;

    // - convert back to unix & update post

    if(!isset($_POST["fs_signature_country"])):
        return $post;
    endif;
    update_post_meta($post->ID, "fs_signature_country", $_POST["fs_signature_country"] );
    update_post_meta($post->ID, "fs_signature_state", $_POST["fs_signature_state"] );
    update_post_meta($post->ID, "fs_signature_newsletter", $_POST["fs_signature_newsletter"] );
    update_post_meta($post->ID, "fs_signature_registered", $_POST["fs_signature_registered"] );
    if(!isset($_POST["fs_signature_email"])):
        return $post;
    endif;
    update_post_meta( $post->ID, "fs_signature_referrer", $_POST["fs_signature_referrer"] );
    update_post_meta($post->ID, "fs_signature_email", $_POST["fs_signature_email"]);
    update_post_meta($post->ID, "fs_signature_public", $_POST["fs_signature_public"]);
    update_post_meta($post->ID, "fs_signature_moderate", $_POST["fs_signature_moderate"]);
}

add_filter('post_updated_messages', 'signature_updated_messages');
 
function signature_updated_messages( $messages ) {
 
  global $post, $post_ID;
 
  $messages['fs_signature'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => sprintf( __('Signature updated. <a href="%s">View item</a>'), esc_url( get_permalink($post_ID) ) ),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Signature updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Signature restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => sprintf( __('Signature published. <a href="%s">View Signature</a>'), esc_url( get_permalink($post_ID) ) ),
    7 => __('Signature saved.'),
    8 => sprintf( __('Signature submitted. <a target="_blank" href="%s">Preview event</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
    9 => sprintf( __('Signature scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview signature</a>'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
    10 => sprintf( __('Signature draft updated. <a target="_blank" href="%s">Preview signature</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
  );
 
  return $messages;
}
/* 
 * returns list of states for use wherever
 */
function fs_states() {
    return array(
        "NSW"=>"New South Wales",
        "VIC"=>"Victoria",
        "QLD"=>"Queensland",
        "WA"=>"Western Australia",
        "SA"=>"South Australia",
        "TAS"=>"Tasmania",
        "ACT"=>"ACT",
        "NT"=>"Northern Territory",
    );
}
/* 
 * list of countries
 */
function fs_country() {
    return array(
        "AU"=>"Australia",
        "NZ"=>"New Zealand",
        "AF"=>"Afghanistan",
        "AL"=>"Albania",
        "DZ"=>"Algeria",
        "AS"=>"American Samoa",
        "AD"=>"Andorra",
        "AO"=>"Angola",
        "AI"=>"Anguilla",
        "AQ"=>"Antarctica",
        "AG"=>"Antigua and Barbuda",
        "AR"=>"Argentina",
        "AM"=>"Armenia",
        "AW"=>"Aruba",
        "AT"=>"Austria",
        "AZ"=>"Azerbaijan",
        "BS"=>"Bahamas",
        "BH"=>"Bahrain",
        "BD"=>"Bangladesh",
        "BB"=>"Barbados",
        "BY"=>"Belarus",
        "BE"=>"Belgium",
        "BZ"=>"Belize",
        "BJ"=>"Benin",
        "BM"=>"Bermuda",
        "BT"=>"Bhutan",
        "BO"=>"Bolivia",
        "BA"=>"Bosnia and Herzegovina",
        "BW"=>"Botswana",
        "BR"=>"Brazil",
        "IO"=>"British Indian Ocean Territory",
        "BN"=>"Brunei",
        "BG"=>"Bulgaria",
        "BF"=>"Burkina Faso",
        "BI"=>"Burundi",
        "KH"=>"Cambodia",
        "CM"=>"Cameroon",
        "CA"=>"Canada",
        "CV"=>"Cape Verde",
        "KY"=>"Cayman Islands",
        "CF"=>"Central African Republic",
        "TD"=>"Chad",
        "CL"=>"Chile",
        "CN"=>"China",
        "CX"=>"Christmas Islands",
        "CC"=>"Cocos (Keeling) Islands",
        "CO"=>"Colombia",
        "KM"=>"Comoros",
        "CG"=>"Congo",
        "CD"=>"Congo, Democratic Republic of",
        "CK"=>"Cook Island",
        "CR"=>"Costa Rica",
        "CI"=>"Cote d\'lvoire",
        "HR"=>"Croatia",
        "CY"=>"Cyprus",
        "CZ"=>"Czech Republic",
        "DK"=>"Denmark",
        "DJ"=>"Djibouti",
        "DM"=>"Dominica",
        "DO"=>"Dominican Republic",
        "TP"=>"East Timor",
        "EG"=>"Egypt",
        "SV"=>"El Salvador",
        "EC"=>"Ecuador",
        "GC"=>"Equatorial Guinea",
        "ER"=>"Eritrea",
        "EE"=>"Estonia",
        "ET"=>"Ethiopia",
        "FK"=>"Falkland Islands",
        "FO"=>"Faroe Islands",
        "FM"=>"Federated States of Micronesia",
        "FJ"=>"Fiji",
        "FI"=>"Finland",
        "FR"=>"France",
        "GF"=>"French Guiana",
        "PF"=>"French Polynesia",
        "TF"=>"French Southern Territories",
        "GA"=>"Gabon",
        "GM"=>"Gambia",
        "GE"=>"Georgia",
        "DE"=>"Germany",
        "GH"=>"Ghana",
        "GI"=>"Gibraltar",
        "GR"=>"Greece",
        "GL"=>"Greenland",
        "GD"=>"Grenada",
        "GP"=>"Guadeloupe",
        "GU"=>"Guam",
        "GT"=>"Guatemala",
        "GN"=>"Guinea",
        "GW"=>"Guinea-Bissau",
        "GY"=>"Guyana",
        "HT"=>"Haiti",
        "HM"=>"Heard and Macdonald Islands",
        "HN"=>"Honduras",
        "HK"=>"Hong Kong",
        "HU"=>"Hungary",
        "IS"=>"Iceland",
        "IN"=>"India",
        "ID"=>"Indonesia",
        "IQ"=>"Iraq",
        "IE"=>"Ireland",
        "IL"=>"Israel",
        "IT"=>"Italy",
        "JM"=>"Jamaica",
        "JP"=>"Japan",
        "JO"=>"Jordan",
        "KZ"=>"Kazakhstan",
        "KE"=>"Kenya",
        "KI"=>"Kiribati",
        "KP"=>"Korea, North",
        "KR"=>"Korea, South",
        "KW"=>"Kuwait",
        "KG"=>"Kyrgyzstan",
        "LA"=>"Laos",
        "LV"=>"Latvia",
        "LB"=>"Lebanon",
        "LS"=>"Lesotho",
        "LR"=>"Liberia",
        "LY"=>"Libya",
        "LI"=>"Liechtenstein",
        "LT"=>"Lithuania",
        "LU"=>"Luxembourg",
        "MO"=>"Macau",
        "MK"=>"Macedonia",
        "MG"=>"Madagascar",
        "MW"=>"Malawi",
        "MY"=>"Malaysia",
        "MV"=>"Maldives",
        "ML"=>"Mali",
        "MT"=>"Malta",
        "MH"=>"Marshall Islands",
        "MQ"=>"Martinique",
        "MR"=>"Mauritania",
        "MU"=>"Mauritius",
        "YT"=>"Mayotte",
        "FX"=>"Metropolitan France",
        "MX"=>"Mexico",
        "MD"=>"Moldova",
        "MC"=>"Monaco",
        "MN"=>"Mongolia",
        "ME"=>"Montenegro",
        "MS"=>"Montserrat",
        "MA"=>"Morocco",
        "MZ"=>"Mozambique",
        "MM"=>"Myanmar",
        "NA"=>"Namibia",
        "NR"=>"Nauru",
        "NP"=>"Nepal",
        "NL"=>"Netherlands",
        "AN"=>"Netherlands Antilles",
        "NC"=>"New Caledonia",
        "NI"=>"Nicaragua",
        "NE"=>"Niger",
        "NG"=>"Nigeria",
        "NU"=>"Niue",
        "NF"=>"Norfolk Island",
        "MP"=>"Northern Mariana Islands",
        "NO"=>"Norway",
        "OM"=>"Oman",
        "PK"=>"Pakistan",
        "PW"=>"Palau",
        "PS"=>"Palestinian Territory, Occupied",
        "PA"=>"Panama",
        "PG"=>"Papua New Guinea",
        "PY"=>"Paraguay",
        "PE"=>"Peru",
        "PH"=>"Philippines",
        "PN"=>"Pitcairn",
        "PL"=>"Poland",
        "PT"=>"Portugal",
        "PR"=>"Puerto Rico",
        "QA"=>"Qatar",
        "RE"=>"Reunion",
        "RO"=>"Romania",
        "RU"=>"Russia",
        "RW"=>"Rwanda",
        "GS"=>"S. Georgia &amp; S. Sandwich Isls",
        "WS"=>"Samoa",
        "SM"=>"San Marino",
        "ST"=>"Sao Tome and Principe",
        "SA"=>"Saudi Arabia",
        "SN"=>"Senegal",
        "RS"=>"Serbia, Republic of",
        "SC"=>"Seychelles",
        "SL"=>"Sierra Leone",
        "SG"=>"Singapore",
        "SK"=>"Slovakia",
        "SI"=>"Slovenia",
        "SB"=>"Solomon Islands",
        "SO"=>"Somalia",
        "ZA"=>"South Africa",
        "ES"=>"Spain",
        "LK"=>"Sri Lanka",
        "SH"=>"St Helena",
        "KN"=>"St Kitts and Nevis",
        "LC"=>"St Lucia",
        "PM"=>"St Pierre and Miquelon",
        "VC"=>"St Vincent and the Grenadines",
        "SR"=>"Suriname",
        "SJ"=>"Svalbard and Jan Mayen Islands",
        "SZ"=>"Swaziland",
        "SE"=>"Sweden",
        "CH"=>"Switzerland",
        "TW"=>"Taiwan",
        "TJ"=>"Tajikistan",
        "TZ"=>"Tanzania",
        "TH"=>"Thailand",
        "TG"=>"Togo",
        "TK"=>"Tokelau",
        "TO"=>"Tonga",
        "TT"=>"Trinidad and Tobago",
        "TN"=>"Tunisia",
        "TR"=>"Turkey",
        "TM"=>"Turkmenistan",
        "TC"=>"Turks and Caicos Islands",
        "TV"=>"Tuvalu",
        "UG"=>"Uganda",
        "UA"=>"Ukraine",
        "AE"=>"United Arab Emirates",
        "GB"=>"United Kingdom",
        "US"=>"United States",
        "UY"=>"Uruguay",
        "UZ"=>"Uzbekistan",
        "VU"=>"Vanuatu",
        "VA"=>"Vatican City",
        "VE"=>"Venezuela",
        "VN"=>"Vietnam",
        "VG"=>"Virgin Islands - British",
        "VI"=>"Virgin Islands - US",
        "WF"=>"Wallis and Futuna Islands",
        "EH"=>"Western Sahara",
        "YE"=>"Yemen",
        "YU"=>"Yugoslavia",
        "ZR"=>"Zaire",
        "ZM"=>"Zambia",
        "ZW"=>"Zimbabwe",
    );
}
/* 
 * showing sigs to the public - called from ajax wrapper and also when loading page initially
 */
function get_sigs( $first_sig, $rows_per_page ){
    global $wpdb;
    $fs_country = fs_country();
    $query = $wpdb->prepare ( 
        "SELECT p.post_title, p.post_excerpt, p.ID, pmc.meta_value AS country, pms.meta_value AS state, pmp.meta_value AS public, pmm.meta_value as moderate, pmr.meta_value as registered from " . 
        $wpdb->posts . " p" .
        " LEFT JOIN " . $wpdb->postmeta . " pmc ON pmc.post_id=p.ID AND pmc.meta_key='fs_signature_country'" . 
        " LEFT JOIN " . $wpdb->postmeta . " pms ON pms.post_id=p.ID AND pms.meta_key='fs_signature_state'" .
        " LEFT JOIN " . $wpdb->postmeta . " pmp ON pmp.post_id=p.ID AND pmp.meta_key='fs_signature_public'" .
        " LEFT JOIN " . $wpdb->postmeta . " pmm ON pmm.post_id=p.ID AND pmm.meta_key='fs_signature_moderate'" .
        " LEFT JOIN " . $wpdb->postmeta . " pmr ON pmr.post_id=p.ID AND pmr.meta_key='fs_signature_registered'" .
        " WHERE p.post_type='fs_signature' AND p.`post_status`='private' ORDER BY registered DESC LIMIT %d,%d", $first_sig, $rows_per_page 
    );
    $rows = $wpdb->get_results ( $query );
    $output = array();
    foreach ( $rows as $row ) {
        $output[] = array(
            'name'=>$row->public==="y" ? $row->post_title : "withheld",
            'location'=>$row->country==="AU" ? $row->state : $fs_country[$row->country],
            'date'=> date( "j/n/y", strtotime( $row->registered ) ),
            'moderate'=>$row->moderate,
            'comment'=>$row->post_excerpt==="" ? "" : ( $row->moderate==="y" || current_user_can('moderate_comments') ? $row->post_excerpt : "comment awaiting moderation" ),
            'id'=> current_user_can('moderate_comments') ? $row->ID : "",
        );
    }
    return $output;
}
/*
 * AJAX wrapper to get sigs
 */
add_action( 'wp_ajax_get_sigs', 'fs_get_sigs' );
add_action( 'wp_ajax_nopriv_get_sigs', 'fs_get_sigs' );

function fs_get_sigs() {
    $rows_per_page = $_POST['rows_per_page'];
    $page = $_POST['page'];
    $first_sig = ( $page - 1 ) * $rows_per_page;
    echo json_encode( get_sigs( $first_sig, $rows_per_page) );
    die;
}
/*
 * AJAX call to moderate a comment in a signature (i.e. approve it)
 */
add_action( 'wp_ajax_moderate', 'fs_moderate' );

function fs_moderate() {
    if(current_user_can('moderate_comments') ) {
        $id = $_POST['id'];
        update_post_meta( $id, "fs_signature_moderate", "y" );
        echo json_encode( array('success'=>true, 'id'=>$id ) );
        die;
    } else {
        echo json_encode( array( 'success'=>false, 'message'=>'Not logged in as admin') );
        die;
    }
}
/*
 * used in secret key for email confirmation of signatures
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
/*
 * Shortcode for confirming or reconfirming signature
 */
add_action('init', 'register_signature_confirm_script' );
add_action( 'wp_footer', 'enqueue_signature_confirm_script' );
function register_signature_confirm_script() {
	wp_register_script('confirm',  plugins_url( 'js/confirm.js', __FILE__ ), 'jquery');
}
function enqueue_signature_confirm_script() {
    global $add_signature_confirm_script;
    if( ! $add_signature_confirm_script ) return;
    wp_enqueue_script('confirm');
}
function fs_signature_confirm () {
    global $add_signature_confirm_script;
    $add_signature_confirm_script = true;
    
    $secret = $_GET['secret'];
    $email = $_GET['email'];
    global $wpdb;
    $found = false;
    if($secret!=="") {
        $query = $wpdb->prepare ( "SELECT * FROM " . $wpdb->postmeta . " WHERE meta_value='%s' AND meta_key='fs_signature_secret'", $secret );
        $row = $wpdb->get_row( $query );
        if( $row ) {
            $post_id = $row->post_id;
            if( $post_id ) {
                $sig = get_post ( $post_id, 'OBJECT' );
                if($sig->post_type === 'fs_signature' && ( $sig->post_status==="draft" || $sig->post_status==="private" ) ) {
                    $custom = get_post_custom( $post_id );
                    $update = $sig->post_status === "private";
                    if( $sig->post_title !== "" && $custom['fs_signature_country'][0] ) {
                        $found = true;
                        $sig->post_status = "private";
                        if( ! $update ) update_post_meta( $post_id, 'fs_signature_registered', date('Y-m-d') );
                        wp_update_post ( $sig );
                    }
                }
            }
        }
    }
    ob_start();
    if( ! $found ) { 
        if( $secret ) { ?>
            <P>Sorry, the secret code doesn't match our records and we can't confirm your email.</P>
            <P>The link in our original email to you only works once.  If you would like to change your email preferences, enter your email address here, and we will send you a fresh email, with a link that will enable you to update your details.</P>
        <?php } else { ?>
            <h2>Welcome back.</h2>
            To edit your details on Freestyle Cyclists' campaign to reform helmet law, enter your email address below, and we will send you
            a new email link with a secret code that works just once.
        <?php } ?>

        <form name="confirm">
            <table border="0">
                <tbody>
                    <tr><td class="leftcol">Your email:</td><td class="rightcol"><input name="email" value="<?=$email?>"/></td></tr>
                    <tr><td colspan="2"><button type="button" id="confirmButton">Get a new email</button></td></tr>
                    <tr><td colspan="2"><div id="ajax-loading" class="farleft"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></div></td></tr>
                    <tr><td colspan="2"><div id="returnMessage"></div></td></tr>
                </tbody>
            </table>
            <input name="action" value="reconfirmSignature" type="hidden">
            <input name="secretkey" value="<?=$secret;?>" type="hidden">
            <?php wp_nonce_field( "fs_reconfirm_sig", "fs_nonce" );?>
        </form>
        <P>Otherwise, send an email to <a href='mailto:info@freestylecyclists.org'>info@freestylecyclists.org</a> so we can help you sort it out.</P>
        <P><a href="<?=get_site_url();?>/sign-the-petition-to-reform-helmet-law/">Click here to add your name to the petition to reform bicycle helmet laws</a></p>
        <?php
    } else { ?>
        <h2>Thanks for confirming your email address with Freestyle Cyclists</h2>
        You can update any details below. If you would like to be kept informed, upgrade your notification option below:
        <form name="confirm">
            <table border="0">
                <tbody>
                <tr><td class="leftcol">name:</td><td class="rightcol"><?=$sig->post_title;?></td></tr>
                <tr valign="top"><td class="leftcol"><input name="fs_signature_public" class="inputc" value="y" <?=($custom['fs_signature_public'][0] === "y" ? "checked='checked'" : "");?> id="public" type="checkbox"></td><td>Show my name on this website</td></tr>
                <tr valign="top"><td class="leftcol">email:</td><td class="rightcol"><?=$custom['fs_signature_email'][0];?></td></tr>
                <tr valign="top"><td class="leftcol">Email me:</td><td>
                        <input type="radio" value="" name="fs_signature_newsletter" <?=($custom['fs_signature_newsletter'][0] === "" ? "checked" : "")?>>Never<br/>
                        <input type="radio" value="y" name="fs_signature_newsletter" <?=($custom['fs_signature_newsletter'][0] === "y" ? "checked" : "")?>>Occasionally: When something important is happening<br/>
                        <input type="radio" value="m" name="fs_signature_newsletter" <?=($custom['fs_signature_newsletter'][0]==="m" ? "checked" : "")?>>More often: Keep me updated</td></tr>

                <tr><td class="leftcol">Country:</td><td><select id="country" class="inputc" name="fs_signature_country" style="width: 200px;">
                <option value="">Please select</option>
                <?php
                $fs_country = fs_country();
                foreach( $fs_country as $ab => $title ) { ?>
                    <option value="<?=$ab;?>" <?=($ab===$custom['fs_signature_country'][0] ? "selected" : "")?>><?php echo $title;?></option>
                <?php } ?>
                </select></td></tr>

                <tr><td class="state leftcol <?=($custom['fs_signature_country'][0]==="AU" ? "" : "removed")?>">State:</td><td class="rightcol state <?=($custom['fs_signature_country'][0]==="AU" ? "" : "removed")?>">
                        <select name="fs_signature_state" class="inputc">
                <?php
                $fs_states = fs_states();
                foreach( $fs_states as $ab => $title ) { ?>
                    <option value="<?=$ab;?>" <?=($custom['fs_signature_state'][0] === $ab ? "selected" : "")?>><?php echo $title;?></option>
                <?php } ?>
                </select></td></tr>

                <tr><td class="leftcol">Comment:</td><td class="rightcol"><textarea id="message" name="excerpt" class="inputc"><?=$sig->post_excerpt?></textarea><br><span class="smallfont">Comments are subject to moderation</span></td></tr>
                <tr><td colspan="2"><button type="button" id="confirmButton">Confirm</button></td></tr>
                <tr><td colspan="2"><div id="ajax-loading" class="farleft"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></div></td></tr>
                <tr><td colspan="2"><div id="returnMessage"></div></td></tr>
            </tbody></table>
            <input name="action" value="confirmSignature" type="hidden">
            <input name="id" value="<?=$sig->ID?>" type="hidden">
            <input name="secretkey" value="<?=$secret;?>" type="hidden">
            <?php wp_nonce_field( "fs_confirm_sig_" . $sig->ID, "fs_nonce");?>
        </form>
    <?php } ?>
    <P><a href="<?=get_site_url();?>/signatures">See who has signed</a></p>
    <?php
    return ob_get_clean();
}
add_shortcode('confirm', 'fs_signature_confirm' );
/*
 * Shortcode for displaying signatures, paginated
 */
add_action('init', 'register_signature_signatures_script');
add_action('wp_footer', 'enqueue_signature_signatures_script');
function register_signature_signatures_script() {
    wp_register_script( 'angular', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.18/angular.min.js", 'jquery' );
    wp_register_script( 'angular-animate', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.18/angular-animate.min.js", array( 'angular', 'jquery' ) );
    wp_register_script('signatures',  plugins_url( 'js/signatures.js' , __FILE__ ), array('jquery', 'angular') );
}
function enqueue_signature_signatures_script() {
	global $add_signature_signatures_script;

	if ( ! $add_signature_signatures_script )
		return;

        wp_enqueue_script('angular');
        wp_enqueue_script('angular-animate');
	wp_enqueue_script('signatures');
}
function fs_signature_signatures (  ) {
    global $add_signature_signatures_script;
    $add_signature_signatures_script = true;
    
    $rows_per_page = 15;
    $sigs = get_sigs( 0, $rows_per_page ); // first lot of sigs are loaded with the page
    ob_start();
    ?>
    <div class="row" ng-app="signaturesApp" ng-controller="signaturesCtrl">
        <script type="text/javascript">
            _sigs = <?=json_encode($sigs)?>;
            <?php
            global $wpdb;
            $query = $wpdb->prepare('select count(*) from ' . $wpdb->posts . ' where post_type="fs_signature" and post_status="private"', '' );
            $pages = $wpdb->get_col( $query );
            $pages = floor ( ($pages[0] + 0.9999) / $rows_per_page ) + 1;
            if(!$pages) $pages = 1;
            $data = array('pages'=>$pages);
            $data['rows_per_page'] = $rows_per_page;
            ?>
            _data = <?=json_encode($data)?>;
        </script>
        <table id="signatures" border="0" width="90%">
            <tbody>
                <tr><th width="120">Name</th><th width="100">Location</th><th>Date</th>
                <?php if(current_user_can('moderate_comments')) { ?>
                    <th>Admin</th>
                <?php } ?>
                <th>Comment</th></tr>
                <tr ng-repeat="sig in sigs">
                    <td>{{sig.name}}</td>
                    <td>{{sig.location}}</td>
                    <td>{{sig.date}}</td>
                    <?php if(current_user_can('moderate_comments')) { ?>
                    <td><a ng-hide="sig.moderate==='y' || sig.comment===''" ng-click="moderate(sig)" href="#">Approve</a><span ng-hide="sig.moderate==='y' || sig.comment===''"> | </span>
                        <a ng-hide="sig.comment===''" href="<?=get_site_url();?>/wp-admin/post.php?post={{sig.id}}&action=edit">Edit</a></td>
                    <?php } ?>
                    <td>{{sig.comment}}</td>
                </tr>
            </tbody>
        </table>
        <div id="ajax-loading" ng-class="{'farleft':!showLoading}"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></div>

        <div>
            <a href="<?=get_site_url();?>/sign-the-petition-to-reform-helmet-law/">Click here to sign this petition</a>
        </div>
        <?php
        // pagination adapted from http://sgwordpress.com/teaches/how-to-add-wordpress-pagination-without-a-plugin/                    
        ?>
        <div ng-hide="data.pages===1" class="pagination"><span>Page {{paged}} of {{data.pages}}</span>
            <a ng-show="paged>2 && paged > range+1 && showitems<data.pages" ng-click="gotoPage(1)">&laquo; First</a>
            <a ng-show="paged>1 && showitems<data.pages" ng-click='gotoPage(paged-1)'>&lsaquo; Previous</a>

            <span ng-show='data.pages!==1' ng-repeat='i in pagearray'>
                <span ng-show='paged===i' class="current">{{i}}</span>
                <a ng-hide="paged===i" ng-click="gotoPage(i)" class="inactive">{{i}}</a>
            </span>

            <a ng-show='paged<data.pages && showitems<data.pages' onclick='gotoPage(paged+1)'>Next &rsaquo;</a>
            <a ng-show='paged<data.pages-1 && paged+range-1<data.pages && showitems < data.pages' ng-click='gotoPage(data.pages)'>Last &raquo;</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('signatures', 'fs_signature_signatures' );
/* 
 * Shortcode for signature submission form
 */
function fs_page_sign ( $atts ) { 
    $a = shortcode_atts( array(
        'narrow' => '0',
        'popup' => '0',
    ), $atts );
    $narrow = $a['narrow']==='1';
    $popup = $a['popup']==='1';
    
    ob_start() ?>

    <form name="register<?=($popup ? "_popup" : "");?>">
        <table border="0"<?= ($narrow ? ' width="275"' : '');?>>
            <tbody>
            <tr><td class="leftcol">name:</td><td class="rightcol"><input class="inputc" type="text" name="title" id="name<?=($popup ? "_popup" : "");?>"></td></tr>
            <tr valign="top"><td class="leftcol"><input name="fs_signature_public" class="inputc" value="y" checked="checked" id="public<?=($popup ? "_popup" : "");?>" type="checkbox"></td><td>Show my name on this website</td></tr>
            <tr valign="top"><td class="leftcol">email:</td><td class="rightcol"><input type="email" class="inputc" name="fs_signature_email" id="email<?=($popup ? "_popup" : "");?>" title="email address"><br>
                    <div class="smallfont">An email will be sent to you to confirm this address, to ensure the integrity of your registration.</div></td></tr>
            <tr valign="top"><td class="leftcol"><input name="fs_signature_newsletter" class="inputc" value="y" checked="checked" id="newsletter" type="checkbox"></td><td class="medfont">Send me an occasional email if something really important is happening.</td></tr>

            <tr><td class="leftcol">Country:</td><td><select id="country<?=($popup ? "_popup" : "");?>" class="inputc" name="fs_signature_country" style="width: 200px;">
            <option value="" selected="selected">Please select</option>
            <?php
            $fs_country = fs_country();
            foreach( $fs_country as $ab => $title ) { ?>
                <option value="<?=$ab;?>"><?php echo $title;?></option>
            <?php } ?>
            </select></td></tr>

            <tr><td class="state<?=($popup ? "_popup" : "");?> leftcol removed">State:</td><td class="rightcol state<?=($popup ? "_popup" : "");?> removed"><select name="fs_signature_state" class="inputc">
            <?php
            $fs_states = fs_states();
            foreach( $fs_states as $ab => $title ) { ?>
                <option value="<?=$ab;?>"><?php echo $title;?></option>
            <?php } ?>
            </select></td></tr>

            <tr><td class="leftcol"><input id="simpleTuring<?=($popup ? "_popup" : "");?>" name="areYouThere" type="checkbox" value="y" class="inputc"></td><td class="medfont">Tick this box to show you are not a robot</td></tr>
            <tr><td class="leftcol">Comment:</td><td class="rightcol"><textarea name="excerpt" class="inputc"></textarea><br><div class="smallfont">Comments are subject to moderation</div></td></tr>
            <tr><td colspan="2"><button type="button" id="saveButton<?=($popup ? "_popup" : "");?>">Save</button></td></tr>
            <tr><td colspan="2"><div id="ajax-loading<?=($popup ? "_popup" : "");?>" class="farleft"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif"></div></td></tr>
            <tr><td colspan="2"><div id="returnMessage<?=($popup ? "_popup" : "");?>"></div></td></tr>
        </tbody></table>
        <input name="action" value="newSignature" type="hidden">
        <?php wp_nonce_field( "fs_new_sig", "fs_nonce");?>
    </form>
<?php return ob_get_clean();
}
add_shortcode('signature', fs_page_sign );

/*
 * change the label for title (for this custom post) from Title to Name
 */
add_filter( 'enter_title_here', 'fs_signature_enter_title' );
function fs_signature_enter_title( $input ) {
    global $post_type;

    if ( 'fs_signature' == $post_type ) {
        return __( 'Enter Name here' );
    }
    return $input;
}
function fs_signatures_enqueue_scripts(  ) {
    global $post;
    if( $post->post_type !== 'fs_signature' ) return;
    wp_enqueue_style( 'admin-style', plugins_url( 'css/admin-style.css' , __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'fs_signatures_enqueue_scripts' );
/*
 * set cookies for referrer and campaign
 */
add_action( 'wp_head', 'fs_signature_head_cookies' );
function fs_signature_head_cookies() {
    if(isset($_COOKIE['referrer'])) {
        $referrer = $_COOKIE['referrer'];
    } else {
        $referrer = $_SERVER['HTTP_REFERER'];
        setcookie('referrer', $referrer, 0, '/' );
    }

    if( isset($_GET['campaign']) ) {
        $campaign = $_GET['campaign'];
        setcookie('campaign', $campaign, 0, '/');
    } else if(isset($_COOKIE['campaign'])) {
        $campaign = $_COOKIE['campaign'];
    }
}