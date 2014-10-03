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
                <td><input type="text" name="pmpro_regimental_number" value="<?php echo esc_attr(get_the_author_meta( 'pmpro_regimental_number', $user->ID )); ?>" class="regular-text" /></td>
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
                <td><input type="checkbox" name="pmpro_deceased" value="1" <?php echo (get_the_author_meta( 'pmpro_deceased', $user->ID )==1 ? "SELECTED" : ""); ?> /></td>
            </tr>
            
        </table>
    <?php
}
