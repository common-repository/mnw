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

function mnw_handle_get_notice()
{
    if (!isset($_GET[MNW_NOTICE_ID])) {
        return null;
    }

    $notice = get_notice($_GET[MNW_NOTICE_ID]);

    if (is_null($notice)) {
        return new mnw_Themepage_NoSuchNotice();
    } elseif (!is_null($notice['url']) && get_option('mnw_forward_to_object')) {
        wp_redirect($notice['url'], 307);
        return new mnw_No_Themepage();
    }

    return new mnw_Themepage_Notice($notice);
}

class mnw_Themepage_Notice extends mnw_Themepage
{
    protected $notice;

    public function __construct($notice)
    {
        $this->notice = $notice;
    }

    public function render()
    {
        parent::render();
        echo '<h3>';
        printf(__('Notice from %s', 'mnw'), date(__('d F Y H:i:s', 'mnw'),
                  strtotime($this->notice['created'])));
        echo '</h3>';
        echo '<p style="font-size: 2em; margin-left: 0.5em;">';
        if (!is_null($this->notice['url'])) {
            echo "<a href='" . $this->notice['url'] . "'>" .
                 $this->notice['content'] . '</a>';
        } else {
            echo $this->notice['content'];
        }
        echo '</p>';
    }
}

class mnw_Themepage_NoSuchNotice extends mnw_Themepage
{
    public function __construct()
    {
        header('HTTP/1.1 404 Not Found');
    }

    public function render()
    {
        parent::render();
        echo '<h3>' . __('Notice', 'mnw') . '</h3>';
        echo '<p>' . __('Notice not found.', 'mnw') . '</p>';
    }
}
?>
