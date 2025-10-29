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
 * Renderer for occompletionprogress block.
 *
 * @package      block_occompletionprogress
 * @copyright    2024 oncampus GmbH <support@oncampus.de>
 * @license      http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_occompletionprogress\output;

use block_occompletionprogress\completions;

/**
 * Class renderer
 * Renders the completion data for display within the block.
 *
 * @package    block_occompletionprogress
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Renders completion data.
     *
     * @param completions $completions The completions object containing completion data.
     * @return string Rendered HTML for the completion progress.
     */
    public function render_completions(completions $completions) {
        $data = [];
        $data['percentage'] = $completions->completedpercentage();
        $data['sections'] = $completions->sections();
        $data['user'] = $completions->user();
        $data['cells'] = [];

        $data['completedcolor'] = get_config('block_occompletionprogress', 'completedcolor');
        $data['uncompletedcolor'] = get_config('block_occompletionprogress', 'uncompletedcolor');
        $data['notrackingcolor'] = get_config('block_occompletionprogress', 'notrackingcolor');

        foreach ($data['sections'] as &$section) {
            $stateclass = '';
            $statestyle = '';
            $barcolor = '';
            if ($section->count > 0) {
                $section->showpercentage = true;
                if ($section->completed) {
                    $statestyle = 'background-color: ' . $data['completedcolor'];
                    $barcolor = 'background-color: ' . $data['completedcolor'];
                } else {
                    $statestyle = 'background-color: ' . $data['uncompletedcolor'];
                    $barcolor = 'background-color: ' . $data['uncompletedcolor'];
                }
            } else {
                $statestyle = 'background-color: ' . $data['notrackingcolor'];
                $barcolor = 'background-color: ' . $data['notrackingcolor'];
            }

            $borderclasses = '';
            if ($section->first) {
                $borderclasses = 'rounded-left';
            } else if ($section->last) {
                $borderclasses = 'rounded-right';
            }

            $section->celloptions = [
                'classes' => "$stateclass ; $borderclasses ;",
                'style' => "$statestyle",
            ];

            $section->barcolor = $barcolor;

            $i = 0;
            $count = count($section->activities);
            foreach ($section->activities as &$activity) {
                if (strlen($activity['name']) > 32) {
                    $activity['displayname'] = substr($activity['name'], 0, 29) . '...';
                    $activity['tooltip'] = true;
                } else {
                    $activity['displayname'] = $activity['name'];
                }

                if ($activity['state'] == COMPLETION_COMPLETE || $activity['state'] == COMPLETION_COMPLETE_PASS) {
                    $icon = 'tick';
                    $activity['completetext'] = get_string('completed', 'block_occompletionprogress');
                } else {
                    $icon = 'circle';
                    $activity['completetext'] = get_string('notcompleted', 'block_occompletionprogress');
                }
                $iconattributes = [
                    'class' => '',
                ];
                $activity['completeicon'] = $this->pix_icon(
                    $icon,
                    $activity['completetext'],
                    'block_occompletionprogress',
                    $iconattributes
                );

                $activity['classes'] = '';
                if ($i == 0) {
                    $activity['first'] = true;
                    $activity['classes'] .= 'rounded-top ';
                }
                if ($i == ($count - 1)) {
                    $activity['last'] = true;
                    $activity['classes'] .= 'rounded-bottom ';
                }

                $i++;
            }
        }

        return $this->output->render_from_template('block_occompletionprogress/completions', $data);
    }
}
