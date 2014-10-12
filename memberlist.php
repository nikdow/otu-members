<?php

/*
 * Shortcode for displaying members, paginated
 */
add_action('init', 'register_memberlist_script');
add_action('wp_footer', 'enqueue_memberlist_script');
function register_memberlist_script() {
    wp_register_script( 'angular', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.26/angular.min.js", 'jquery' );
    wp_register_script( 'angular-animate', "//ajax.googleapis.com/ajax/libs/angularjs/1.2.26/angular-animate.min.js", array( 'angular', 'jquery' ) );
    wp_register_script('memberlist',  plugins_url( 'js/memberlist.js' , __FILE__ ), array('jquery', 'angular') );
}
function enqueue_memberlist_script() {
	global $add_memberlist_script;

	if ( ! $add_memberlist_script )
		return;

        wp_enqueue_script('angular');
        wp_enqueue_script('angular-animate');
	wp_enqueue_script('memberlist');
}
function otu_memberlist (  ) {
    global $add_memberlist_script;
    $add_memberlist_script = true;
    
    $rows_per_page = 15;
    $members = get_members( 0, $rows_per_page ); // first lot of sigs are loaded with the page
    ob_start();
    ?>
    <div class="row" ng-app="membersApp" ng-controller="membersCtrl">
        <script type="text/javascript">
            _members = <?=json_encode($members)?>;
            <?php
            global $wpdb;
            $query = $wpdb->prepare('select count(*) from ' . $wpdb->users . ' where post_type="fs_signature" and post_status="private"', '' );
            $pages = $wpdb->get_col( $query );
            $pages = floor ( ($pages[0] + 0.9999) / $rows_per_page ) + 1;
            if(!$pages) $pages = 1;
            $data = array('pages'=>$pages);
            $data['rows_per_page'] = $rows_per_page;
            ?>
            _data = <?=json_encode($data)?>;
        </script>
        <table id="members" border="0" width="90%" ng-cloak>
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
                    <td class="fs-members-comments">{{sig.comment}}</td>
                </tr>
            </tbody>
        </table>
        <div id="ajax-loading" ng-class="{'farleft':!showLoading}"><img src="<?php echo get_site_url();?>/wp-includes/js/thickbox/loadingAnimation.gif" ng-cloak></div>

        <div>
            <a href="<?=get_site_url();?>/sign-the-petition-to-reform-helmet-law/">Click here to sign this petition</a>
        </div>
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
add_shortcode('otu_memberlist', 'otu_memberlist' );

/* 
 * showing sigs to the public - called from ajax wrapper and also when loading page initially
 */
function get_members( $first_item, $rows_per_page ){
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
        " WHERE p.post_type='fs_signature' AND p.`post_status`='private' ORDER BY registered DESC LIMIT %d,%d", $first_item, $rows_per_page 
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