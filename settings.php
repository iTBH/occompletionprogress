<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     block_occompletionprogress
 * @category    admin
 * @copyright   2024 oncampus GmbH <support@oncampus.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'block_occompletionprogress_settings',
        new lang_string('pluginname', 'block_occompletionprogress')
    );

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        $name = 'block_occompletionprogress/completedcolor';
        $title = get_string('completedcolor', 'block_occompletionprogress', null, true);
        $description = get_string('completedcolor_desc', 'block_occompletionprogress', null, true);
        $setting = new admin_setting_configcolourpicker(
            $name,
            $title,
            $description,
            '#43CB65'
        );
        $settings->add($setting);

        $name = 'block_occompletionprogress/uncompletedcolor';
        $title = get_string('uncompletedcolor', 'block_occompletionprogress', null, true);
        $description = get_string('uncompletedcolor_desc', 'block_occompletionprogress', null, true);
        $setting = new admin_setting_configcolourpicker(
            $name,
            $title,
            $description,
            '#F1FCF3'
        );
        $settings->add($setting);

        $name = 'block_occompletionprogress/notrackingcolor';
        $title = get_string('notrackingcolor', 'block_occompletionprogress', null, true);
        $description = get_string('notrackingcolor_desc', 'block_occompletionprogress', null, true);
        $setting = new admin_setting_configcolourpicker(
            $name,
            $title,
            $description,
            '#D4D4D4'
        );
        $settings->add($setting);

        $name = 'block_occompletionprogress/showinactive';
        $title = get_string('showinactive', 'block_occompletionprogress');
        $description = '';
        $setting = new admin_setting_configcheckbox(
            $name,
            $title,
            $description,
            0, // Moodle usually expects 0 or 1 here.
        );
        $settings->add($setting);

        $name = 'block_occompletionprogress/showlastincourse';
        $title = get_string('showlastincourse', 'block_occompletionprogress');
        $description = '';
        $setting = new admin_setting_configcheckbox(
            $name,
            $title,
            $description,
            0, // Moodle usually expects 0 or 1 here.
        );
        $settings->add($setting);
    }
}
