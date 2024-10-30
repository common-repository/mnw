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

require_once 'libomb/service_provider.php';
require_once 'mnw-datastore.php';
require_once 'mnw-lib.php';

function mnw_handle_oauth() {
    $action = valid_input_set(MNW_OAUTH_ACTION, array('requesttoken',
                                                      'userauthorization',
                                                      'userauth_continue',
                                                      'accesstoken',
                                                      'default' => ''),
                              $_REQUEST);
    if ($action === '') {
        return null;
    }

    try {
        switch ($action) {
        case 'requesttoken':
            $srv = new OMB_Service_Provider(get_own_profile(),
                                            mnw_Datastore::getInstance());
            $srv->writeRequestToken();
            return new mnw_No_Themepage();

        case 'userauthorization':
            global $user_level;
            get_currentuserinfo();
            if ($user_level < MNW_ACCESS_LEVEL) {
                throw new Exception(__('Not logged in or not admin.', 'mnw'));
            }
            $srv = new OMB_Service_Provider(get_own_profile(), mnw_Datastore::getInstance());
            try {
                $remote_user = $srv->handleUserAuth();
            } catch (Exception $e) {
                throw new Exception(sprintf(__('Error while verifying the authorize request. Original error: “%s”', 'mnw'), $e->getMessage()));
            }
            common_ensure_session();
            $_SESSION['omb_provider'] = $srv;
            return new mnw_Themepage_UserAuth(null, $remote_user);

        case 'userauth_continue':
            common_ensure_session();
            $srv = $_SESSION['omb_provider'];
            if (is_null($srv)) {
               throw new Exception(__('Error with your session.', 'mnw'));
            }
            if (!wp_verify_nonce($_POST['nonce'], 'mnw_userauth_nonce')) {
               throw new Exception(__('Error with your nonce.', 'mnw'));
            }
            $accepted = isset($_POST['accept']) && !isset($_POST['reject']);
            try {
              list($val, $token) = $srv->continueUserAuth($accepted);
            } catch (Exception $e) {
              throw new Exception(sprintf(__('Error while verifying the authorization. Original error: “%s”', 'mnw'), $e->getMessage()));
            }
            if ($val !== false) {
                wp_redirect($val, 303);
                return new mnw_No_Themepage();
            } else {
                return new mnw_Themepage_UserAuth_Continue($token);
            }
            break;

        case 'accesstoken':
            $srv = new OMB_Service_Provider(get_own_profile(), mnw_Datastore::getInstance());
            $srv->writeAccessToken();
            return new mnw_No_Themepage();
        }
    } catch (Exception $e) {
        return new mnw_Themepage_UserAuth($e->getMessage());
    }
    return null;
}

class mnw_Themepage_UserAuth extends mnw_Themepage {
    protected $err;
    protected $remote;

    public function __construct($err = null, $remote = null) {
        $this->err = $err;
        $this->remote = $remote;
    }

    public function render() {
        parent::render();
?>
        <h3><?php _e('Authorize subscription', 'mnw'); ?></h3>
        <p>
<?php
        if (!is_null($this->err)) {
            echo $this->err;
        } else {
            $uri = $this->remote->getIdentifierURI();
            $action = attribute_escape(mnw_set_action('oauth') . '&' .
                          MNW_OAUTH_ACTION . '=userauth_continue');
?>
            <form id="mnw_userauthorization" name="mnw_userauthorization"
                      method="post" action="<?php echo $action; ?>">
                <p><?php printf(__('Do you really want to subscribe %s?',
                                'mnw'), '<a href="' .
                                $this->remote->getProfileURL() . '">' .
                                $uri . '</a>');?></p>
                <input id="profile" type="hidden" value="<?php echo $uri; ?>"
                       name="profile"/>
                <input id="nonce" type="hidden" name="nonce"
                       value="<?php echo wp_create_nonce('mnw_userauth_nonce'); ?>"/>
                <input id="accept" class="submit" type="submit" title=""
                       value="<?php _e('Yes', 'mnw'); ?>" name="accept"/>
                <input id="reject" class="submit" type="submit" title=""
                       value="<?php _e('No', 'mnw'); ?>" name="reject"/>
            </form>
<?php
        }
?>
        </p>
<?php
    }
}

class mnw_Themepage_UserAuth_Continue extends mnw_Themepage {
    protected $token;

    public function __construct($token) {
        $this->token = $token;
    }

    public function render() {
        parent::render();
?>
        <h3><?php _e('Authorization granted', 'mnw'); ?></h3>
        <p>
<?php
            if ($this->token !== '') {
                printf(__('Confirm the subscribee‘s service that token %s is
authorized.', 'mnw'), $this->token);
            } else {
                _e('You rejected the subscription.', 'mnw');
            }
        echo '</p>';
    }
}
?>
