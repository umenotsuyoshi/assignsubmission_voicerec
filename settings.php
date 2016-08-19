<?php
// This file is part of Moodle - http://moodle.org/
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
 * This file defines the admin settings for this plugin
 *
 * @package   assignsubmission_voicerec
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Note: This is on by default.
$settings->add(new admin_setting_configcheckbox('assignsubmission_voicerec/default',
		new lang_string('default', 'assignsubmission_voicerec'),
		new lang_string('default_help', 'assignsubmission_voicerec'), 0));

// 教員が選択可能な録音回数の上限。
$options = array();
for ($i = 1; $i <= 20; $i++) {
	$options[$i] = $i;
}
$settings->add($setting = new admin_setting_configselect('assignsubmission_voicerec/sitemaxfiles',
		get_string('sitemaxfiles', 'assignsubmission_voicerec'), 
		get_string('sitemaxfilesdefaultsetinfo', 'assignsubmission_voicerec'),
		20,
		$options));


// 録音回数のサイトデフォルト値
$settings->add(new admin_setting_configtext('assignsubmission_voicerec/defaultfilenum',
		get_string('defaultfilenum', 'assignsubmission_voicerec'),
		get_string('defaultfilenumsetinfo', 'assignsubmission_voicerec'), 1, PARAM_INT, 2));

// 録音時間のサイトでフォルト
$settings->add(new admin_setting_configtext('assignsubmission_voicerec/maxduration',
		get_string('maxduration', 'assignsubmission_voicerec'), 
		get_string('maxdurationdefaultsetinfo', 'assignsubmission_voicerec'), 600, PARAM_INT, 6));


