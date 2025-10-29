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

namespace block_occompletionprogress\table;

use block_occompletionprogress\completions;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Class overview
 * Renders a table with user completion progress data.
 *
 * @package      block_occompletionprogress
 * @copyright    2024 oncampus GmbH <support@oncampus.de>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \table_sql {
    /** @var completions Holds completion data. */
    private completions $completions;

    /** @var \plugin_renderer_base Output renderer. */
    private \plugin_renderer_base $output;

    /**
     * Constructor for the overview class.
     *
     * @param completions $completions Completion data object.
     * @param array $groups Group IDs for filtering.
     * @param ?int $roleid Role ID for filtering, or null.
     * @param bool $bulkoperations Whether bulk operations are enabled.
     */
    public function __construct(completions $completions, array $groups = [], ?int $roleid = null, bool $bulkoperations = false) {
        global $PAGE;

        $this->completions = $completions;
        $this->output = $PAGE->get_renderer('block_occompletionprogress');

        parent::__construct('block_occompletionprogress_overview');

        $tablecolumns = [];
        $tableheaders = [];

        if ($bulkoperations) {
            $checkbox = new \core\output\checkbox_toggleall('participants-table', true, [
                'id' => 'select-all-participants',
                'name' => 'select-all-participants',
                'label' => get_string('selectall'),
                'labelclasses' => 'sr-only',
                'checked' => false,
            ]);
            $tablecolumns[] = 'select';
            $tableheaders[] = $this->output->render($checkbox);
        }

        $tablecolumns[] = 'fullname';
        $tableheaders[] = get_string('fullname');

        if (get_config('block_occompletionprogress', 'showlastincourse') != 0) {
            $tablecolumns[] = 'timeaccess';
            $tableheaders[] = get_string('lastonline', 'block_occompletionprogress');
        }

        $tablecolumns[] = 'progressbar';
        $tableheaders[] = get_string('progressbar', 'block_occompletionprogress');
        $tablecolumns[] = 'progress';
        $tableheaders[] = get_string('progress', 'block_occompletionprogress');

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);
        $this->sortable(true, 'firstname');
        $this->no_sorting('progressbar');

        if ($bulkoperations) {
            $this->column_class('select', 'col-select');
            $this->no_sorting('select');
        }

        $this->set_attribute('class', 'overviewTable');
        $this->column_class('fullname', 'col-fullname');
        $this->column_class('timeaccess', 'col-timeaccess');
        $this->column_class('progressbar', 'col-progressbar');
        $this->column_class('progress', 'col-progress');

        if (class_exists('\core_user\fields')) {
            $picturefields = \core_user\fields::for_userpic()->get_sql('u', false, '', '', false)->selects;
        } else {
            // 3.10 and older.
            $picturefields = \user_picture::fields('u');
        }

        $enroljoin = get_enrolled_with_capabilities_join(
            $this->completions->context(),
            '',
            '',
            $groups,
            get_config('block_occompletionprogress', 'showinactive') == 0
        );

        $params = $enroljoin->params + ['courseid' => $this->completions->course()->id];
        if ($roleid) {
            $rolejoin = "INNER JOIN {role_assignments} ra ON ra.contextid = :contextid AND ra.userid = u.id";
            $rolewhere = "AND ra.roleid = :roleid";
            $params['contextid'] = $this->completions->context()->id;
            $params['roleid'] = $roleid;
        } else {
            $rolejoin = $rolewhere = '';
        }

        $this->set_sql(
            "DISTINCT $picturefields, l.timeaccess",
            "{user} u {$enroljoin->joins} {$rolejoin} LEFT JOIN {user_lastaccess} l ON l.userid = u.id AND l.courseid = :courseid",
            "{$enroljoin->wheres} {$rolewhere}",
            $params
        );

        $this->set_count_sql(
            "SELECT COUNT(DISTINCT u.id) FROM {$this->sql->from} WHERE {$this->sql->where}",
            $params
        );
    }

    /**
     * Sets up the table for display or downloading.
     */
    public function setup() {
        if ($this->is_downloading()) {
            unset($this->headers[$this->columns['select']], $this->columns['select']);
            unset($this->headers[$this->columns['progressbar']], $this->columns['progressbar']);
        }
        parent::setup();
    }

    /**
     * Queries the database to retrieve user data for the table.
     *
     * @param int $pagesize The number of records to retrieve per page.
     * @param bool $useinitialsbar Whether to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $sortcols = $this->get_sort_columns();
        if (isset($sortcols['progress'])) {
            // Kludge to sort by the runtime-computed percentage column.
            if ($useinitialsbar && !$this->is_downloading()) {
                $this->initialbars(true);
            }
            [$wsql, $wparams] = $this->get_sql_where();
            if ($wsql) {
                $this->sql->where .= ' AND ' . $wsql;
                $this->sql->params = array_merge($this->sql->params, $wparams);
            }
            $sql = "SELECT {$this->sql->fields}
                    FROM {$this->sql->from}
                    WHERE {$this->sql->where}";
            $rawdata = $DB->get_recordset_sql($sql, $this->sql->params);

            // Compute the percentage for each record and sort.
            $data = [];
            $percents = [];
            foreach ($rawdata as $key => $row) {
                $this->completions->set_user($row, true);
                $percents[$key] = $this->completions->completedpercentage() ?? -1;
                $data[$key] = $row;
            }
            $sortfunc = $sortcols['progress'] === SORT_ASC ? 'asort' : 'arsort';
            $sortfunc($percents);
            $rawdata->close();

            if (!$this->is_downloading()) {
                $pagestart = $this->currpage * $pagesize;
                $percents = array_slice($percents, $pagestart, $pagesize, true);
                $this->pagesize($pagesize, count($data));
            }

            $this->rawdata = [];
            foreach (array_keys($percents) as $key) {
                $this->rawdata[] = $data[$key];
            }
            return;
        }

        parent::query_db($pagesize, $useinitialsbar);
    }

    /**
     * Formats a row of data for display.
     *
     * @param object $row The data for the row.
     * @return array The formatted row.
     */
    public function format_row($row) {
        $this->completions->set_user($row, true);
        return parent::format_row($row);
    }

    /**
     * Renders the checkbox column.
     *
     * @param object $row The data for the row.
     * @return string The HTML for the checkbox.
     */
    public function col_select($row) {
        $checkbox = new \core\output\checkbox_toggleall('participants-table', false, [
            'classes' => 'usercheckbox',
            'id' => 'user' . $row->id,
            'name' => 'user' . $row->id,
            'checked' => false,
            'label' => get_string(
                'selectitem',
                'block_occompletionprogress',
                fullname($row, has_capability('moodle/site:viewfullnames', $this->completions->context()))
            ),
            'labelclasses' => 'accesshide',
        ]);
        return $this->output->render($checkbox);
    }

    /**
     * Renders the fullname column.
     *
     * @param object $row The data for the row.
     * @return string The HTML for the fullname.
     */
    public function col_fullname($row) {
        if (!$this->is_downloading()) {
            return $this->output->user_picture($row, [
                'courseid' => $this->completions->course()->id,
                'includefullname' => true,
            ]);
        } else {
            return parent::col_fullname($row);
        }
    }

    /**
     * Renders the timeaccess column.
     *
     * @param object $row The data for the row.
     * @return string The formatted access time.
     */
    public function col_timeaccess($row) {
        if ($row->timeaccess == 0) {
            return get_string('never');
        }
        return userdate($row->timeaccess, get_string('strftimedaydatetime', 'langconfig'));
    }

    /**
     * Renders the progressbar column.
     *
     * @param object $row The data for the row.
     * @return string The HTML for the progress bar.
     */
    public function col_progressbar($row) {
        return $this->output->render($this->completions);
    }

    /**
     * Renders the progress column.
     *
     * @param object $row The data for the row.
     * @return string The percentage of progress.
     */
    public function col_progress($row) {
        $pct = $this->completions->completedpercentage();
        if ($pct === null) {
            return get_string('indeterminate', 'block_occompletionprogress');
        }
        return get_string('percents', '', $pct);
    }
}
