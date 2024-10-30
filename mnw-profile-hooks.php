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


$mnw_profile_options = array('omb_full_name', 'omb_nickname', 'omb_license', 'omb_bio',
                     'omb_location', 'omb_avatar');

foreach($mnw_profile_options as $option_name) {
    add_action("update_option_{$option_name}", 'mnw_upd_settings');
}

function mnw_upd_settings() {
    require_once 'mnw-datastore.php';
    require_once 'mnw-lib.php';
    require_once 'libomb/service_provider.php';
    $srv = new OMB_Service_Provider(get_own_profile(),
                                    mnw_Datastore::getInstance());
    $srv->updateProfile();
}
?>
