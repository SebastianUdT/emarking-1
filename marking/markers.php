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
 * Page to send a new print order
 *
 * @package    mod
 * @subpackage emarking
 * @copyright  2014 Jorge Villalón
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (dirname(dirname(dirname(dirname(__FILE__ )))) . '/config.php');
require_once ($CFG->dirroot . "/mod/emarking/locallib.php");
require_once ($CFG->dirroot . "/grade/grading/form/rubric/renderer.php");
require_once ("forms/markers_form.php");
require_once ("forms/pages_form.php");

global $DB, $USER;

// Obtain parameter from URL
$cmid = required_param('id', PARAM_INT);
$criterionid = optional_param('criterion', 0, PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHA);

if(!$cm = get_coursemodule_from_id('emarking', $cmid)) {
	print_error ( get_string('invalidid','mod_emarking' ) . " id: $cmid" );
}

if(!$emarking = $DB->get_record('emarking', array('id'=>$cm->instance))) {
	print_error ( get_string('invalidid','mod_emarking' ) . " id: $cmid" );
}

// Validate that the parameter corresponds to a course
if (! $course = $DB->get_record ( 'course', array ('id' => $emarking->course))) {
	print_error ( get_string('invalidcourseid','mod_emarking' ) . " id: $courseid" );
}

if($criterionid > 0) {
    $criterion = $DB->get_record('gradingform_rubric_criteria', array('id'=>$criterionid));
    if($criterion == null) {
        print_error("Invalid criterion id");
    }
}

$context = context_module::instance ( $cm->id );

$url = new moodle_url('/mod/emarking/marking/markers.php',array('id'=>$cmid));
// First check that the user is logged in
require_login($course->id);

if (isguestuser ()) {
	die ();
}

// Get rubric instance
list($gradingmanager, $gradingmethod) = emarking_validate_rubric($context);

// As we have a rubric we can get the controller
$rubriccontroller = $gradingmanager->get_controller($gradingmethod);
if(!$rubriccontroller instanceof gradingform_rubric_controller) {
    print_error(get_string('invalidrubric', 'mod_emarking'));
}

$definition = $rubriccontroller->get_definition();

$PAGE->set_context ( $context );
$PAGE->set_course($course);
$PAGE->set_cm($cm);
$PAGE->set_url ( $url );
$PAGE->set_pagelayout ( 'incourse' );
$PAGE->set_title(get_string('emarking', 'mod_emarking'));
$PAGE->navbar->add(get_string('markers','mod_emarking'));

// Verify capability for security issues
if (! has_capability ( 'mod/emarking:assignmarkers', $context )) {
	$item = array (
			'context' => context_module::instance ( $cm->id ),
			'objectid' => $cm->id,
	);
	// Add to Moodle log so some auditing can be done
	\mod_emarking\event\markers_assigned::create ( $item )->trigger ();
	print_error ( get_string('invalidaccess','mod_emarking' ) );
}

echo $OUTPUT->header();

echo $OUTPUT->tabtree(emarking_tabs($context, $cm, $emarking), "markers" );

$mform_markers = new emarking_markers_form(null,
    array('context'=>$context, 'criteria'=>$definition->rubric_criteria, 'id'=>$cmid, 'emarking'=>$emarking, "action"=>"addmarkers"));

if($mform_markers->get_data()) {
    $newmarkers = process_mform($mform_markers, "addmarkers", $emarking);
}

if($action === 'deletemarkers') {
    $DB->delete_records('emarking_marker_criterion', array('emarking'=>$emarking->id, 'criterion'=>$criterion->id));
    echo $OUTPUT->notification(get_string("transactionsuccessfull", "mod_emarking"), 'notifysuccess');
}

$nummarkerscriteria = $DB->count_records(
    "emarking_marker_criterion",
    array("emarking"=>$emarking->id));

$markercriteria = $DB->get_recordset_sql("
        SELECT 
        id, 
        description, 
        GROUP_CONCAT(uid) AS markers,
        sortorder
    FROM (
    SELECT 
        c.id, 
        c.description,
        c.sortorder, 
        u.id as uid
    FROM {gradingform_rubric_criteria} as c
    LEFT JOIN {emarking_marker_criterion} as mc ON (c.definitionid = :definition AND mc.emarking = :emarking AND c.id = mc.criterion)
    LEFT JOIN {user} as u ON (mc.marker = u.id)
    WHERE c.definitionid = :definition2
    ORDER BY c.id ASC, u.lastname ASC) as T
    GROUP BY id", 
    array("definition"=>$definition->id, "definition2"=>$definition->id, "emarking"=>$emarking->id));

    $data = array();
    foreach($markercriteria as $d) {
        $urldelete = new moodle_url('/mod/emarking/marking/markers.php', array('id'=>$cm->id, 'criterion'=>$d->id, 'action'=>'deletemarkers'));
        $markershtml = "";
        if($d->markers) {
        $markers = explode(",", $d->markers);
        foreach($markers as $marker) {
            $u = $DB->get_record("user", array("id"=>$marker));
            $markershtml .= $OUTPUT->user_picture($u);
        }
        $markershtml .= $OUTPUT->action_link($urldelete, get_string("delete"), null, array("class"=>"rowactions"));
        }
        $row = array();
        $row[] = $d->description;
        $row[] = $markershtml;
        $data[] = $row;
    }
    $table = new html_table();
    $table->head = array(
        get_string("criterion", "mod_emarking"), 
        get_string("markers", "mod_emarking"));
    $table->colclasses = array(
        null,
        null
    );
    $table->data = $data;

    $numpagescriteria = $DB->count_records(
        "emarking_page_criterion",
        array("emarking"=>$emarking->id));
    
    
if($nummarkerscriteria == 0 && $numpagescriteria == 0) {
    echo $OUTPUT->box(get_string("markerscanseewholerubric", "mod_emarking"));
    echo $OUTPUT->box(get_string("markerscanseeallpages", "mod_emarking"));
} else if ($nummarkerscriteria > 0 && $numpagescriteria == 0) {
    echo $OUTPUT->box(get_string("markerscanseeselectedcriteria", "mod_emarking"));
    echo $OUTPUT->box(get_string("markerscanseeallpages", "mod_emarking"));
} else if($nummarkerscriteria == 0 && $numpagescriteria > 0) {
    echo $OUTPUT->notification(get_string("markerscanseenothing", "mod_emarking"), "notifyproblem");
} else {
    echo $OUTPUT->box(get_string("markerscanseeselectedcriteria", "mod_emarking"));
    echo $OUTPUT->box(get_string("markerscanseepageswithcriteria", "mod_emarking"));
}

echo html_writer::table($table);

$mform_markers->display();

echo $OUTPUT->footer();

function process_mform($mform, $action, $emarking) {
    global $DB, $OUTPUT;
    
    if($mform->get_data()) {
        if($action !== $mform->get_data()->action)
            return;
        if($action === "addmarkers") {
            $datalist = $mform->get_data()->datamarkers;
        } else {
            $datalist = $mform->get_data()->datapages;
        }
        $toinsert = array();
        foreach($datalist as $data) {
            if($action === "addmarkers") {
                $criteria = $mform->get_data()->criteriamarkers;
            } else {
                $criteria = $mform->get_data()->criteriapages;
            }
            foreach($criteria as $criterion) {
                $association = new stdClass();
                $association->data = $data;
                $association->criterion = $criterion;
                $toinsert[] = $association;
            }
        }
    
        if($action === "addmarkers") {
            $blocknum = $DB->get_field_sql("SELECT max(block) FROM {emarking_marker_criterion} WHERE emarking = ?", array($emarking->id));
        } else {
            $blocknum = $DB->get_field_sql("SELECT max(block) FROM {emarking_page_criterion} WHERE emarking = ?", array($emarking->id));
        }
    
        if(!$blocknum) {
            $blocknum = 1;
        } else {
            $blocknum++;;
        }
    
        foreach($toinsert as $data)  {
            if($action === "addmarkers") {
                $association = $DB->get_record("emarking_marker_criterion", array("emarking"=>$emarking->id, "criterion"=>$data->criterion, "marker"=>$data->data));
                $tablename = "emarking_marker_criterion";
            } else {
                $association = $DB->get_record("emarking_page_criterion", array("emarking"=>$emarking->id, "criterion"=>$data->criterion, "page"=>$data->data));
                $tablename = "emarking_page_criterion";
            }
            if($association) {
                $association->block = $blocknum;
                $DB->update_record($tablename, $association);
            } else {
                $association = new stdClass();
                $association->emarking = $emarking->id;
                $association->criterion = $data->criterion;
                $association->block = $blocknum;
                $association->timecreated = time();
                $association->timemodified = time();
    
                if($action === "addmarkers") {
                    $association->marker = $data->data;
                } else {
                    $association->page = $data->data;
                }
    
                $association->id = $DB->insert_record($tablename, $association);
            }
        }
        echo $OUTPUT->notification(get_string('saved', 'mod_emarking'),'notifysuccess');
    }
}