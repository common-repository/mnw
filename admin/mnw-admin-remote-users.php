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

require_once 'mnw-lib.php';

function mnw_remote_users_parse_action() {
    $action = valid_input_set('action', array('default' => '',  'delete',
                                              'delete-selected'), $_REQUEST);

    if ($action === '') {
        return;
    }

    switch ($action) {
    case 'delete':
        if (!isset($_REQUEST['user'])) {
            return;
        }
        $user = $_REQUEST['user'];
        check_admin_referer('mnw-delete-user_' . $user);
        $users = array($user);
        break;
    case 'delete-selected':
        if (!isset($_POST['checked'])) {
            return;
        }
        check_admin_referer('mnw-bulk-users');
        $users = $_POST['checked'];
        break;
    }

    $user_count = count($users);
    if (delete_remote_users($users) < $user_count) {
        $msg = _n('Could not delete remote user.',
                  'Could not delete remote users.', $user_count, 'mnw');
    } else {
        $msg = _n('Remote user successfully deleted.',
                  'Remote users successfully deleted.', $user_count, 'mnw');
    }

    echo '<div id="message" class="updated fade"><p>' . $msg . '</p></div>';
}

function mnw_remote_users_options() {
    /* Perform action and display result message. */
    mnw_remote_users_parse_action();

    $paged = valid_input_fnc('paged', 'is_positive', 1, $_REQUEST);

    /* Get remote users. */
    list($users, $total) = get_remote_users($paged - 1);

    mnw_start_admin_page();
?>
<p><?php printf(__('<em>Subscribers</em> are users of an OMB service who listen to your notices. The messages of <em>subscribed users</em> get published to %s and may be displayed.', 'mnw'), get_bloginfo('title'));?></p>
<p><?php _e('If you delete a user, both subscriptions – if available – are removed.', 'mnw'); ?></p>
<p><?php _e('Note that the OpenMicroBlogging standard does not yet support a block feature; Therefore the remote user is not informed about a deletion. Moreover, if another user from the same service is subscribed to you, the remote service will probably publish your messages to deleted users as well.', 'mnw');?></p>
<p><?php _e('Likewise a user which is listed as subscriber may have canceled his subscription recently.', 'mnw');?></p>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <h3><?php _e('Remote microblog users', 'mnw'); ?></h3>
      <?php wp_nonce_field('mnw-bulk-users') ?>
      <div class="tablenav">
        <div class="alignleft actions">
          <select name="action">
            <option value="" selected="selected"><?php _e('Bulk Actions'); ?></option>
            <option value="delete-selected"><?php _e('Delete'); ?></option>
          </select>
          <input type="submit" name="doaction_active" value="<?php _e('Apply'); ?>" class="button-secondary action" />
        </div>
        <div class="tablenav-pages">
            <span class="displaying-num">
<?php
                printf(__('Displaying %s–%s of %s', 'mnw'),
                    number_format_i18n(($paged - 1) * 15 + 1),
                    number_format_i18n(min($paged * 15, $total)),
                    number_format_i18n($total));
?>
            </span>
<?php
            echo paginate_links(array(
                'base' => add_query_arg( 'paged', '%#%' ),
                'format' => '',
                'prev_text' => __('&laquo;'),
                'next_text' => __('&raquo;'),
                'total' => ceil($total / 15.0),
                'current' => $paged));
?>
        </div>
      </div>
      <div class="clear" />
<table class="widefat" cellspacing="0" id="users-table">
  <thead>
  <tr>
    <th scope="col" class="check-column"><input type="checkbox" /></th>
    <th scope="col"><?php _e('User', 'mnw'); ?></th>
    <th scope="col"><?php _e('Direction', 'mnw'); ?></th>
    <th scope="col"><?php _e('License', 'mnw'); ?></th>
    <th scope="col" class="action-links"><?php _e('Action'); ?></th>
  </tr>
  </thead>

  <tfoot>
  <tr>
    <th scope="col" class="check-column"><input type="checkbox" /></th>
    <th scope="col"><?php _e('User', 'mnw'); ?></th>
    <th scope="col"><?php _e('Direction', 'mnw'); ?></th>
    <th scope="col"><?php _e('License', 'mnw'); ?></th>
    <th scope="col" class="action-links"><?php _e('Action'); ?></th>
  </tr>
  </tfoot>

  <tbody class="users">

<?php
    if ($users == 0) {
?>
    <tr>
      <td colspan="4"> <?php _e('No remote users', 'mnw'); ?></td>
    </tr>
<?php
    } else {
      foreach ($users as $user) {
?>
      <tr>
        <th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='<?php echo $user['id']; ?>' /></th>
        <td><a href="<?php echo $user['url'];?>"><?php echo $user['nickname'];?></a></td>
        <td><?php if ($user['token'] && $user['resubtoken']) { _e('Both', 'mnw'); } elseif ($user['token']) { _e('Subscriber', 'mnw'); } else { _e('Subscribed user', 'mnw');} ?>
        <td><a href="<?php echo $user['license'];?>"><?php echo $user['license'];?></a></td>
        <td class='togl action-links'>
          <a href="<?php echo wp_nonce_url(
              $_SERVER['REQUEST_URI'] . '&amp;action=delete' . '&amp;user=' . $user['id'],
              'mnw-delete-user_' . $user['id']); ?>"
             title="<?php _e('Delete this user', 'mnw'); ?>">
            <?php _e('Delete', 'mnw'); ?>
          </a>
        </td>
      </tr>
<?php
      }
    }
?>
    </tbody>
</table>
    </form>
<?php
    mnw_finish_admin_page();
}

mnw_remote_users_options();
?>
