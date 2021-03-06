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
 * Restore file
 *
 * @package   mod_regularvideo
 * @category  backup
 * @copyright 2020 Leeloo LXP (https://leeloolxp.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_regularvideo_activity_task
 */

/**
 * Structure step to restore one regularvideo activity
 */
class restore_regularvideo_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define Strucutre
     */
    protected function define_structure() {

        $paths = array();
        $paths[] = new restore_path_element('regularvideo', '/activity/regularvideo');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process video
     *
     * @param stdClass $data
     */
    protected function process_regularvideo($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // insert the regularvideo record
        $newitemid = $DB->insert_record('regularvideo', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Fucntion after execute
     */
    protected function after_execute() {
        // Add regularvideo related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_regularvideo', 'intro', null);
        $this->add_related_files('mod_regularvideo', 'content', null);
    }
}
