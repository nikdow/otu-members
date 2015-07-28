<?php
/*
 * Shortcode for displaying items, paginated
 */
add_action('init', 'register_itemlist_script');
add_action('wp_footer', 'enqueue_itemlist_script');
function register_itemlist_script() {
    wp_register_script( 'angular', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.26/angular.min.js", 'jquery' );
    wp_register_script( 'angular-animate', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.26/angular-animate.min.js", array( 'angular', 'jquery' ) );
    wp_register_script('itemlist',  plugins_url( 'js/itemlist.js' , __FILE__ ), array('jquery', 'angular') );
    wp_register_script('fileDownload', plugins_url( 'js/fileDownload.js', __FILE__ ), array('jquery') );
    wp_register_style('itemstyle', plugins_url('css/style.css', __FILE__ ) );
    wp_register_script( 'dialog', get_stylesheet_directory_uri() . '/ngDialog/js/ngDialog' . (WP_DEBUG ? '' : '.min') . '.js', array('jquery', 'angular' ) );
    wp_register_style('dialogstyle', get_stylesheet_directory_uri() . '/ngDialog/css/ngDialog' . (WP_DEBUG ? '' : '.min') . '.css' );
    wp_register_style('dialogdefault', get_stylesheet_directory_uri() . '/ngDialog/css/ngDialog-theme-default' . (WP_DEBUG ? '' : '.min') . '.css' );
    wp_register_style('dialogcustom', plugins_url('css/ngDialogCustom.css', __FILE__ ), array('dialogstyle', 'dialogdefault') );
}
function enqueue_itemlist_script() {
	global $add_itemlist_script;

	if ( ! $add_itemlist_script )
		return;

        wp_enqueue_script('angular');
        wp_enqueue_script('angular-animate');
	wp_enqueue_script('itemlist');
        wp_enqueue_script('fileDownload');
        wp_enqueue_style('itemstyle' );
        wp_enqueue_script('dialog');
        wp_enqueue_style('dialogstyle');
        wp_enqueue_style( 'dialogdefault' );
        wp_enqueue_style( 'dialogcustom' );
}

add_shortcode('otu_itemlist', 'otu_itemlist' );

function otu_itemlist (  ) {
    global $add_itemlist_script;
    $add_itemlist_script = true;
    
    $rows_per_page = 10;
    global $wpdb;
    /*
     * get membership levels
     */
    $query = "SELECT id, name FROM $wpdb->pmpro_membership_levels";
    $membertypes = $wpdb->get_results ( $query, OBJECT );
    
    $simplemembertypes = array('0');
    $membertypearr = array();
    foreach($membertypes as $membertype ) {
        $membertypearr[] = array('id'=>$membertype->id, 'name'=>$membertype->name );
        $simplemembertypes[] = $membertype->id; // enables inclusion of all membertypes in initial data call (but not deceased)
    }
    $query = get_query( 0, $rows_per_page, $simplemembertypes ); // first lot of items are loaded with the page
    $data = get_items ( $query, $rows_per_page );
    $data['membertypes'] = $membertypearr; // used to display membertype choosers
    $data['ajaxurl'] = admin_url('admin-ajax.php');
    $data['siteurl'] = get_site_url();
    $data['rows_per_page'] = $rows_per_page;
    
    /*
     * get States
     */
    $query = "SELECT IF(u.meta_value=\"Overseas\", \"ZZ\", u.meta_value) as mv FROM $wpdb->usermeta u " .
            "LEFT JOIN $wpdb->usermeta m ON m.user_id=u.user_id AND m.meta_key=\"wp_my0ord_user_level\" " .
            "WHERE u.meta_key=\"pmpro_bstate\" AND u.meta_value!=\"\" AND m.meta_value=0 GROUP BY u.meta_value ORDER BY mv";
    $results = $wpdb->get_results ( $query, OBJECT );
    $states = array();
    foreach($results as $result) {
        $states[] = ($result->mv==="ZZ" ? "Overseas" : $result->mv);
    }
    $data['states'] = $states;
    /*
     * get classes
     */
    $query = "SELECT meta_value AS clss FROM $wpdb->usermeta WHERE meta_key=\"pmpro_class\" GROUP BY meta_value";
    $results = $wpdb->get_results ( $query, OBJECT );
    $classes = array();
    foreach ( $results as $result ) {
        $match = preg_match('/^([\d]+)\/([\d]+)$/', $result->clss, $matches );
        if( $match ) {
            $term = intval( $matches[1] );
            $year = intval( $matches[2] );
            $arr = array('term'=>$term, 'year'=>$year );
            if( array_search ( $arr, $classes ) === false ) {
                $classes[] = $arr;
            }
        }
    }
    function sortclass($a, $b) {
        $va = $a['year'] * 1000 + $a['term'];
        $vb = $b['year'] * 1000 + $b['term'];
        if ( $va == $vb ) return 0;
        return ( $va < $vb ) ? -1 : 1;
    }
    usort ( $classes, "sortclass" );
    $clsses = array();
    foreach ( $classes as $class ) {
        $clsses[] =  $class['term'] . "/" . $class['year'];
    }
    $data['clsses'] = $clsses;
    
/*    $data['gallery'] =  
        do_shortcode ( '[wppa type="album" album="#owner,cbdweb,$Members"][/wppa]' ); // Use member cbdweb to create an empty container
*/    ob_start();
    ?>
    <div class="row" ng-app="itemsApp" ng-controller="itemsCtrl">
        <script type="text/javascript">
            _data = <?=json_encode($data)?>;
        </script>
        <script type="text/ng-template" id="templateId">
            <img src="{{item.avatar}}" border=0/>
            <h2>{{item.name}}</h2>
            <table border="0" width="100%">
                <tbody>
                    <tr>
                        <th width="100">Class</th><th>email</th><th>Home phone</th><th>Mobile phone</th><th>Business phone</th><th>&nbsp;</th>
                    </tr>
                    <tr>
                        <td>{{item.class}}</td>
                        <td><a href="mailto:{{item.email}}" target="_blank">{{item.email}}</a></td>
                        <td>{{item.homephone}}</td>
                        <td>{{item.mobilephone}}</td>
                        <td>{{item.businessphone}}</td>
                        <td>{{etc(item)}}</td>
                    </tr>
                    <tr>
                        <th colspan="2">Address</th>
                        <th>City</th>
                        <th>State</th>
                        <th colspan="3">Postcode</th>
                    </tr>
                    <tr>
                        <td colspan="2">{{item.address1}}, {{item.address2}}</td>
                        <td>{{item.city}}</td>
                        <td>{{item.state}}</td>
                        <td colspan="3">{{item.postcode}}</td>
                    </tr>
                    <tr>
                        <td>Vietnam Service</td>
                        <td>Partner</td>
                        <td>Awards</td>
                    </tr>
                    <tr>
                        <td>{{item.vietnam ? "Yes" : "No"}}</td>
                        <td>{{item.partner}}</td>
                        <td>{{item.awards}}</td>
                    </tr>        
                </tbody>
            </table>
            <div id="wppa-container-1">Searching for member's photographs...</div>
        </script>
        <div id='clsses'>
            <div class='clss wider' ng-class='{selected: (clss=="")}' ng-click='setclss("")'>All</div>
            <?php
            foreach ( $clsses as $clss ) {
                echo "<div class='clss' ng-class='{selected: (clss==\"" . $clss . "\")}' ng-click='setclss(\"" . $clss . "\")'>" . $clss . "</div>";
            }
            ?>
        </div>
        <div id='states'>
            <div class='state' ng-class='{selected: isState("")}' ng-click='togglestate("")'>Unknown</div>
            <?php
            foreach ( $states as $state ) {
                echo "<div class='state' ng-class='{selected: isState(\"" . $state . "\")}' ng-click='togglestate(\"" . $state . "\")'>" . $state . "</div>";
            }
            ?>
        </div>
        <div id='membertypes'>
            <div class='membertype' ng-class='{selected: isMemberType("") }' ng-click='togglemembertype("")'>Unfinancial</div>
            <?php
            foreach ( $membertypes as $membertype ) {
                echo "<div class='membertype' ng-class='{selected: isMemberType(\"" . $membertype->id . "\") }' ng-click='togglemembertype(\"" . $membertype->id . "\")'>" . $membertype->name . "</div>";
            }
            ?>
            <div class='membertype' ng-class='{selected: isMemberType("d")}' ng-click='togglemembertype("d")'>Deceased</div>
            <div class="search"><form ng-submit="gotoPage(1)"><input placeholder="search" ng-model="search"></form></div>
        </div>
        <div id="letters">
            <div class='letter wider' ng-class='{selected: (letter=="")}' ng-click='setletter("")'>ALL</div>
            <?php
            foreach ( range("A", "Z") as $char) {
                echo "<div class='letter' ng-class='{selected:(letter==\"" . $char . "\")}' ng-click='setletter(\"" . $char . "\")'>" . $char . "</div>";
            }
            ?>
        </div>
        <div id='items'>
            <div ng-show="membertype.length===0">
                <h2>You have not selected any Member-types above</h2>
            </div>
            <div ng-show="state.length===0">
                <h2>You have not selected any locations above</h2>
            </div>
            <table border="0" class="listitems" width="90%" ng-cloak>
                <tbody>
                    <tr><th width="120">Name</th><th width="100">Class</th><th>email</th><th>Home phone</th><th>Mobile phone</th><th>Business phone</th><th>&nbsp;</th><th>&nbsp;</th></tr>
                    <tr ng-repeat="item in data.items">
                        <td><span class='hand' ng-click='show(item)'>{{item.name}}</span></td>
                        <td>{{item.class}}</td>
                        <td><a href="mailto:{{item.email}}" target="_blank">{{item.email}}</a></td>
                        <td>{{item.homephone}}</td>
                        <td>{{item.mobilephone}}</td>
                        <td>{{item.businessphone}}</td>
                        <td>{{etc(item)}}</td>
                        <?php
                        if ( current_user_can ( 'create_users' ) ) { ?>
                            <td><a href="<?=admin_url( 'user-edit.php' );?>?user_id={{item.ID}}"><i class="fa fa-folder-open-o pull-left"></i></a></td>                            
                            <?php
                        } else { ?>
                            <td class='hand'><i class="fa fa-folder-open-o pull-left" ng-click="show(item)"></i></td>
                        <?php } ?>
                    </tr>
                </tbody>
            </table>
        </div>
       
        <div id='download' ng-click='download()' ng-hide='membertype.length===0 || state.length===0'>
            download
        </div>
        <div id="ajax-loading" ng-class="{'farleft':!showLoading}"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif" ng-cloak></div>
        <?php
        // pagination adapted from http://sgwordpress.com/teaches/how-to-add-wordpress-pagination-without-a-plugin/                    
        ?>
        <div ng-hide="data.pages===1" class="pagination listitems" ng-cloak>
            <span>Page {{paged}} of {{data.pages}}</span>
            <a ng-show="paged>2 && paged > range+1 && showitems<data.pages" ng-click="gotoPage(1)">&laquo; First</a>
            <a ng-show="paged>1 && showitems<data.pages" ng-click='gotoPage(paged-1)'>&lsaquo; Previous</a>

            <span ng-show='data.pages!==1' ng-repeat='i in pagearray'>
                <span ng-show='paged===i' class="current">{{i}}</span>
                <a ng-hide="paged===i" ng-click="gotoPage(i)" class="inactive">{{i}}</a>
            </span>

            <a ng-show='paged<data.pages && showitems<data.pages' ng-click='gotoPage(paged+1)'>Next &rsaquo;</a>
            <a ng-show='paged<data.pages-1 && paged+range-1<data.pages && showitems < data.pages' ng-click='gotoPage(data.pages)'>Last &raquo;</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
/* 
 * showing items to members - called from ajax wrapper and also when loading page initially
 */
function get_avatar_url($get_avatar){
    preg_match("/src=['\"](.*?)['\"]/i", $get_avatar, $matches);
    return $matches[1];
}
function get_query( $first_item, $rows_per_page, $membertypes=array(), $letter='', $states=array(), $clss='', $search='' ){
    $paged = $first_item >= 0;
    global $wpdb;
    $params = array();
    if ( $search != '' ) {
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    if( $letter != '' ) $params[] = $letter;
    if( $clss != '' ) {
        preg_match('/^([\d]+)\/([\d]+)$/', $clss, $matches );
        $params[] = intval ( $matches[1] );
        $params[] = intval ( $matches[2] );
    }
    $membertypearr = array();
    foreach ( $membertypes as $membertype ) {
        $membertypearr[] = "%d";
        if ( $membertype == "d" ) { // deceased
            $params[] = -1;
        } else {
            $params[] = $membertype;
        }
    }
    $membertypestr = join(",", $membertypearr );
    if ( Count($states)>0 ) {
        $statearr = array();
        foreach ( $states as $state ) {
            $statearr[] = "%s";
            $params[] = $state;
        }
        $statestr = join(",", $statearr );
    }
    if ( $paged ) { // if negative, get all rows
        $params[] = $first_item;
        $params[] = $rows_per_page;
    }
    $query = 
        "SELECT" . ( $paged ? " SQL_CALC_FOUND_ROWS" : "" ) .
        " IF(p.membership_id IS NULL, 0, p.membership_id) as ml," .
        ( $paged ? " wppa.id as album," : "" ) .
        " u.user_email as email, u.display_name as name, u.ID FROM " . $wpdb->users . " u" .
        " LEFT JOIN $wpdb->usermeta m ON m.user_id=u.ID AND m.meta_key='" . $wpdb->base_prefix . "user_level' " .
        " LEFT JOIN $wpdb->usermeta l ON l.user_id=u.ID AND l.meta_key='pmpro_blastname'" .
        ( $clss == '' ? "" : " LEFT JOIN $wpdb->usermeta c ON c.user_id=u.ID AND c.meta_key='pmpro_class'" ) .
        ( Count($states) == 0 ? "" : " LEFT JOIN $wpdb->usermeta s ON s.user_id=u.ID AND s.meta_key='pmpro_bstate'" ) .
        " LEFT JOIN $wpdb->pmpro_memberships_users p ON p.user_id=u.ID AND p.status='active'" .
        ( $paged ? " LEFT JOIN $wpdb->prefix" . "wppa_albums wppa ON u.user_login=wppa.owner" : "" ) .
        " LEFT JOIN $wpdb->usermeta d ON d.user_id=u.ID AND d.meta_key=\"pmpro_deceased\"" .
        " WHERE m.meta_value=0" .
        ( $search == '' ? "" : " AND ( u.display_name LIKE %s OR u.user_email LIKE %s )" ) .
        ( $letter == '' ? "" : " AND SUBSTRING(l.meta_value, 1, 1)=%s" ) .
        ( $clss == '' ? "" : " AND SUBSTRING_INDEX(c.meta_value, '/', 1)=%d AND SUBSTRING_INDEX(c.meta_value, '/', -1)=%d" ) .
        " AND IF( d.meta_value='1', -1, IF( p.membership_id IS NULL, 0, p.membership_id ) ) IN (" . $membertypestr . ")" .
        ( Count($states)==0 ? "" : " AND s.meta_value IN (" . $statestr . ")" ) .
        " ORDER BY l.meta_value, name" .
        ( $paged ? " LIMIT %d,%d" : "" );
    if ( Count ( $params ) > 0 ) {
        $query = $wpdb->prepare ( $query, $params );
    }
    return $query;
}
function get_items ( $query, $rows_per_page ) {
    
    global $wpdb;
    
    $rows = $wpdb->get_results ( $query );
    $nitems = $wpdb->get_var('SELECT FOUND_ROWS();');
    
    $items = array();
    foreach ( $rows as $row ) {
        // get meta values directly, WP can be inefficient
        $queryc = $wpdb->prepare ( "SELECT meta_key, meta_value
             FROM $wpdb->usermeta
             WHERE user_id=%u", $row->ID );
        $customs = $wpdb->get_results( $queryc );
        $custom = array();
        foreach ( $customs as $c ) {
            $custom[$c->meta_key] = $c->meta_value;
        }
        
        $hidecontactdetails = 
            ( isset ( $custom['pmpro_do_not_contact'] ) && $custom['pmpro_do_not_contact']==1 ) ||
            ( isset ( $custom['pmpro_deceased'] ) && $custom['pmpro_deceased']==1 );
        
        $item = array (
            'email'=> $hidecontactdetails ? "" : $row->email,
            'name'=>$row->name,
            'membershiplevel'=>$row->ml,
            'class'=>( isset($custom['pmpro_class']) ? $custom['pmpro_class'] : ""),
            'homephone'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_bphone'] ) ? $custom['pmpro_bphone'] : "",
            'mobilephone'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_bmobile'] ) ? $custom['pmpro_bmobile'] : "",
            'businessphone'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_bbusiness'] ) ? $custom['pmpro_bbusiness'] : "",
            'deceased'=>isset ( $custom['pmpro_deceased'] ) ? $custom['pmpro_deceased'] : "",
            'ID'=>$row->ID,
            'address1'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_baddress1'] ) ? $custom['pmpro_baddress1'] : "",
            'address2'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_baddress2'] ) ? $custom['pmpro_baddress2'] : "",
            'state'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_bstate'] ) ? $custom['pmpro_bstate'] : "",
            'city'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_bcity'] ) ? $custom['pmpro_bcity'] : "",
            'postcode'=> $hidecontactdetails ? "" : isset ( $custom['pmpro_bzipcode'] ) ? $custom['pmpro_bzipcode'] : "",
            'avatar'=>get_avatar_url ( get_avatar( $row->ID ) ),
            'vietnam'=>isset ( $custom['vietnam'] ) ? $custom['vietnam'] : "",
            'awards'=>isset ( $custom['awards'] )  ? $custom['awards'] : "",
            'partner'=>isset ( $custom['partner'] ) ? $custom['partner'] : "",
            'album'=> $row->album,
        ); 
        $items[] = $item;
    }
    $data = array ( 'items'=>$items, 'query'=>$query );
    $pages = floor ( ($nitems - 0.9999) / $rows_per_page ) + 1;
    if(!$pages) $pages = 1;
    $data['pages'] = $pages;
    return $data;
}
/*
 * AJAX wrapper to get items
 */
add_action( 'wp_ajax_CBDWeb_get_items', 'CBDWeb_get_items' );
add_action( 'wp_ajax_nopriv_CBDWeb_get_items', 'CBDWeb_get_items' );

function CBDWeb_get_items() {
    $rows_per_page = $_POST['rows_per_page'];
    $page = $_POST['page'];
    $letter = $_POST['letter'];
    $clss = $_POST['clss'];
    $membertype = isset( $_POST['membertype'] ) ? $_POST['membertype'] : array();
    $state = isset ( $_POST['state'] ) ? $_POST['state'] : array();
    $first_item = ( $page - 1 ) * $rows_per_page;
    $search = $_POST['search'];
    $query = get_query( $first_item, $rows_per_page, $membertype, $letter, $state, $clss, $search );
    $data = get_items ( $query, $rows_per_page );

    header( "Content-Type: application/json" );
    echo json_encode( $data );
    die;
}

add_action( 'wp_ajax_CBDWeb_download_items', 'CBDWeb_download_items' );
add_action( 'wp_ajax_nopriv_CBDWeb_download_items', 'CBDWeb_download_items' );

function CBDWeb_download_items() { // because this can be a large file, output each row as it is retrieved. 
    /* c.f. paginated where the limit in the query prevents large retrieval and an array is used to create the JSON */
    $letter = $_GET['letter'];
    $clss = $_GET['clss'];
    $membertype = isset( $_GET['membertype'] ) ? $_GET['membertype'] : array();
    $state = isset ( $_GET['state'] ) ? $_GET['state'] : array();
    $search = $_GET['search'];
    ob_clean();
    download_send_headers("OTU_members_" . $letter . $clss . date("Y-m-d") . ".csv");

    $query = get_query ( -1, 0, $membertype, $letter, $state, $clss, $search );
    global $wpdb;
    $rows = $wpdb->get_results ( $query );

    $count = 0;
    $df = fopen("php://output", 'w');
    foreach ( $rows as $row ) {
        $queryc = $wpdb->prepare ( "SELECT meta_key, meta_value
             FROM $wpdb->usermeta
             WHERE user_id=%u", $row->ID );
        $customs = $wpdb->get_results( $queryc );
        $custom = array();
        foreach ( $customs as $c ) {
            $custom[$c->meta_key] = $c->meta_value;
        }
//        $custom = get_user_meta( $row->ID );
        $item = array (
            'email'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $row->email,
            'name'=>$row->name,
            'membershiplevel'=>$row->ml,
            'class'=>$custom['pmpro_class'],
            'homephone'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $custom['pmpro_bphone'],
            'mobilephone'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $custom['pmpro_bmobile'],
            'businessphone'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $custom['pmpro_bbusiness'],
            'deceased'=>$custom['pmpro_deceased'],
            'ID'=>$row->ID,
            'address1'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $custom['pmpro_baddress1'],
            'address2'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : isset ( $custom['pmpro_baddress2'] ) ? $custom['pmpro_baddress2'] : "",
            'state'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $custom['pmpro_bstate'],
            'city'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $custom['pmpro_bcity'],
            'postcode'=>$custom['pmpro_do_not_contact']==1 || $custom['pmpro_deceased']==1 ? "" : $custom['pmpro_bzipcode'],
        );
        if( ! $count ) { // header row
            fputcsv( $df, array_keys( $item ) );
        }
        $count++;
        fputcsv( $df, $item );
    }
    fclose($df);
    die;
}

function download_send_headers($filename) {
    // disable caching
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
    header("Set-Cookie: fileDownload=true; path=/");
}

/*
add_action( 'init', function() { 
    ps_register_shortcode_ajax( 'CBDWeb_get_items', 'CBDWeb_get_items' ); 
} );
// require_once plugin_dir_path ( __FILE__ ) . '../wp-photo-album-plus/wppa-non-admin.php';

function ps_register_shortcode_ajax( $callable, $action ) {

  if ( empty( $_POST['action'] ) || $_POST['action'] != $action )
    return;
  
  wppa_load_theme();
  call_user_func( $callable );
} */
