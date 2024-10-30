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

class mnw_Themepage {
    protected $title;

    protected function __construct() {
    }

    public function getTitle() {
        return $this->title;
    }

    public function shouldDisplay() {
        return true;
    }

    public function render() {
        echo '<p>';
        printf(__('%s supports the <a href="//openmicroblogging.org"
title="Official website of the OpenMicroBlogging standard">
OpenMicroBlogging</a> standard.', 'mnw') . ' ',
               get_bloginfo('title'));
        _e('It uses the free plugin <a href="//adrianlang.de/mnw"
title="Official website of mnw">mnw</a> for OMB functionality.', 'mnw');
?>
        </p>
        <ul>
            <li><a href="<?php echo attribute_escape(get_option('mnw_themepage_url')); ?>" title="<?php _e('Go to the subscribe form', 'mnw'); ?>"><?php _e('Subscribe', 'mnw'); ?></a></li>
            <li><a href="<?php echo attribute_escape(mnw_set_action('notices') . '&type=sent'); ?>" title="<?php _e('Display sent notices', 'mnw'); ?>"><?php _e('Sent notices', 'mnw'); ?></a></li>
            <li><a href="<?php echo attribute_escape(mnw_set_action('notices') . '&type=received'); ?>" title="<?php _e('Display received notices', 'mnw'); ?>"><?php _e('Received notices', 'mnw'); ?></a> (<a href="<?php echo attribute_escape(mnw_set_action('notices') . '&type=replies'); ?>" title="<?php _e('Display received notices replying', 'mnw'); ?>"><?php _e('Only replies', 'mnw'); ?></a>)</li>
        </ul>
<?php
    }

    /*
     * Parse requests to the main microblog page
     */
    public static function getHandler() {

        /* Assure that we have a valid themepage setting. */
        if (get_option('mnw_themepage_url') == '') {
            /* Since this method is only called from the themepage, we can
               just copy the current url if something‘s broken. */
            global $wp_query;
            update_option('mnw_themepage_url', $wp_query->post->guid);
        }

        if (!isset($_REQUEST[MNW_ACTION])) {
            /* No action at all – display the standard page. */
            return new mnw_Themepage_Subscribe();
        }

        if ($_REQUEST[MNW_ACTION] == 'get_notice') {
            /* get_notice is deprecated and should be replaced by notice. */
            wp_redirect(add_query_arg(MNW_ACTION, 'notice'));
            return new mnw_No_Themepage();
        }

        if (!in_array($_REQUEST[MNW_ACTION], array('subscribe', 'notice',
                                                   'xrds', 'oauth', 'omb',
                                                   'notices'))) {
            /* Save a poor user who entered a wrong action. */
            wp_redirect(get_option('mnw_themepage_url'));
            return new mnw_No_Themepage();
        }
        /* Load file and call method for this action request. */
        $action = $_REQUEST[MNW_ACTION];
        require_once "mnw-themepage-$action.php";
        $handler = "mnw_handle_$action";
        $handler = $handler();
        if (is_null($handler)) {
            $handler = new mnw_Themepage_Subscribe();
        }
        return $handler;
    }
}

class mnw_No_Themepage extends mnw_Themepage {
    public function __construct() {
    }

    public function shouldDisplay() {
        return false;
    }
}

class mnw_Themepage_Subscribe extends mnw_Themepage {
    protected $error;
    protected $profile_url;

    public function __construct($err = null, $profile = '') {
        $this->error = $err;
        $this->profile_url = $profile;
    }

    public function render() {
        parent::render();
        /* Gather data for subscribe form. */
        global $wp_query;
        $action = attribute_escape(mnw_set_action('subscribe') . '&' .
                                        MNW_SUBSCRIBE_ACTION . '=continue');

        /* Display the form. */
        echo '<h3>' . __('Subscribe', 'mnw') . '</h3>';
        echo '<p>';
        printf(__('If you have an user account at another OMB service
like <a href="//identi.ca" title="identi.ca, the largest
open microblogging service">identi.ca</a>, you can
easily subscribe to %s.', 'mnw'), get_bloginfo('name'));
        echo '</p>';
        echo '<p>';
        _e('To subscribe, just enter the URL of your profile at another OMB
service.', 'mnw');
        echo ' ';
        _e('You will be asked to log in there if you are not yet.', 'mnw');
        echo ' ';
        printf(__('There will be a confirmation prompt showing details of
%s.', 'mnw'), get_bloginfo('name'));
        echo '</p>';
        if (!is_null($this->error)) {
            echo '<p>';
            printf(__('Error: %s', 'mnw'), $this->error);
            echo '</p>';
        }
?>
        <form id='omb-subscribe' method='post' action='<?php echo $action; ?>'>
             <label for="profile_url"><?php _e('OMB Profile URL', 'mnw'); ?>
             </label>
             <input name="profile_url" type="text" class="input_text"
                    id="profile_url" value='<?php echo $this->profile_url; ?>'/>
             <input type="submit" id="submit" name="submit" class="submit"
                    value="<?php _e('Subscribe', 'mnw'); ?>"/>
        </form>
<?php
    }
}
?>
