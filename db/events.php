<?php
/**
 * module_assignment_created
 *
 * Class for event to be triggered when an assignment is created or updated.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - string associatetype: type of blog association, course/coursemodule.
 *      - int blogid: id of blog.
 *      - int associateid: id of associate.
 *      - string subject: blog subject.
 * }
 *
 * @package    local
 * @since      Moodle 4.1
 * @copyright  2023 Marcel Suter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

error_log("Assignment Events");
$observers = array(
    array(
        'eventname' => '\core\event\course_module_created',
        'callback' => 'local_assignment_export_observer::module_created',
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback' => 'local_assignment_export_observer::module_updated',
    )
);