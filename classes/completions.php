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

namespace block_occompletionprogress;
use completion_info;
use core\context\course;
use core_completion\external\completion_info_exporter;
use moodle_url;
use stdClass;

/**
 * Class completions
 * Manages user completion data for activities within a Moodle course.
 *
 * @package    block_occompletionprogress
 * @copyright  2024 oncampus GmbH <support@oncampus.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completions implements \renderable {
    /** @var \context Course context. */
    protected \context $context;

    /** @var stdClass Course object. */
    protected stdClass $course;

    /** @var stdClass User object. */
    protected stdClass $user;

    /** @var completion_info Completion information for the course. */
    protected completion_info $completioninfo;

    /** @var array List of activities in the course. */
    protected array $activities;

    /** @var array List of sections in the course. */
    protected array $sections;

    /** @var int Number of completed activities. */
    protected int $completed;

    /** @var int Total number of activities. */
    protected int $count;

    /** @var int Percentage of completed activities. */
    protected int $completionpercentage;

    /**
     * Constructor for the completions class.
     *
     * @param mixed $courseorid Course object or ID.
     */
    public function __construct($courseorid) {
        global $CFG, $USER;

        require_once("$CFG->libdir/completionlib.php");

        if (is_object($courseorid)) {
            $this->course = $courseorid;
        } else {
            $this->course = get_course($courseorid);
        }
        $this->context = course::instance($this->course->id);
        $this->completioninfo = new completion_info($this->course);

        $this->set_user($USER);
    }

    /**
     * Sets the user for tracking completion data.
     *
     * @param mixed $userorid User object or ID.
     * @param bool $forceload Whether to force section loading.
     */
    public function set_user($userorid, bool $forceload = false) {
        global $DB;

        if (is_object($userorid)) {
            $this->user = $userorid;
        } else {
            $this->user = $DB->get_record('user', ['id' => $userorid]);
        }

        if ($forceload) {
            $this->load_sections();
        }
    }

    /**
     * Loads completion data for overview display.
     */
    public function for_overview() {
        $this->load_sections();
    }

    /**
     * Loads completion data for block display.
     */
    public function for_block() {
        $this->load_sections();
    }

    /**
     * Returns sections of the course.
     *
     * @param bool $resetkeys Whether to reset array keys.
     * @return array List of sections.
     */
    public function sections($resetkeys = true) {
        if ($resetkeys) {
            return array_values($this->sections);
        } else {
            return $this->sections;
        }
    }

    /**
     * Returns the completion percentage.
     *
     * @return int Completion percentage.
     */
    public function completedpercentage() {
        return $this->completed;
    }

    /**
     * Returns the user object.
     *
     * @return stdClass User object.
     */
    public function user() {
        return $this->user;
    }

    /**
     * Returns the course context.
     *
     * @return context Course context.
     */
    public function context() {
        return $this->context;
    }

    /**
     * Returns the course object.
     *
     * @return stdClass Course object.
     */
    public function course() {
        return $this->course;
    }

    /**
     * Loads sections data for the course.
     */
    protected function load_sections() {
        $this->count = 0;
        $this->completed = 0;

        $modinfo = get_fast_modinfo($this->course);
        $this->sections = [];
        $i = 0;
        $count = count($modinfo->sections);
        foreach ($modinfo->sections as $section => $activities) {
            $sectioninfo = $modinfo->get_section_info($section);
            if (!$sectioninfo->uservisible) {
                continue; // Skip sections that are not visible to the user.
            }

            $sectionobj = new stdClass();
            $sectionobj->num = $section;
            $sectionobj->count = 0;
            $sectionobj->finished = 0;
            $sectionobj->percentage = -1;
            $sectionobj->activities = [];
            $sectionobj->name = get_section_name($this->course->id, $section);
            $sectionobj->first = $i == 0;
            $sectionobj->last = $i == ($count - 1);
            $url = new moodle_url('/course/view.php', ['id' => $this->course->id, 'section' => $section]);
            $sectionobj->url = $url->out(false);
            $this->sections[$section] = $sectionobj;

            $i++;
        }

        $this->load_activities();

        foreach ($this->sections as &$section) {
            if ($section->count == 0) {
                continue;
            }
            $section->percentage = $section->finished / $section->count;
            $section->percentage = (int) round(100 * $section->percentage);
            $section->completed = $section->finished == $section->count;
            $section->hasactivities = $section->count > 0;
        }

        if ($this->count > 0) {
            $this->completed = (int) round(100 * ($this->completed / $this->count));
        } else {
            $this->completed = -1;
        }
    }

    /**
     * Loads activities for each section.
     */
    protected function load_activities() {
        global $PAGE;

        $this->activities = [];
        foreach ($this->completioninfo->get_activities() as $activity) {
            // Skip activities that are not visible or set to "stealth mode".
            if (!$activity->uservisible || !$activity->visibleoncoursepage) {
                continue;
            }
            $exporter = new completion_info_exporter(
                $this->course,
                $activity,
                $this->user->id,
            );
            $renderer = $PAGE->get_renderer('core');
            $data = $exporter->export($renderer);

            $activitydata = [
                'type' => $activity->module,
                'modname' => $activity->modname,
                'modfullname' => $activity->modfullname,
                'id' => $activity->id,
                'instance' => $activity->instance,
                'name' => $activity->get_name(),
                'expected' => $activity->completionexpected,
                'url' => $activity->url instanceof moodle_url ? $activity->url->out() : '',
                'onclick' => $activity->onclick,
                'context' => $activity->context,
                'icon' => $activity->get_icon_url(),
                'available' => $activity->available,
                'state' => $data->state,
                'timecompleted' => $data->timecompleted,
            ];

            $this->activities[$activity->id] = $activitydata;

            if (isset($this->sections[$activity->sectionnum])) {
                $sectionobj = &$this->sections[$activity->sectionnum];
                $sectionobj->activities[] = $activitydata;
                $this->count++;
                $sectionobj->count++;

                if ($data->state == COMPLETION_COMPLETE || $data->state == COMPLETION_COMPLETE_PASS) {
                    $this->completed++;
                    $sectionobj->finished++;
                }
            }
        }
    }
}
