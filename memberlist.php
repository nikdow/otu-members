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
    
    $rows_per_page = 15;
    $items = get_items( 0, $rows_per_page ); // first lot of items are loaded with the page
    ob_start();
    ?>
    <div class="row" ng-app="itemsApp" ng-controller="itemsCtrl">
        <script type="text/javascript">
            _items = <?=json_encode($items)?>;
            <?php
            global $wpdb;
            $query = $wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->users . ' u LEFT JOIN ' . $wpdb->usermeta  . ' m ON m.user_id=u.ID LEFT JOIN ' . $wpdb->usermeta . ' d ON d.user_id=u.ID '
                    . 'WHERE m.meta_key="' . $wpdb->base_prefix . 'user_level" AND m.meta_value=%d AND d.meta_key="pmpro_do_not_contact" and d.meta_value=0', 0 );
            $pages = $wpdb->get_col( $query );
            $pages = floor ( ($pages[0] + 0.9999) / $rows_per_page ) + 1;
            if(!$pages) $pages = 1;
            $data = array('pages'=>$pages);
            $data['ajaxurl'] = admin_url( 'admin-ajax.php' );
            $data['rows_per_page'] = $rows_per_page;
            ?>
            _data = <?=json_encode($data)?>;
        </script>
        <table id="items" border="0" width="90%" ng-cloak>
            <tbody>
                <tr><th width="120">Name</th><th width="100">Class</th><th>email</th><th>Home phone</th><th>Mobile phone</th><th>Business phone</th><th>&nbsp;</th></tr>
                <tr ng-repeat="item in items">
                    <td>{{item.name}}</td>
                    <td>{{item.class}}</td>
                    <td>{{item.email}}</td>
                    <td>{{item.homephone}}</td>
                    <td>{{item.mobilephone}}</td>
                    <td>{{item.businessphone}}</td>
                    <td><i class="fa fa-binoculars"></i></td>
                </tr>
            </tbody>
        </table>
        <div id="ajax-loading" ng-class="{'farleft':!showLoading}"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif" ng-cloak></div>
        <?php
        // pagination adapted from http://sgwordpress.com/teaches/how-to-add-wordpress-pagination-without-a-plugin/                    
        ?>
        <div ng-hide="data.pages===1" class="pagination" ng-cloak>
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
function get_items( $first_item, $rows_per_page ){
    global $wpdb;
    $query = $wpdb->prepare ( 
        "SELECT u.user_email as email, u.display_name as name, u.ID, umc.meta_value AS class, umh.meta_value AS homephone, umm.meta_value AS mobilephone, umb.meta_value as businessphone FROM " . 
        $wpdb->users . " u" .
        " LEFT JOIN $wpdb->usermeta m ON m.user_id=u.ID AND m.meta_key='" . $wpdb->base_prefix . "user_level' " .
        " LEFT JOIN $wpdb->usermeta umc ON umc.user_id=u.ID AND umc.meta_key='pmpro_class'" . 
        " LEFT JOIN $wpdb->usermeta umh ON umh.user_id=u.ID AND umh.meta_key='pmpro_bphone'" .
        " LEFT JOIN $wpdb->usermeta umm ON umm.user_id=u.ID AND umm.meta_key='pmpro_bmobile'" .
        " LEFT JOIN $wpdb->usermeta umb ON umb.user_id=u.ID AND umb.meta_key='pmpro_bbusiness'" .
        " LEFT JOIN $wpdb->usermeta d ON d.user_id=u.ID AND d.meta_key='pmpro_do_not_contact'" .
        ' WHERE m.meta_value=0 AND d.meta_value=0' .
        " LIMIT %d,%d",
        $first_item, $rows_per_page
    );
    $rows = $wpdb->get_results ( $query );
    return $rows;
}
/*
 * AJAX wrapper to get sigs
 */
add_action( 'wp_ajax_CBDWeb_get_items', 'CBDWeb_get_items' );
add_action( 'wp_ajax_nopriv_CBDWeb_get_items', 'CBDWeb_get_items' );

function CBDWeb_get_items() {
    $rows_per_page = $_POST['rows_per_page'];
    $page = $_POST['page'];
    $first_item = ( $page - 1 ) * $rows_per_page;
    echo json_encode( get_items( $first_item, $rows_per_page) );
    die;
}