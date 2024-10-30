<?php

require_once 'libomb/xrds_writer.php';

/**
 * Write OMB-specific XRDS using DiSo’s Simple-XRDS plugin.
 *
 * This class writes the XRDS file announcing the OMB server. It uses DiSo’s
 * Simple-XRDS plugin for wordpress.
 * An instance of DiSo_XRDS_Writer should be passed to
 * OMB_Service_Provider->writeXRDS.
 *
 * PHP version 5
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
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
 *
 * @author    Stephen Paul Weber <singpolyma@singpolyma.net>,
              Adrian Lang <mail@adrianlang.de>
 * @copyright 2009 Stephen Paul Weber, Adrian Lang
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL 3.0
 **/

class DiSo_XRDS_Writer implements OMB_XRDS_Writer {

  protected $xrds;

  public function __construct(&$xrds) {
    $this->xrds = $xrds;
  }

  public function writeXRDS($user, $mapper) {
     if($oauth_base_url) {
       $supports = array(OAUTH_AUTH_HEADER,OAUTH_POST_BODY,OAUTH_HMAC_SHA1);
       $oauth = xrds_add_xrd($this->xrds, 'oauth', 'xri://$xrds*simple');
       $oauth->service[] = new XRDS_Service(array(-1=>OAUTH_ENDPOINT_REQUEST)+$supports,
                                            null, new XRDS_URI($mapper->getURL(OAUTH_ENDPOINT_REQUEST)),
                                            new XRDS_LocalID($user->getIdentifierURI()));
       $oauth->service[] = new XRDS_Service(array(-1=>OAUTH_ENDPOINT_AUTHORIZE)+$supports,
                                            null, new XRDS_URI($mapper->getURL(OAUTH_ENDPOINT_AUTHORIZE)));
       $oauth->service[] = new XRDS_Service(array(-1=>OAUTH_ENDPOINT_ACCESS)+$supports,
                                            null, new XRDS_URI($mapper->getURL(OAUTH_ENDPOINT_ACCESS)));
       $oauth->service[] = new XRDS_Service(array(-1=>OAUTH_ENDPOINT_RESOURCE)+$supports);
       xrds_add_simple_service($xrds, 'oAuth', OAUTH_DISCOVERY, '#oauth');
     }
     $omb = xrds_add_xrd($this->xrds, 'omb', 'xri://$xrds*simple');
     $omb->service[] = new XRDS_Service(OMB_ENDPOINT_POSTNOTICE, null, new XRDS_URI($mapper->getURL(OMB_ENDPOINT_POSTNOTICE)));
     $omb->service[] = new XRDS_Service(OMB_ENDPOINT_UPDATEPROFILE, null, new XRDS_URI($mapper->getURL(OMB_ENDPOINT_UPDATEPROFILE)));
     xrds_add_simple_service($this->xrds, 'OMB', OMB_VERSION, '#omb');
  }
}
?>
