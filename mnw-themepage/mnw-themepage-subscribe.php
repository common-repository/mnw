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

require_once 'libomb/service_consumer.php';
require_once 'libomb/profile.php';
require_once 'mnw-datastore.php';

function mnw_handle_subscribe() {
    $action = valid_input_set(MNW_SUBSCRIBE_ACTION, array('continue', 'finish',
                                                          'default' => ''),
                              $_GET);
    if ($action == '') {
        return null;
    }

    $err = null;
    try {
        switch ($action) {
        case 'continue':
            continue_subscription();
            return new mnw_No_Themepage();
        case 'finish':
            finish_subscription();
            return new mnw_No_Themepage();
        }
    } catch (Exception $e) {
        $err = $e->getMessage();
    }
    $profile_url = '';
    if (isset($_POST['profile_url'])) {
        $profile_url = attribute_escape($_POST['profile_url']);
    } elseif (isset($_GET['profile_url'])) {
        $profile_url = attribute_escape($_GET['profile_url']);
    } elseif (isset($_GET['omb_listener'])) {
        $profile_url = attribute_escape($_GET['omb_listener']);
    }
    return new mnw_Themepage_Subscribe($err, $profile_url);
}

function continue_subscription() {
    if (!isset($_POST['profile_url'])) {
        throw new Exception(__('No remote profile submitted', 'mnw'));
    }
    try {
        $service = new OMB_Service_Consumer($_POST['profile_url'],
                                            get_bloginfo('url'),
                                            mnw_Datastore::getInstance());
    } catch (Exception $e) {
        throw new Exception(__('Invalid profile URL', 'mnw'));
    }
    $service->requestToken();

    try {
        $redir = $service->requestAuthorization(get_own_profile(),
          mnw_set_action('subscribe') . '&' . MNW_SUBSCRIBE_ACTION . '=finish');
    } catch (Exception $e) {
        throw new Exception(__('Error requesting authorization', 'mnw'));
    }
    common_ensure_session();
    $_SESSION['omb_service'] = $service;
    wp_redirect($redir);
}

function finish_subscription() {
    common_ensure_session();
    $service = $_SESSION['omb_service'];
    if (!$service) {
        throw new Exception(__('No session found', 'mnw'));
    }
    try {
        $service->finishAuthorization();
    } catch (Exception $e) {
        throw new Exception(__('Error storing the subscription', 'mnw'));
    }

    unset($_SESSION['omb_service']);

    wp_redirect(get_option('mnw_after_subscribe'));
}
?>
