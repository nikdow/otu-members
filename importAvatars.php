<?php

    function get_old_avatars() {

    $mimes = array(
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
    );    
        
    global $wpdb;
    $query = "SELECT * from avatar_temp";
    $rows = $wpdb->get_results( $query );
    foreach ($rows as $row ) {
        $file = $row->avatar;
        echo "file = " . $file . " ";
        $regno = $row->regno;
        echo "regno = " . $regno . " ";
        $query2 = "SELECT user_id from $wpdb->usermeta WHERE meta_value=" . $row->regno;
        $row2 = $wpdb->get_row( $query );
        $user_id = $row2->user_id;
        echo "User ID = " . $user_id . " ";
        /* pretend the file was just uploaded */
        if ( preg_match ( "/.*\.(jpg|gif|png)$/", $file, $matches ) ) {
            $type = $mimes[$matches[1]];
            $wp_upload_dir = wp_upload_dir();
            $file = array('name'=>$file, 'tmp_name'=>$upload_path['basedir'] . '/' . $file, 'type'=>$type );
            $avatar = wp_handle_upload( $file, array ( 'test_form' => false, 'test_size' => false, 'test_type'=>false, ) );
            if ( empty( $avatar['file'] ) ) {
				echo $avatar['error'] . " ";
            } else {
                update_user_meta( $user_id, 'basic_user_avatar', array( 'full' => $avatar['url'] ) );            
                echo "success ";
            }
        } else {
            echo " preg no match on file type ";
        }
        
        echo "<br/>";
    }
}
