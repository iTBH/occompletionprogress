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

use block_occompletionprogress\completions;

/**
 * Block occompletionprogress is defined here.
 *
 * @package    block_occompletionprogress
 * @copyright  2024 oncampus GmbH <support@oncampus.de>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_occompletionprogress extends block_base {
    /**
     * Initializes class member variables.
     */
    public function init() {
        // Needed by Moodle to differentiate between blocks.
        $this->title = get_string('pluginname', 'block_occompletionprogress');
    }

    /**
     * Returns the block contents.
     *
     * @return stdClass The block contents.
     */
    public function get_content() {
        global $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = [];
        $this->content->icons = [];

        $output = $this->page->get_renderer('block_occompletionprogress');
        $completions = new completions($COURSE);
        $completions->for_block();

        $this->content->text = $output->render($completions);

        if (has_capability('block/occompletionprogress:overview', $this->context)) {
            $url = new moodle_url(
                '/blocks/occompletionprogress/overview.php',
                ['id' => $this->instance->id, 'courseid' => $COURSE->id]
            );
            $this->content->footer = $OUTPUT->render_from_template(
                'block_occompletionprogress/footer',
                ['url' => $url->out(false)]
            );
        }

        $this->page->requires->js_call_amd('block_occompletionprogress/showinfo', 'init', []);

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediately after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_occompletionprogress');
        } else {
            $this->title = $this->config->title;
        }
    }

    /**
     * Enables global configuration of the block in settings.php.
     *
     * @return bool True if the global configuration is enabled.
     */
    public function has_config() {
        return true;
    }

    /**
     * Sets the applicable formats for the block.
     *
     * @return string[] Array of pages and permissions.
     */
    public function applicable_formats() {
        return [
                'admin' => false,
                'site-index' => false,
                'course-view' => true,
                'mod' => false,
                'my' => false,
        ];
    }
}
