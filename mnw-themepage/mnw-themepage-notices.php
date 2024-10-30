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

function mnw_handle_notices() {
    $type   = valid_input_set('type', array('default' => 'sent', 'received',
                                            'replies'), $_GET);
    $paged  = valid_input_fnc('paged', 'is_positive', 1, $_GET);
    $format = valid_input_set('format', array('default' => 'html', 'atom'),
                              $_GET);

    /* Get notices. */
    list($notices, $total) = get_notices($type, $paged - 1);

    /* Send notices to output engine. */
    require_once "mnw-themepage-notices-$format.php";
    $handler = "mnw_notices_$format";
    return $handler($type, $paged, $total, $notices);
}
?>
