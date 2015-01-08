<?php

    function get_old_avatars() {

    $mimes = array(
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png',
    );    
        
    global $wpdb;
    $query = "SELECT * from avatar_temp";
    $rows = $wpdb->get_rows( $query );
    foreach ($rows as $row ) {
        $file = $row->avatar;
        echo "file = " . $file . " ";
        $regno = $row->regno;
        echo "regno = " . $regno . " ";
        $query2 = "SELECT user_id from $wpdb->usermeta WHERE meta_value=" . $row->regno;
        $row2 = $wpdb->get_rows( $query );
        $user_id = $row2[0]->user_id;
        echo "User ID = " . $user_id . " ";
        /* pretend the file was just uploaded */
        if ( preg_match ( "/.*\.(jpg|gif|png)$/", $file, $matches ) ) {
            $type = $mimes[$matches[1]];
            $file = array('name'=>$file, 'tmp_name'=>$file, 'type'=>$type );
            $avatar = wp_handle_upload( $file );
            update_user_meta( $user_id, 'basic_user_avatar', array( 'full' => $avatar['url'] ) );
            echo "success ";
        } else {
            echo " preg no match on file type ";
        }
        
        echo "<br/>";
    }
}
