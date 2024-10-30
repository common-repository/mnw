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
require_once 'libomb/service_consumer.php';
require_once 'mnw-datastore.php';

function mnw_admin_test() {
    assert_options(ASSERT_BAIL, 1);

    common_ensure_session();
    mnw_start_admin_page();

    foreach (array('mnw_DiscoverXRDS',
                   'mnw_Subscribe') as $testclass) {
        $test = new $testclass();
        $test->execute();
    }

    mnw_finish_admin_page();
}

class mnw_Test
{
    function prepare()
    {
        $_SESSION['mnwtest-tokens'] = array();
        $_SESSION['mnwtest-profiles'] = array();
        $_SESSION['mnwtest-notices'] = array();
        $_SESSION['mnwtest-nonces'] = array();
        $_SESSION['mnwtest-subscriptions'] = array();
    }

    function execute()
    {
        $this->prepare();
        echo 'Starting test »' . $this->title . '« …';
        $this->test();
        echo ' done.<br />';
        $this->cleanup();
    }

    public function cleanup()
    {
        unset($_SESSION['mnwtest-tokens']);
        unset($_SESSION['mnwtest-profiles']);
        unset($_SESSION['mnwtest-notices']);
        unset($_SESSION['mnwtest-nonces']);
        unset($_SESSION['mnwtest-subscriptions']);
    }
}

class mnw_DiscoverXRDS extends mnw_Test {
    public $title = 'Discover own XRDS file';

    public function test()
    {
        $service = new OMB_Service_Consumer(get_own_uri(), null, null);
        foreach (array(OAUTH_DISCOVERY => OMB_Helper::$OAUTH_SERVICES,
                       OMB_VERSION     => OMB_Helper::$OMB_SERVICES)
                  as    $service_root   => $targetservices) {
            OMB_Helper::validateURL($service->getServiceURI($targetservice));
            assert($service->getRemoteUserURI() === get_own_uri());
        }
    }
}

class mnw_Subscribe extends mnw_Test {
    public $title = 'Subscribe';
    protected $token;

    public function test()
    {
        $service = new OMB_Service_Consumer(get_own_uri(),
                                            $_SERVER['REQUEST_URI'],
                                            new mnw_Test_Datastore());
        $this->token = $service->requestToken();
#        $redir = $service->requestAuthorization(new OMB_Profile($_SERVER['REQUEST_URI']), add_query_arg('mnwtest_action', 'finish'));
#        common_ensure_session();
#        $_SESSION['mnwtest_omb_service'] = $service;
#        wp_redirect($redir);
    }

    public function cleanup()
    {
        parent::cleanup();
        mnw_Datastore::getInstance()->revoke_token($this->token->key);
    }
}

class mnw_Test_Datastore extends OMB_Datastore
{

    /*********
     * OAUTH *
     *********/

    function lookup_consumer($consumer_key)
    {
        return new OAuthConsumer($consumer_key, '');
    }

    function lookup_token($consumer, $token_type, $token_key)
    {
        $ret = $_SESSION['mnwtest-tokens'][$token_key];
        if ($ret && (($ret['type'] !== '3') ^ ($token_type === 'access'))) {
            return new OAuthToken($token_key, $ret['secret']);
        } else {
            return null;
        }
    }

    function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        if (isset($_SESSION['mnwtest-nonces'][$nonce])) {
            return true;
        } else {
            $_SESSION['mnwtest-nonces'][$nonce] = 1;
            return false;
        }
    }

    function new_request_token($consumer)
    {
        return $this->_new_token($consumer, '0');
    }

    function _new_token($consumer, $type)
    {
        $token  = common_good_rand(16);
        $secret = common_good_rand(16);
        $_SESSION['mnwtest-tokens'][$token] = 
                           array('consumer' => $consumer->key,
                                 'token'    => $token,
                                 'secret'   => $secret,
                                 'type'     => $type);
        return new OAuthToken($token, $secret);
    }

    function fetch_request_token($consumer)
    {
        throw new Exception();
    }

    function new_access_token($token, $consumer)
    {
        $request = $this->_lookup_token($token->key);
        if (!$request || $request['type'] !== '1') {
            return null;
        }

        $o_token = $this->_new_token($consumer, '3');
        $_SESSION['mnwtest-tokens'][$token->key]['type'] = 2;
        return $o_token;
    }

    function fetch_access_token($consumer)
    {
        throw new Exception();
    }

    /**
     * Revoke specified OAuth token
     *
     * Revokes the authorization token specified by $token_key.
     * Throws exceptions in case of error.
     *
     * @param string $token_key The key of the token to be revoked
     *
     * @access public
     */
    public function revoke_token($token_key)
    {
        $tokens = $_SESSION['mnwtest-tokens'];
        unset($tokens[$token_key]);
    }

    /**
     * Authorize specified OAuth token
     *
     * Authorizes the authorization token specified by $token_key.
     * Throws exceptions in case of error.
     *
     * @param string $token_key The key of the token to be authorized
     *
     * @access public
     */
    public function authorize_token($token_key)
    {
        $tokens = $_SESSION['mnwtest-tokens'];
        $tokens[$token_key]['type'] = 1;
    }

    /*********
     *  OMB  *
     *********/

    /**
     * Get profile by identifying URI
     *
     * Returns an OMB_Profile object representing the OMB profile identified by
     * $identifier_uri.
     * Returns null if there is no such OMB profile.
     * Throws exceptions in case of other error.
     *
     * @param string $identifier_uri The OMB identifier URI specifying the
     *                               requested profile
     *
     * @access public
     *
     * @return OMB_Profile The corresponding profile
     */
    public function getProfile($identifier_uri)
    {
        $profiles = $_SESSION['mnwtest-profiles'];
        return $profiles[$identifier_uri];
    }

    /**
     * Save passed profile
     *
     * Stores the OMB profile $profile. Overwrites an existing entry.
     * Throws exceptions in case of error.
     *
     * @param OMB_Profile $profile The OMB profile which should be saved
     *
     * @access public
     */
    public function saveProfile($profile)
    {
        $_SESSION['mnwtest-profiles'][$profile->getIdentifierURI()] = $profile;
    }

    /**
     * Save passed notice
     *
     * Stores the OMB notice $notice. The datastore may change the passed
     * notice. This might by necessary for URIs depending on a database key.
     * Note that it is the user’s duty to present a mechanism for his
     * OMB_Datastore to appropriately change his OMB_Notice.
     * Throws exceptions in case of error.
     *
     * @param OMB_Notice &$notice The OMB notice which should be saved
     *
     * @access public
     */
    public function saveNotice(&$notice)
    {
        $_SESSION['mnwtest-notices'][] = $notice;
    }

    /**
     * Get subscriptions of a given profile
     *
     * Returns an array containing subscription informations for the specified
     * profile. Every array entry should in turn be an array with keys
     *   'uri´: The identifier URI of the subscriber
     *   'token´: The subscribe token
     *   'secret´: The secret token
     * Throws exceptions in case of error.
     *
     * @param string $subscribed_user_uri The OMB identifier URI specifying the
     *                                    subscribed profile
     *
     * @access public
     *
     * @return mixed An array containing the subscriptions or 0 if no
     *               subscription has been found.
     */
    public function getSubscriptions($subscribed_user_uri)
    {
        function _filt($val) {
            global $subscribed_user_uri;
            return $val['subscribed_user'] == $subscribed_user_uri;
        }
        function _map($val) {
            $val['uri'] = $val['subscriber'];
            unset($val['subscriber']);
            unset($val['subscribed_user']);
            return $val;
        }
        $subscriptions = $_SESSION['mnwtest-subscriptions'];
        $filtered = array_filter($subscriptions, '_filt');
        return array_map($filtered, '_map');
    }

    /**
     * Delete a subscription
     *
     * Deletes the subscription from $subscriber_uri to $subscribed_user_uri.
     * Throws exceptions in case of error.
     *
     * @param string $subscriber_uri      The OMB identifier URI specifying the
     *                                    subscribing profile
     *
     * @param string $subscribed_user_uri The OMB identifier URI specifying the
     *                                    subscribed profile
     *
     * @access public
     */
    public function deleteSubscription($subscriber_uri, $subscribed_user_uri)
    {
        $subscriptions = $_SESSION['mnwtest-subscriptions'];
        foreach($subscriptions as $key=>$val) {
            if ($val['subscribed_user'] == $subscribed_user_uri &&
                $val['subscriber'] == $subscriber_uri) {
                unset($_SESSION['mnwtest-subscriptions'][$key]);
            }
        }
    }

    /**
     * Save a subscription
     *
     * Saves the subscription from $subscriber_uri to $subscribed_user_uri.
     * Throws exceptions in case of error.
     *
     * @param string     $subscriber_uri      The OMB identifier URI specifying
     *                                            the subscribing profile
     *
     * @param string     $subscribed_user_uri The OMB identifier URI specifying
     *                                            the subscribed profile
     * @param OAuthToken $token               The access token
     *
     * @access public
     */
    public function saveSubscription($subscriber_uri, $subscribed_user_uri,
                                     $token)
    {
        $_SESSION['mnwtest-subscriptions'][] = array(
                    'subscriber' => $subscriber_uri,
                    'subscribed_user' => $subscribed_user_uri,
                    'token' => $token->key,
                    'secret' => $token->secret);
    }
}
mnw_admin_test();

?>
