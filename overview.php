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
 * Overview page for completion progress block.
 *
 * @package    block_occompletionprogress
 * @copyright  2024 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_occompletionprogress\completions;
use block_occompletionprogress\table\overview;

require_once(__DIR__ . '/../../config.php');
require_once("$CFG->libdir/tablelib.php");

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT); // Which page to show.
$perpage = optional_param('perpage', 20, PARAM_INT); // How many per page.
$group = optional_param('group', 0, PARAM_ALPHANUMEXT); // Group selected.
$role = optional_param('role', null, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$pageurl = new moodle_url('/blocks/occompletionprogress/overview.php', [
    'id' => $id,
    'courseid' => $courseid,
    'page' => $page,
    'perpage' => $perpage,
    'group' => $group,
    'role' => $role,
    'download' => $download,
]);
$PAGE->set_url($pageurl);

require_login($courseid);

$course = get_course($courseid);
$context = context_course::instance($courseid);
require_capability('block/occompletionprogress:overview', $context);

$title = get_string('overview', 'block_occompletionprogress');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');

$output = $PAGE->get_renderer('block_occompletionprogress');
$completions = new completions($course);
$completions->for_block();

// Prepare a group selector if there are groups in the course.
$groupids = [];
$groupoptions = [];
if (has_capability('moodle/site:accessallgroups', $context)) {
    $allgroups = groups_get_all_groups($course->id, 0);
    $allgroupings = groups_get_all_groupings($course->id);
    if ($allgroups) {
        $groupoptions[0] = get_string('allparticipants');
    }
} else {
    $allgroups = groups_get_all_groups($course->id, $USER->id);
    $allgroupings = [];
    $groupids = array_keys($allgroups);
}

foreach ($allgroups as $rec) {
    if ($group == $rec->id) {
        $groupids = [ $rec->id ]; // Selected filter.
    }
    $groupoptions[$rec->id] = format_string($rec->name);
}

foreach ($allgroupings as $rec) {
    if ($group === "g{$rec->id}") { // Selected grouping.
        $groupids = array_keys(groups_get_all_groups($course->id, 0, $rec->id));
    }
    $groupoptions["g{$rec->id}"] = format_string($rec->name);
}

if (!$groupids) {
    $group = 0;
    $pageurl->param('group', $group);
    $PAGE->set_url($pageurl);
}

// Prepare the roles menu.
$sql = "SELECT DISTINCT r.id, r.name, r.shortname, r.archetype, r.sortorder
          FROM {role} r, {role_assignments} ra
         WHERE ra.contextid = :contextid
           AND r.id = ra.roleid
        ORDER BY r.sortorder";
$params = ['contextid' => $context->id];
$roles = role_fix_names($DB->get_records_sql($sql, $params), $context);
$roleoptions = [0 => get_string('allparticipants')];

if ($role === null) {
    foreach ($roles as $rec) {
        if ($rec->archetype === 'student') {
            $role = $rec->id;
            $pageurl->param('role', $role);
            break;
        }
    }
    $PAGE->set_url($pageurl);
}
foreach ($roles as $rec) {
    $roleoptions[$rec->id] = $rec->localname;
}

echo $OUTPUT->header();

$table = new overview($completions, $groupids, $role);
$table->define_baseurl($pageurl);
$table->show_download_buttons_at([]);   // We'll output them ourselves.
$table->is_downloading($download, 'completion_progress-' . $course->shortname);
$table->setup();

if ($download) {
    $table->query_db($perpage);
    $table->start_output();
    $table->build_table();
    $table->finish_output();
    exit;
}

echo $output->container_start('progressoverviewmenus');
if ($groupoptions) {
    $basegroupurl = clone $pageurl;
    $basegroupurl->remove_params('group');
    echo $output->single_select(
        $basegroupurl,
        'group',
        $groupoptions,
        $group,
        ['' => 'choosedots'],
        null,
        ['label' => s(get_string('groupsgroupings', 'group'))]
    );
}
if ($roleoptions) {
    $baseroleurl = clone $pageurl;
    $baseroleurl->remove_params('role');
    echo $output->single_select(
        $baseroleurl,
        'role',
        $roleoptions,
        $role,
        ['' => 'choosedots'],
        null,
        ['label' => get_string('role')]
    );
}
echo $output->container_end();

// Form for messaging selected participants.
$formattributes = ['action' => $CFG->wwwroot . '/user/action_redir.php', 'method' => 'post', 'id' => 'participantsform'];
$formattributes['data-course-id'] = $course->id;
$formattributes['data-table-unique-id'] = 'block-completion_progress-overview-' . $course->id;
echo html_writer::start_tag('form', $formattributes);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'returnto', 'value' => s($pageurl->out(false))]);

// Imitate a 3.9 dynamic table enough to fool the core_user/participants JS code, until
// next time it changes again.
$tabledivattributes = [
        'data-region' => 'core_table/dynamic',
        'data-table-uniqueid' => $formattributes['data-table-unique-id'],
];
echo html_writer::start_div('', $tabledivattributes);

// Render the overview table.
$table->query_db($perpage);
$table->start_output();
$table->build_table();
$table->finish_output();

$PAGE->requires->js_call_amd('block_occompletionprogress/showinfo', 'init', []);

echo $OUTPUT->footer();
