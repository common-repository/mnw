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

/* Perform action and display result message. */
function mnw_notices_parse_action($show)
{
    $action = valid_input_set('action', array('default' => '', 'delete',
                                              'delete-selected',
                                              __('Send notice', 'mnw')),
                              $_REQUEST);

    if ($action === '') {
        return;
    }

    $msg = '';

    switch ($action) {
    case __('Send notice', 'mnw'):
        $msg = mnw_notices_send();
        break;
    case 'delete': case 'delete-selected':
        $msg = mnw_notices_delete($show, $action);
        break;
    }

    if ($msg === false) {
        /* Do not show a message. */
        return;
    }
    echo '<div id="message" class="updated fade"><p>' . $msg . '</p></div>';
}

function mnw_notices_send()
{
    if (!isset($_POST['mnw_notice'])) {
        return false;
    }

    $content = $_POST['mnw_notice'];
    if ($content === '') {
        return __('You did not specify a notice text.', 'mnw');
    }

    /* Do not display the message in the input box if it has been
       sent. */
    unset($_POST['mnw_notice']);

    check_admin_referer('mnw-new_notice');

    require_once 'Notice.php';
    require_once 'mnw-datastore.php';
    require_once 'libomb/service_provider.php';

    $srv = new OMB_Service_Provider(get_own_profile(),
                                    mnw_Datastore::getInstance());
    if (count($srv->postNotice(new mnw_Notice($content))) > 0) {
        return __('Error sending notice.', 'mnw');
    }
    return __('Notice sent.', 'mnw');
}

function mnw_notices_delete($show, $action) {
    switch ($action) {
    case 'delete':
        if (!isset($_GET['notice'])) {
            return false;
        }
        check_admin_referer('mnw-delete-notice_' . $_GET['notice']);
        $notices = array($_GET['notice']);
        break;
    case 'delete-selected':
        if (!isset($_POST['checked'])) {
            return false;
        }
        check_admin_referer('mnw-bulk-notices');
        $notices = $_POST['checked'];
        break;
    }

    $notice_count = count($notices);
    if ($notice_count !== delete_notices($show, $notices)) {
        return _n('Could not delete notice.', 'Could not delete notices.',
                  $notice_count, 'mnw');
    }
    return _n('Notice successfully deleted.',
              'Notices successfully deleted.', $notice_count, 'mnw');
}

function out_caption($item, $caption, $show) {
    if ($item === $show) {
        echo "<em>$caption</em>";
    } else {
        echo "<a href='" . attribute_escape(mnw_set_param('admin.php?' .
             'page=mnw/admin/mnw-admin-notices.php', 'mnw_show', $item)) .
             "'>$caption</a>";
    }
}

function mnw_notices() {
    $show      = valid_input_set('mnw_show', array('received', 'replies',
                                                   'default' => 'sent'),
                                 $_REQUEST);
    $show_sent = $show === 'sent';
    $paged     = valid_input_fnc('paged', 'is_positive', 1, $_REQUEST);

    mnw_notices_parse_action($show);

    /* Get notices. */
    list($notices, $total) = get_notices($show, $paged - 1);

    mnw_start_admin_page();
?>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="hidden" name="mnw_show" value="<?php echo $show; ?>" />
      <?php wp_nonce_field('mnw-new_notice'); ?>
      <h3><?php _e('New notice', 'mnw'); ?></h3>
      <textarea id="mnw_notice" name="mnw_notice" cols="45" rows="3"
                style="font-size: 2em; line-height: normal;"><?php
        if (isset($_REQUEST['mnw_notice'])) echo $_REQUEST['mnw_notice'];
      ?></textarea>
      <br />
      <input type="submit" name="action" class="button-primary action"
             value="<?php _e('Send notice', 'mnw'); ?>" />
    </form>

    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
      <input type="hidden" name="mnw_show" value="<?php echo $show; ?>" />
      <?php wp_nonce_field('mnw-bulk-notices'); ?>
      <h3><?php out_caption('sent', __('Sent notices', 'mnw'), $show);
                echo ' / ';
                out_caption('received', __('Received notices', 'mnw'), $show);
                echo ' (';
                out_caption('replies', __('Only replies', 'mnw'), $show);
                echo ') ';
      ?></h3>
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

<table class="widefat" cellspacing="0" id="notices-table">
  <thead>
    <tr>
        <th scope="col" class="check-column"><input type="checkbox" /></th>
<?php if (!$show_sent) { ?>
        <th scope="col"><?php _e('Author', 'mnw'); ?></th>
<?php } ?>
        <th scope="col"><?php _e('Content', 'mnw'); ?></th>
        <th scope="col" class="action-links"><?php _e('Action'); ?></th>
    </tr>
  </thead>
  <tfoot>
    <tr>
        <th scope="col" class="check-column"><input type="checkbox" /></th>
<?php if (!$show_sent) { ?>
        <th scope="col"><?php _e('Author', 'mnw'); ?></th>
<?php } ?>
        <th scope="col"><?php _e('Content', 'mnw'); ?></th>
        <th scope="col" class="action-links"><?php _e('Action'); ?></th>
    </tr>
  </tfoot>
  <tbody class="notices">

<?php
    if ($notices == 0) {
?>
    <tr>
      <td colspan="<?php echo $show_sent ? 2 : 3;?>"><?php _e('No notices', 'mnw'); ?></td>
    </tr>
<?php
    } else {
      foreach ($notices as $notice) {
?>
      <tr>
        <th scope='row' class='check-column'><input type='checkbox' name='checked[]' value='<?php echo $notice['id']; ?>' /></th>
<?php if (!$show_sent) { ?>
        <td><?php echo $notice['author']; ?></th>
<?php } ?>
        <td><?php echo $notice['content']; ?></td>
        <td class='togl action-links'>
          <a href="<?php echo wp_nonce_url(
              $_SERVER['REQUEST_URI'] . '&amp;action=delete' . '&amp;mnw_show=' . $mnw_show . '&amp;notice=' . $notice['id'],
              'mnw-delete-notice_' . $notice['id']); ?>"
             title="<?php _e('Delete this notice', 'mnw'); ?>">
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

mnw_notices();
?>
