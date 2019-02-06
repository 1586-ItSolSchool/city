<?php
// This file is part of the Tutorial Booking activity.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the Moodle mobile plugins provided.
 *
 * @package    mod_city
 * @copyright  2019 Citrus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = array(
    'mod_city' => array(
        'handlers' => array(
            'city' => array(
                'displaydata' => array(
                    'icon' => $CFG->wwwroot . '/mod/city/pix/icon.gif',
                    'class' => '',
                ),
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'city',
                'offlinefunctions' => array(),
            ),
        ),
        'lang' => array(
            ['freespaces' , 'city'],
            ['lockedprompt', 'city'],
            ['oversubscribedby', 'city'],
            ['removefromslot' , 'city'],
            ['signupforslot' , 'city'],
            ['totalspaces', 'city'],
            ['usedspaces' , 'city'],
            ['yousignedup' , 'city'],
        ),
    ),
);