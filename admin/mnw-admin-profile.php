<?php
/**
 * This file is part of mnw.
 *
 * mnw - an OpenMicroBlogging compatible Microblogging plugin for Wordpress
 * Copyright (C) 2009, Adrian Lang
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function mnw_profile_options() {
    global $mnw_profile_options;
    mnw_start_admin_page();
?>
    <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <h3><?php _e('OMB profile', 'mnw'); ?></h3>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Nickname (1-64 characters, only lowercase letters and digits)', 'mnw'); ?></th>
                <td>
                    <input type="text" name="omb_nickname" value="<?php echo get_option('omb_nickname'); ?>" /><br />
                    <?php _e('The nickname is the name under which remote users will know you. You should consider thoroughly if you really want to change this setting.', 'mnw'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Full name (up to 255 characters)', 'mnw'); ?></th>
                <td><input type="text" class="regular-text" name="omb_full_name" value="<?php echo get_option('omb_full_name'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Bio (less than 140 characters)', 'mnw'); ?></th>
                <td><textarea cols="40" rows="3" name="omb_bio"><?php echo get_option('omb_bio'); ?></textarea></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Location (less than 255 characters)', 'mnw'); ?></th>
                <td><input type="text" class="regular-text" name="omb_location" value="<?php echo get_option('omb_location'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Avatar URL', 'mnw'); ?></th>
                <td><input type="text" class="regular-text" name="omb_avatar" value="<?php echo get_option('omb_avatar'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('License URL', 'mnw'); ?></th>
                <td><input type="text" class="regular-text" name="omb_license" value="<?php echo get_option('omb_license'); ?>" /></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="<?php echo join(",", $mnw_profile_options); ?>" />
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
<?php
    mnw_finish_admin_page();
}

mnw_profile_options();
?>
