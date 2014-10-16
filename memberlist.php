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
    wp_register_style('itemstyle', plugins_url('css/style.css', __FILE__ ) );
}
function enqueue_itemlist_script() {
	global $add_itemlist_script;

	if ( ! $add_itemlist_script )
		return;

        wp_enqueue_script('angular');
        wp_enqueue_script('angular-animate');
	wp_enqueue_script('itemlist');
        wp_enqueue_style('itemstyle' );
}

add_shortcode('otu_itemlist', 'otu_itemlist' );

function otu_itemlist (  ) {
    global $add_itemlist_script;
    $add_itemlist_script = true;
    
    $rows_per_page = 10;
    $items = get_items( 0, $rows_per_page ); // first lot of items are loaded with the page
    global $wpdb;
    $query = $wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->users . ' u LEFT JOIN ' . $wpdb->usermeta  . ' m ON m.user_id=u.ID LEFT JOIN ' . $wpdb->usermeta . ' d ON d.user_id=u.ID '
            . 'WHERE m.meta_key="' . $wpdb->base_prefix . 'user_level" AND m.meta_value=%d AND d.meta_key="pmpro_do_not_contact" and d.meta_value=0', 0 );
    $nitems = $wpdb->get_col( $query );
    $pages = floor ( ($nitems[0] + 0.9999) / $rows_per_page );
    if(!$pages) $pages = 1;
    $data = array('pages'=>$pages);
    $data['ajaxurl'] = admin_url( 'admin-ajax.php' );
    $data['rows_per_page'] = $rows_per_page;
    $data['items'] = $items;
    ob_start();
    ?>
    <div class="row" ng-app="itemsApp" ng-controller="itemsCtrl">
        <script type="text/javascript">
            _data = <?=json_encode($data)?>;
        </script>
        <div id="letters">
        <?php
        foreach ( range("A", "Z") as $char) {
            echo "<div class='letter' ng-class='{selected:(letter==\"" . $char . "\")}' ng-click='setletter(\"" . $char . "\")'>" . $char . "</div>";
        }
        ?>
        </div>
        <table id="items" border="0" class="listitems" width="90%" ng-cloak>
            <tbody>
                <tr><th width="120">Name</th><th width="100">Class</th><th>email</th><th>Home phone</th><th>Mobile phone</th><th>Business phone</th><th>&nbsp;</th><th>&nbsp;</th></tr>
                <tr ng-repeat="item in data.items">
                    <td>{{item.name}}</td>
                    <td>{{item.class}}</td>
                    <td><a href="mailto:{{item.email}}" target="_blank">{{item.email}}</a></td>
                    <td>{{item.homephone}}</td>
                    <td>{{item.mobilephone}}</td>
                    <td>{{item.businessphone}}</td>
                    <td>{{(item.deceased=="1" ? "deceased" : "")}}</td>
                    <td><i class="fa fa-folder-open-o pull-left" ng-click="show(item)"></i></td>
                </tr>
            </tbody>
        </table>
        <div id="item">
            <table border="0" width="90%" ng-cloak>
                <tbody>
                    <tr>
                        <td colspan="6">&nbsp;</td>
                        <td class="alignright"><i class="fa fa-times" ng-click="hide()"></i></td>
                    </tr>
                    <tr>
                        <td>Name</td><td width="100">Class</td><td>email</td><td>Home phone</td><td>Mobile phone</td><td>Business phone</td><td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>{{item.name}}</td>
                        <td>{{item.class}}</td>
                        <td><a href="mailto:{{item.email}}" target="_blank">{{item.email}}</a></td>
                        <td>{{item.homephone}}</td>
                        <td>{{item.mobilephone}}</td>
                        <td>{{item.businessphone}}</td>
                        <td>{{(item.deceased=="1" ? "deceased" : "")}}</td>
                    </tr>
                    <tr>
                        <td colspan="2">Address</td>
                        <td>City</td>
                        <td>State</td>
                        <td colspan="3">Postcode</td>
                    </tr>
                    <tr>
                        <td>{{item.address1}}</td>
                        <td>{{item.address2}}</td>
                        <td>{{item.city}}</td>
                        <td>{{item.state}}</td>
                        <td colspan="3">{{item.postcode}}</td>
                    </tr>
                </tbody>
            </table>
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
 * showing items to the public - called from ajax wrapper and also when loading page initially
 */
function get_items( $first_item, $rows_per_page, $letter='' ){
    global $wpdb;
    $params = array();
    if( $letter != '' ) $params[] = $letter;
    $params[] = $first_item;
    $params[] = $rows_per_page;
    $query = $wpdb->prepare ( 
        "SELECT u.user_email as email, u.display_name as name, u.ID FROM " . 
        $wpdb->users . " u" .
        " LEFT JOIN $wpdb->usermeta m ON m.user_id=u.ID AND m.meta_key='" . $wpdb->base_prefix . "user_level' " .
        " LEFT JOIN $wpdb->usermeta d ON d.user_id=u.ID AND d.meta_key='pmpro_do_not_contact'" .
        " LEFT JOIN $wpdb->usermeta l ON l.user_id=u.ID AND l.meta_key='pmpro_blastname'" .
        " WHERE m.meta_value=0 AND d.meta_value=0" .
        ( $letter == '' ? "" : " AND SUBSTRING(l.meta_value, 1, 1)=%s" ) .
        " ORDER BY l.meta_value, name" .
        " LIMIT %d,%d",
        $params
    );
//    echo $query . "<br/>";
    $rows = $wpdb->get_results ( $query );
    $items = array();
    foreach ( $rows as $row ) {
        $custom = get_user_meta( $row->ID );
        $item = array (
            'email'=>$row->email,
            'name'=>$row->name,
            'class'=>$custom['pmpro_class'][0],
            'homephone'=>$custom['pmpro_bphone'][0],
            'mobilephone'=>$custom['pmpro_bmobile'][0],
            'businessphone'=>$custom['pmpro_bbusiness'][0],
            'deceased'=>$custom['pmpro_deceased'][0],
            'ID'=>$row->ID,
            'address1'=>$custom['pmpro_baddress1'][0],
            'address2'=>isset ( $custom['pmpro_baddress2'] ) ? $custom['pmpro_baddress2'][0] : "",
            'state'=>$custom['pmpro_bstate'][0],
            'city'=>$custom['pmpro_bcity'][0],
            'postcode'=>$custom['pmpro_bzipcode'][0],
        );
        $items[] = $item;
    }
    return $items;
}
/*
 * AJAX wrapper to get sigs
 */
add_action( 'wp_ajax_CBDWeb_get_items', 'CBDWeb_get_items' );
add_action( 'wp_ajax_nopriv_CBDWeb_get_items', 'CBDWeb_get_items' );

function CBDWeb_get_items() {
    $rows_per_page = $_POST['rows_per_page'];
    $page = $_POST['page'];
    $letter = $_POST['letter'];
    $first_item = ( $page - 1 ) * $rows_per_page;
    echo json_encode( get_items( $first_item, $rows_per_page, $letter) );
    die;
}