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

function mnw_handle_xrds() {
    mnw_get_xrds();
    return new mnw_No_Themepage();
}

function mnw_get_xrds(&$xrds = null) {
    $srv = new OMB_Service_Provider(get_own_profile());
    if($xrds != null) {
        require_once 'diso_xrds_writer.php';
        $xrds_writer = new DiSo_XRDS_Writer($xrds);
    } else {
        /* Use libomb’s default XRDS Writer. */
        $xrds_writer = null;
    }
    require_once 'libomb/base_url_xrds_mapper.php';
    $mapper = new OMB_Base_URL_XRDS_Mapper(
                  mnw_set_action('oauth') . '&' . MNW_OAUTH_ACTION . '=',
                  mnw_set_action('omb') . '&' . MNW_OMB_ACTION . '=');
    $srv->writeXRDS($mapper, $xrds_writer);
}
?>
