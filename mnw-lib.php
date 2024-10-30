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

/* Stuff called common_ is copied from laconica/lib/util.php */

function get_remote_users($page) {
    global $wpdb;
    $users = $wpdb->get_results('SELECT SQL_CALC_FOUND_ROWS id, nickname, ' .
                                'url, token, resubtoken, license FROM ' .
                                MNW_SUBSCRIBER_TABLE . ' WHERE ' .
                                'token is not null or resubtoken is not null ' .
                                'LIMIT ' . floor($page * 15) . ', 15',
                                ARRAY_A);

    $total = $wpdb->get_var('SELECT FOUND_ROWS()');
    return array($users, $total);
}

function delete_remote_users($ids) {
    global $wpdb;
    $ids = array_map($wpdb->escape, $ids);
    return $wpdb->query('UPDATE ' . MNW_SUBSCRIBER_TABLE . ' SET ' .
                        'token = null, secret = null, resubtoken = null, ' .
                        'resubsecret = null WHERE id = "' .
                        implode('" OR id = "', $ids) . '"');
}

function count_remote_users($type)
{
    switch($type) {
    case 'subscribers':
        $where = 'token is not null';
        break;
    case 'subscribed':
        $where = 'resubtoken is not null';
        break;
    case 'both':
        $where = 'token is not null and resubtoken is not null';
        break;
    }

    global $wpdb;
    return $wpdb->get_var('SELECT COUNT(*) FROM ' . MNW_SUBSCRIBER_TABLE . ' ' .
                          'WHERE ' . $where);
}

function get_notices($type, $page)
{
    global $wpdb;

    if ($type === 'sent') {
        $query = '*, CONCAT("' . mnw_set_action('notice') .
                      '&mnw_notice_id=' . '", id) AS "noticeurl" FROM ' .
                      MNW_NOTICES_TABLE . ' AS notice';
    } else {
        $query = '*, nickname AS author, author.url AS "authorurl", ' .
                 'notice.url as "noticeurl"' .
                 ' FROM ' . MNW_FNOTICES_TABLE . ' AS notice, ' .
                 MNW_SUBSCRIBER_TABLE . ' AS author WHERE ' .
                 'notice.user_id = author.id' .
                 ($type === 'replies' ? ' AND to_us = 1' : '');
    }

    $notices = $wpdb->get_results("SELECT SQL_CALC_FOUND_ROWS $query " .
                                  'ORDER BY created DESC ' .
                                  'LIMIT ' . floor($page * 15) . ', 15',
                                  ARRAY_A);

    $total = $wpdb->get_var('SELECT FOUND_ROWS()');

    return array($notices, $total);
}

function get_notice($id) {
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare('SELECT url, content, created FROM ' .
                                         MNW_NOTICES_TABLE . ' WHERE id = %d',
                                         $id), ARRAY_A);
}

function delete_notices($type, $notices) {
    global $wpdb;
    $notices = array_map($wpdb->escape, $notices);
    return $wpdb->query('DELETE FROM ' .
          (($type === 'sent') ? MNW_NOTICES_TABLE : MNW_FNOTICES_TABLE) .
          ' WHERE id = "' . implode('" OR id = "', $notices) . '"');
}

function count_notices($type) {
    global $wpdb;
    $query = 'SELECT COUNT(*) FROM ' . MNW_FNOTICES_TABLE;
    if ($type == 'responses') {
        $query .= ' WHERE to_us = 1';
    }

    return $wpdb->get_var($query);
}

if (!function_exists('valid_input_set')) {
function valid_input_set($param, $valid_values, $array) {
    return (isset($array[$param]) && in_array($array[$param], $valid_values))
           ? $array[$param] : $valid_values['default'];
}
}

function valid_input_fnc($param, $validator, $default, $array) {
    return (isset($array[$param]) && call_user_func($validator, $array[$param]))
           ? $array[$param] : $default;
}

function is_positive($val) {
    return is_numeric($val) && $val > 0;
}

function mnw_is_to_us($content) {
  return preg_match('/(^T |@)' . get_option('omb_nickname') . '/', $content);
}

function get_own_profile() {
  static $profile;
  if (is_null($profile)) {
    require_once 'libomb/profile.php';
    $profile = new OMB_Profile(get_bloginfo('url'));
    $profile->setProfileURL(get_bloginfo('url'));
    $profile->setHomepage(get_bloginfo('url'));
    if($v = get_option('omb_nickname')) $profile->setNickname($v);
    if($v = get_option('omb_license')) $profile->setLicenseURL($v);
    if($v = get_option('omb_full_name')) $profile->setFullname($v);
    if($v = get_option('omb_bio')) $profile->setBio($v);
    if($v = get_option('omb_location')) $profile->setLocation($v);
    if($v = get_option('omb_avatar')) $profile->setAvatarURL($v);
  }
  return $profile;
}

function common_have_session() {
    return (0 != strcmp(session_id(), ''));
}

function common_ensure_session() {
    if (!common_have_session()) {
        @session_start();
    }
}

function common_good_rand($bytes)
{
    // XXX: use random.org...?
    if (file_exists('/dev/urandom')) {
        return common_urandom($bytes);
    } else { // FIXME: this is probably not good enough
        return common_mtrand($bytes);
    }
}

function common_urandom($bytes)
{
    $h = fopen('/dev/urandom', 'rb');
    // should not block
    $src = fread($h, $bytes);
    fclose($h);
    $enc = '';
    for ($i = 0; $i < $bytes; $i++) {
        $enc .= sprintf("%02x", (ord($src[$i])));
    }
    return $enc;
}

function common_mtrand($bytes)
{
    $enc = '';
    for ($i = 0; $i < $bytes; $i++) {
        $enc .= sprintf("%02x", mt_rand(0, 255));
    }
    return $enc;
}

function mnw_set_action($action) {
    $themepage = get_option('mnw_themepage_url');
    if (strrpos($themepage, '?') === false) {
        $themepage .= '?';
    } else {
        $themepage .= '&';
    }
    return $themepage . MNW_ACTION . '=' . $action;
}

function mnw_set_param($url, $param_name, $newval) {
    if (strpos($url, $param_name) !== false) {
        return preg_replace("/([?&]$param_name=)[^&]*/", '${1}' . $newval, $url);
    }
    return $url . (strpos($url, '?') === false ? '?' : '&') . $param_name . '=' . $newval;
}
?>
