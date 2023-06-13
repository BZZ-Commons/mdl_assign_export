<?php
require 'customfields.php';

class local_assignment_export_observer
{
    /**
     * listener for new module
     * @param \core\event\course_module_created $event
     */
    public static function module_created(\core\event\course_module_created $event)
    {
        $event_data = $event->get_data();
        self::export_data($event_data);
    }

    /**
     * listener for updated module
     * @param \core\event\course_module_updated $event
     */
    public static function module_updated(\core\event\course_module_updated $event)
    {
        $event_data = $event->get_data();
        self::export_data($event_data);
    }

    /**
     * exporting details for each user
     * @param $event_data
     */
    private static function export_data($event_data)
    {
        global $DB;
        $customFields = custom_field_ids();

        if ($event_data['other']['modulename'] == 'assign') {
            $courseid = $event_data['courseid'];
            $customfield_data = $DB->get_record(
                "customfield_data",
                ["fieldid" => $customFields["classroom_assignment"], "instanceid" => $courseid]
            );
            $reponame = trim($customfield_data->value);

            if ($reponame == '') {
                $module = $DB->get_record('course_modules', ['id' => $event_data['objectid']]);
                $reponame = trim($module->idnumber);
            }

            try {
                if ($reponame != "") {
                    $query = "SELECT e.id, e.courseid, e.roleid, " .
                        "ue.userid, ud.data AS gh_username, " .
                        "u.username,  u.alternatename" .
                        "  FROM {enrol} AS e" .
                        "  JOIN {user_enrolments} AS ue ON (ue.enrolid = e.id)" .
                        "  JOIN {user} AS u ON (ue.userid = u.id)" .
                        "  JOIN {user_info_data} AS ud ON (ud.userid = u.id)" .
                        " WHERE e.courseid = :courseid" .
                        "   AND ud.fieldid = " . $customFields['github_username'];
                    $users = $DB->get_records_sql(
                        $query,
                        ['courseid' => $courseid]
                    );

                    foreach ($users as $id => $user) {
                        $gh_username = $user->alternatename;
                        if ($user->gh_username != '') $gh_username = $user->gh_username;
                        if ($gh_username != '') {
                            self::write_json(
                                $reponame,
                                $gh_username,
                                $courseid,
                                $module->instance,
                                $user->userid
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Ghwalin $e");
            }
        }
    }

    private static function write_json($reponame, $ghuser, $courseid, $assignmentid, $userid)
    {
        $filename = "$reponame-$ghuser.json";
        $json = "{" .
            "\"repo\": \"$reponame\"," .
            "\"courseid\": $courseid," .
            "\"assignmentid\": $assignmentid," .
            "\"actor\": \"$ghuser\"," .
            "\"userid\": $userid," .
            "\"points\": -1" .
            "}";
        $myfile = fopen("/data/grading/$filename", "w") or die("Unable to open file!");
        fwrite($myfile, $json);
        fclose($myfile);

    }
}