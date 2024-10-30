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

require_once 'OAuth.php';
require_once 'libomb/datastore.php';
require_once 'libomb/profile.php';

class mnw_Datastore extends OMB_Datastore {

  /* This class is a singleton. */

  private function __construct() { }

  public static function getInstance() {
    static $instance;
    if (is_null($instance)) {
      $instance = new self();
    }
    return $instance;
  }


    function lookup_consumer($consumer_key)
    {
        return new OAuthConsumer($consumer_key, '');
    }

    function lookup_token($consumer, $token_type, $token_key)
    {
        $ret = $this->_lookup_token($token_key);
        if ($ret && (($ret['type'] !== '3') ^ ($token_type === 'access'))) {
            return new OAuthToken($token_key, $ret['secret']);
        } else {
            return null;
        }
    }

    private function _lookup_token($key) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare('SELECT secret, type FROM ' .
                                             MNW_TOKENS_TABLE . ' WHERE ' .
                                             "token = '$key'"), ARRAY_A);
    }

    function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        global $wpdb;
        $nonce = $wpdb->escape($nonce);
        if ($wpdb->query("SELECT * FROM " . MNW_NONCES_TABLE . " WHERE nonce = '$nonce'") === 1) {
            return true;
        } else {
            $wpdb->query("INSERT INTO " . MNW_NONCES_TABLE . " VALUES ('$nonce')");
            return false;
        }
    }

    function new_request_token($consumer)
    {
        return $this->_new_token($consumer, '0');
    }

    function _new_token($consumer, $type)
    {
        global $wpdb;
        $token = common_good_rand(16);
        $secret = common_good_rand(16);
        if (!$wpdb->query('INSERT INTO ' . MNW_TOKENS_TABLE . ' ' . 
                          '(consumer, token, secret, type) VALUES ' . 
                          "('" . $wpdb->escape($consumer->key) . "', " . 
                          "'$token', '$secret', '$type')")) {
            return null;
        }
        return new OAuthToken($token, $secret);
    }

    function fetch_request_token($consumer)
    {
        throw new Exception();
    }

    function new_access_token($token, $consumer)
    {
        global $wpdb;
        $request = $this->_lookup_token($token->key);
        if (!$request || $request['type'] !== '1') {
          return null;
        }

        $o_token = $this->_new_token($consumer, '3');
        if (is_null($o_token) ||
            !$wpdb->update(MNW_TOKENS_TABLE, array('type' => 2),
                           array('consumer' => $consumer->key,
                                 'token' => $token->key), array('%d'))) {
            return null;
        }

        return $o_token;
    }

    function fetch_access_token($consumer)
    {
        throw new Exception();
    }

    public function revoke_token($token_key) {
        global $wpdb;
        if ($wpdb->query('DELETE FROM ' . MNW_TOKENS_TABLE .
                    " WHERE token = '$token_key' AND type = '0'") !== 1) {
          throw new Exception();
        }
    }

    public function authorize_token($token_key) {
        global $wpdb;
        if ($wpdb->query("UPDATE " . MNW_TOKENS_TABLE . " SET type = '1' " .
                     "WHERE token = '$token_key' AND type = '0'") !== 1) {
          throw new Exception();
        }
    }

  public function getProfile($identifierURI) {
    global $wpdb;
    $result = $wpdb->get_row($wpdb->prepare('SELECT uri AS r, ' .
                'url AS r_profile, nickname AS r_nickname, ' .
                'license AS r_license, fullname AS r_fullname, ' .
                'location AS r_location, bio AS r_bio, ' .
                'homepage as r_homepage, avatar as r_avatar ' .
                'FROM ' . MNW_SUBSCRIBER_TABLE . " WHERE uri = '%s'",
                $identifierURI), ARRAY_A);
    if (!$result) {
      return null;
    }
    return OMB_Profile::fromParameters($result, 'r');
  }

  public function saveProfile($profile) {
    global $wpdb;
    if ($profile->getIdentifierURI() == get_own_profile()->getIdentifierURI()) {
      return;
    }
    if ($wpdb->query($wpdb->prepare('SELECT id FROM ' . MNW_SUBSCRIBER_TABLE .
                    " WHERE uri = '%s'", $profile->getIdentifierURI())) === 1) {
      $query = "UPDATE " . MNW_SUBSCRIBER_TABLE . " SET url = '%s', " .
                  "fullname = '%s', location = '%s', bio = '%s', homepage = '%s', " .
                  "license = '%s', nickname = '%s', avatar = '%s' where uri = '%s'";
    } else {
      $query = "INSERT INTO " . MNW_SUBSCRIBER_TABLE . " (url, fullname, " .
                "location, bio, homepage, license, nickname, avatar, uri) " .
                "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')";
    }
    if ($wpdb->query($wpdb->prepare($query, $profile->getProfileURL(),
                   $profile->getFullname(), $profile->getLocation(),
                   $profile->getBio(), $profile->getHomepage(),
                   $profile->getLicenseURL(), $profile->getNickname(),
                   $profile->getAvatarURL(), $profile->getIdentifierURI())) === false) {
        throw new Exception();
    }
  }

  public function getSubscriptions($subscribed_user_uri) {
    global $wpdb;
    $myself = get_own_profile();
    if ($subscribed_user_uri !== $myself->getIdentifierURI()) {
      $query = $wpdb->prepare('SELECT uri, resubtoken, resubsecret FROM ' .
          MNW_SUBSCRIBER_TABLE . " WHERE uri = '%s'", $subscribed_user_uri);
    } else {
      $query = 'SELECT uri, token, secret FROM ' . MNW_SUBSCRIBER_TABLE . ' WHERE token IS NOT NULL';
    }
    return $wpdb->get_results($query, ARRAY_A);
  }

  public function deleteSubscription($subscriber_uri, $subscribed_user_uri) {
    $me = get_own_profile()->getIdentifierURI();
    if ($me == $subscribed_user_uri) {
      $query = 'UPDATE ' . MNW_SUBSCRIBER_TABLE . " SET token = null, secret = null WHERE uri = '%s'";
      $user = $subscriber_uri;
    } else {
      $query = 'UPDATE ' . MNW_SUBSCRIBER_TABLE . " SET resubtoken = null, resubsecret = null WHERE uri = '%s'";
      $user = $subscribed_user_uri;
    }
    global $wpdb;
    if($wpdb->query($wpdb->prepare($query, $user)) === false) {
      throw new Exception();
    }
  }

  public function saveSubscription($subscriber_uri, $subscribed_user_uri, $token) {
    $me = get_own_profile()->getIdentifierURI();
    if ($me == $subscribed_user_uri) {
      $query = 'UPDATE ' . MNW_SUBSCRIBER_TABLE . " SET token = '%s', secret = '%s' WHERE uri = '%s'";
      $user = $subscriber_uri;
    } else {
      $query = 'UPDATE ' . MNW_SUBSCRIBER_TABLE . " SET resubtoken = '%s', resubsecret = '%s' WHERE uri = '%s'";
      $user = $subscribed_user_uri;
    }
    global $wpdb;
    if($wpdb->query($wpdb->prepare($query, $token->key, $token->secret, $user)) === false) {
      throw new Exception();
    }
  }

  function saveNotice(&$notice) {
    global $wpdb;
    $me = get_own_profile()->getIdentifierURI();
    if ($me == $notice->getAuthor()->getIdentifierURI()) {
      if ($notice->getMappedURL() === false) {
        $query = $wpdb->prepare('INSERT INTO ' . MNW_NOTICES_TABLE .
                                " (content, created) VALUES ('%s', NOW())",
                                $notice->getContent());
      } else {
        $query = $wpdb->prepare('INSERT INTO ' . MNW_NOTICES_TABLE .
                    " (url, content, created) VALUES ('%s', '%s', NOW())",
                    $notice->getMappedURL(), $notice->getContent());
      }
      if($wpdb->query($query) !== 1) {
        throw new Exception();
      }
      $notice->setURI(mnw_set_action('notice') . '&mnw_notice_id=' . $wpdb->insert_id);
    } else {
      $query = 'INSERT INTO ' . MNW_FNOTICES_TABLE . ' (uri, url, user_id, content, created, to_us) ' .
              "SELECT '%s', '%s', user.id, '%s', NOW(), '%s' FROM " . MNW_SUBSCRIBER_TABLE . " AS user WHERE user.uri = '%s'";
      $query = $wpdb->prepare($query, $notice->getIdentifierURI(), $notice->getURL(), $notice->getContent(), mnw_is_to_us($notice->getContent()) ? '1' : '0', $notice->getAuthor()->getIdentifierURI());
      if($wpdb->query($query) !== 1) {
        throw new Exception();
      }
    }
  }
}
?>
