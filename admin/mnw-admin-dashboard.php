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

function mnw_dashboard()
{
    $title = get_bloginfo('title');

    echo '<p>';
    $subscribers = count_remote_users('subscribers');
    printf(_n('%s has <strong>%d subscriber</strong>.',
              '%s has <strong>%d subscribers</strong>.',
              $subscribers, 'mnw') . ' ', $title, $subscribers);

    $subscribed = count_remote_users('subscribed');
    printf(_n('It is <strong>subscribed to %d user</strong>.',
              'It is <strong>subscribed to %d users</strong>.',
              $subscribed, 'mnw') . ' ', $subscribed);
    echo '(<a href="admin.php?page=mnw/admin/mnw-admin-remote-users.php">' .
         __('User overview', 'mnw') . '</a>)';
    echo '</p>';

    echo '<p>';
    $resps = count_notices('responses');
    printf(_n('<strong>%d</strong> notice is listed as ' .
               '<strong>response</strong> to %s.',
              '<strong>%d</strong> notices are listed as ' .
               '<strong>responses</strong> to %s.',
              $resps, 'mnw') . ' ', $resps, $title);
    $total = count_notices('all');
    printf(_n('It has received a total of <strong>%d message</strong>.',
              'It has received a total of <strong>%d messages</strong>.',
              $total, 'mnw') . ' ', $total);
    echo '(<a href="admin.php?page=mnw/admin/mnw-admin-notices.php">' .
         __('Notices overview', 'mnw') . '</a>)';
    echo '</p>';
}

mnw_dashboard();
?>
