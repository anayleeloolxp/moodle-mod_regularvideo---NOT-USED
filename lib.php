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
 * Library functions
 *
 * @package mod_regularvideo
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in Regular Video module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function regularvideo_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function regularvideo_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param array $data the data submitted from the reset course.
 * @return array status array
 */
function regularvideo_reset_userdata($data) {
    return array();
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function regularvideo_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function regularvideo_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add regularvideo instance.
 * @param stdClass $data
 * @param mod_regularvideo_mod_form $mform
 * @return int new regularvideo instance id
 */
function regularvideo_add_instance($data, $mform = null) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;

    $data->timemodified = time();
    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth'] = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = $data->printheading;
    $displayoptions['printintro'] = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

    if ($mform) {
        $data->content = $data->regularvideo['text'];
        $data->contentformat = $data->regularvideo['format'];
    }

    $data->id = $DB->insert_record('regularvideo', $data);

    // we need to use context now, so we need to make sure all needed info is already in db
    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    $context = context_module::instance($cmid);

    if ($mform and !empty($data->regularvideo['itemid'])) {
        $draftitemid = $data->regularvideo['itemid'];
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_regularvideo', 'content', 0, regularvideo_get_editor_options($context), $data->content);
        $DB->update_record('regularvideo', $data);
    }

    return $data->id;
}

/**
 * Update regularvideo instance.
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function regularvideo_update_instance($data, $mform) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    $cmid = $data->coursemodule;
    $draftitemid = $data->regularvideo['itemid'];

    $data->timemodified = time();
    $data->id = $data->instance;
    $data->revision++;

    $displayoptions = array();
    if ($data->display == RESOURCELIB_DISPLAY_POPUP) {
        $displayoptions['popupwidth'] = $data->popupwidth;
        $displayoptions['popupheight'] = $data->popupheight;
    }
    $displayoptions['printheading'] = $data->printheading;
    $displayoptions['printintro'] = $data->printintro;
    $data->displayoptions = serialize($displayoptions);

    $data->content = $data->regularvideo['text'];
    $data->contentformat = $data->regularvideo['format'];

    $DB->update_record('regularvideo', $data);

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $data->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_regularvideo', 'content', 0, regularvideo_get_editor_options($context), $data->content);
        $DB->update_record('regularvideo', $data);
    }

    return true;
}

/**
 * Delete regularvideo instance.
 * @param int $id
 * @return bool true
 */
function regularvideo_delete_instance($id) {
    global $DB;

    if (!$regularvideo = $DB->get_record('regularvideo', array('id' => $id))) {
        return false;
    }

    // note: all context files are deleted automatically

    $DB->delete_records('regularvideo', array('id' => $regularvideo->id));

    return true;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main regularvideo display
 */
function regularvideo_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$regularvideo = $DB->get_record('regularvideo', array('id' => $coursemodule->instance),
        'id, name, display, displayoptions, intro, introformat, vimeo_video_id')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $regularvideo->name;

    $data = file_get_contents("http://vimeo.com/api/v2/video/" . $regularvideo->vimeo_video_id . ".json");
    $data = json_decode($data);

    $info->iconurl = $data[0]->thumbnail_small;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.

        $imgcontent = '<img class="reg_video_img" src="' . $data[0]->thumbnail_small . '"/>';

        $imgcontent = '';

        $info->content = $imgcontent . format_module_intro('regularvideo', $regularvideo, $coursemodule->id, false);
    }

    if ($regularvideo->display != RESOURCELIB_DISPLAY_POPUP) {
        return $info;
    }

    $fullurl = "$CFG->wwwroot/mod/regularvideo/view.php?id=$coursemodule->id&amp;inpopup=1";
    $options = empty($regularvideo->displayoptions) ? array() : unserialize($regularvideo->displayoptions);
    $width = empty($options['popupwidth']) ? 620 : $options['popupwidth'];
    $height = empty($options['popupheight']) ? 450 : $options['popupheight'];
    $wh = "width=$width,height=$height,toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";
    $info->onclick = "window.open('$fullurl', '', '$wh'); return false;";

    return $info;
}

/**
 * Lists all browsable file areas
 *
 * @package  mod_regularvideo
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function regularvideo_get_file_areas($course, $cm, $context) {
    $areas = array();
    $areas['content'] = get_string('content', 'regularvideo');
    return $areas;
}

/**
 * File browsing support for regularvideo module content area.
 *
 * @package  mod_regularvideo
 * @category files
 * @param stdClass $browser file browser instance
 * @param stdClass $areas file areas
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param int $itemid item ID
 * @param string $filepath file path
 * @param string $filename file name
 * @return file_info instance or null if not found
 */
function regularvideo_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG;

    if (!has_capability('moodle/course:managefiles', $context)) {
        // students can not peak here!
        return null;
    }

    $fs = get_file_storage();

    if ($filearea === 'content') {
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;

        $urlbase = $CFG->wwwroot . '/pluginfile.php';
        if (!$storedfile = $fs->get_file($context->id, 'mod_regularvideo', 'content', 0, $filepath, $filename)) {
            if ($filepath === '/' and $filename === '.') {
                $storedfile = new virtual_root_file($context->id, 'mod_regularvideo', 'content', 0);
            } else {
                // not found
                return null;
            }
        }
        require_once("$CFG->dirroot/mod/regularvideo/locallib.php");
        return new regularvideo_content_file_info($browser, $context, $storedfile, $urlbase, $areas[$filearea], true, true, true, false);
    }

    // note: regularvideo_intro handled in file_browser automatically

    return null;
}

/**
 * Serves the regularvideo files.
 *
 * @package  mod_regularvideo
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function regularvideo_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    // require_course_login($course, true, $cm);

    if (!has_capability('mod/regularvideo:view', $context)) {
        return false;
    }

    if ($filearea !== 'content') {
        // intro is handled automatically in pluginfile.php
        return false;
    }

    // $arg could be revision number or index.html
    $arg = array_shift($args);
    if ($arg == 'index.html' || $arg == 'index.htm') {
        // serve regularvideo content
        $filename = $arg;

        if (!$regularvideo = $DB->get_record('regularvideo', array('id' => $cm->instance), '*', MUST_EXIST)) {
            return false;
        }

        // remove @@PLUGINFILE@@/
        $content = str_replace('@@PLUGINFILE@@/', '', $regularvideo->content);

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;
        $content = format_text($content, $regularvideo->contentformat, $formatoptions);

        send_file($content, $filename, 0, 0, true, true);
    } else {
        $fs = get_file_storage();
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_regularvideo/$filearea/0/$relativepath";
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            $regularvideo = $DB->get_record('regularvideo', array('id' => $cm->instance), 'id, legacyfiles', MUST_EXIST);
            if ($regularvideo->legacyfiles != RESOURCELIB_LEGACYFILES_ACTIVE) {
                return false;
            }
            if (!$file = resourcelib_try_file_migration('/' . $relativepath, $cm->id, $cm->course, 'mod_regularvideo', 'content', 0)) {
                return false;
            }
            // file migrate - update flag
            $regularvideo->legacyfileslast = time();
            $DB->update_record('regularvideo', $regularvideo);
        }

        // finally send the file
        send_stored_file($file, null, 0, $forcedownload, $options);
    }
}

/**
 * Return a list of regularvideo types
 * @param string $regularvideotype current regularvideo type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function regularvideo_regularvideo_type_list($regularvideotype, $parentcontext, $currentcontext) {
    $moduleregularvideotype = array('mod-regularvideo-*' => get_string('regularvideo-mod-regularvideo-x', 'regularvideo'));
    return $moduleregularvideotype;
}

/**
 * Export regularvideo resource contents
 *
 * @param object $cm cm
 * @param string $baseurl baseurl
 *
 * @return array of file content
 */
function regularvideo_export_contents($cm, $baseurl) {
    global $CFG, $DB;
    $contents = array();
    $context = context_module::instance($cm->id);

    $regularvideo = $DB->get_record('regularvideo', array('id' => $cm->instance), '*', MUST_EXIST);

    // regularvideo contents
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_regularvideo', 'content', 0, 'sortorder DESC, id ASC', false);
    foreach ($files as $fileinfo) {
        $file = array();
        $file['type'] = 'file';
        $file['filename'] = $fileinfo->get_filename();
        $file['filepath'] = $fileinfo->get_filepath();
        $file['filesize'] = $fileinfo->get_filesize();
        $file['fileurl'] = file_encode_url(
            "$CFG->wwwroot/" . $baseurl,
            '/' . $context->id . '/mod_regularvideo/content/' . $regularvideo->revision . $fileinfo->get_filepath() . $fileinfo->get_filename(),
            true
        );
        $file['timecreated'] = $fileinfo->get_timecreated();
        $file['timemodified'] = $fileinfo->get_timemodified();
        $file['sortorder'] = $fileinfo->get_sortorder();
        $file['userid'] = $fileinfo->get_userid();
        $file['author'] = $fileinfo->get_author();
        $file['license'] = $fileinfo->get_license();
        $contents[] = $file;
    }

    // regularvideo html conent
    $filename = 'index.html';
    $regularvideofile = array();
    $regularvideofile['type'] = 'file';
    $regularvideofile['filename'] = $filename;
    $regularvideofile['filepath'] = '/';
    $regularvideofile['filesize'] = 0;
    $regularvideofile['fileurl'] = file_encode_url("$CFG->wwwroot/" . $baseurl, '/' . $context->id . '/mod_regularvideo/content/' . $filename, true);
    $regularvideofile['timecreated'] = null;
    $regularvideofile['timemodified'] = $regularvideo->timemodified;
    // make this file as main file
    $regularvideofile['sortorder'] = 1;
    $regularvideofile['userid'] = null;
    $regularvideofile['author'] = null;
    $regularvideofile['license'] = null;
    $contents[] = $regularvideofile;

    return $contents;
}

/**
 * Register the ability to handle drag and drop file uploads
 * @return array containing details of the files / types the mod can handle
 */
function regularvideo_dndupload_register() {
    return array('types' => array(
        array('identifier' => 'text/html', 'message' => get_string('createregularvideo', 'regularvideo')),
        array('identifier' => 'text', 'message' => get_string('createregularvideo', 'regularvideo')),
    ));
}

/**
 * Handle a file that has been uploaded
 * @param object $uploadinfo details of the file / content that has been uploaded
 * @return int instance id of the newly created mod
 */
function regularvideo_dndupload_handle($uploadinfo) {
    // Gather the required info.
    $data = new stdClass();
    $data->course = $uploadinfo->course->id;
    $data->name = $uploadinfo->displayname;
    $data->intro = '<p>' . $uploadinfo->displayname . '</p>';
    $data->introformat = FORMAT_HTML;
    if ($uploadinfo->type == 'text/html') {
        $data->contentformat = FORMAT_HTML;
        $data->content = clean_param($uploadinfo->content, PARAM_CLEANHTML);
    } else {
        $data->contentformat = FORMAT_PLAIN;
        $data->content = clean_param($uploadinfo->content, PARAM_TEXT);
    }
    $data->coursemodule = $uploadinfo->coursemodule;

    global $CFG;
    require_once($CFG->libdir . '/filelib.php');

    $leeloolxplicense = get_config('mod_regularvideo')->license;
    $url = 'https://leeloolxp.com/api_moodle.php/?action=page_info';
    $postdata = [
        'license_key' => $leeloolxplicense,
    ];

    $curl = new curl;

    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HEADER' => false,
        'CURLOPT_POST' => count($postdata),
    );

    if (!$output = $curl->post($url, $postdata, $options)) {
        notice(get_string('nolicense', 'mod_regularvideo'));
    }

    $infoleeloolxp = json_decode($output);

    if ($infoleeloolxp->status != 'false') {
        $leeloolxpurl = $infoleeloolxp->data->install_url;
    } else {
        notice(get_string('nolicense', 'mod_regularvideo'));
    }

    $url = $leeloolxpurl . '/admin/Theme_setup/get_vimeo_videos_settings';

    $postdata = [
        'license_key' => $leeloolxplicense,
    ];

    $curl = new curl;

    $options = array(
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_HEADER' => false,
        'CURLOPT_POST' => count($postdata),
    );

    if (!$output = $curl->post($url, $postdata, $options)) {
        notice(get_string('nolicense', 'mod_regularvideo'));
    }

    $resposedata = json_decode($output);
    $settingleeloolxp = $resposedata->data->vimeo_videos;
    $config = $settingleeloolxp;

    // Set the display options to the site defaults.
    // $config = get_config('regularvideo');
    $data->display = $config->display;
    $data->popupheight = $config->popupheight;
    $data->popupwidth = $config->popupwidth;
    $data->printheading = $config->printheading;
    $data->printintro = $config->printintro;

    return regularvideo_add_instance($data, null);
}
