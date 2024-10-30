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

function mnw_handle_omb() {
    /* perform action mnw_omb_action. */
    if (!isset($_REQUEST[MNW_OMB_ACTION])) {
        return null;
    }

    switch ($_REQUEST[MNW_OMB_ACTION]) {
    case 'updateprofile':
        $srv = new OMB_Service_Provider(get_own_profile(), mnw_Datastore::getInstance());
        $profile = $srv->handleUpdateProfile();
        return new mnw_No_Themepage();

    case 'postnotice':
        $srv = new OMB_Service_Provider(get_own_profile(), mnw_Datastore::getInstance());
        $srv->handlePostNotice();
        return new mnw_No_Themepage();
    }
    return null;
}
?>
