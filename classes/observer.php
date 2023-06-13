<?php

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
        $data = self::export_data($event_data);
        self::write_file($data);
    }

    /**
     * exporting details for each user
     * @param $event_data
     */
    private static function export_data($event_data)
    {
        global $DB;
        if ($event_data['other']['modulename'] == 'assign') {
            $courseid = $event_data['courseid'];
            $customfield_data = $DB->get_record('customfield_data', ['fieldid' => 2, 'instanceid' => $courseid]);
            $reponame = trim($customfield_data->value);

            $module = $DB->get_record('course_modules', ['id' => $event_data['objectid']]);
            if ($reponame == '') $reponame = trim($module->idnumber);

            try {
                if ($reponame != '') {
                    $query = 'SELECT e.id, e.courseid, e.roleid, ' .
                        'ue.userid, ud.data AS gh_username, ' .
                        'u.username,  u.alternatename' .
                        '  FROM {enrol} AS e' .
                        '  JOIN {user_enrolments} AS ue ON (ue.enrolid = e.id)' .
                        '  JOIN {user} AS u ON (ue.userid = u.id)' .
                        '  JOIN {user_info_data} AS ud ON (ud.userid = u.id)' .
                        ' WHERE e.courseid = :courseid' .
                        '   AND ud.fieldid = 1';
                    $users = $DB->get_records_sql(
                        $query,
                        ['courseid' => $courseid]
                    );
                    $result = "{" .
                        "\"repo\": \"$reponame\"," .
                        "\"courseid\": $courseid," .
                        "\"assignmentid\": $module->instance," .
                        "\"users\": [";
                    $json = "";
                    foreach ($users as $id => $user) {
                        $gh_username = $user->alternatename;
                        if ($user->gh_username != '') $gh_username = $user->gh_username;
                        if ($gh_username != '') {
                            self::send_request(
                                $gh_username,
                                $reponame,
                                $module->instance,
                                $courseid,
                                $user->userid
                            );
                            $json .= "{" .
                                "\"actor\": \"$gh_username\"," .
                                "\"userid\": $user->userid," .
                                "\"points\": -1" .
                                "},";
                        }
                    }
                    $result .= substr($json, 0, -1) . "]";
                    return $result;
                }
            } catch (Exception $e) {
                error_log("Ghwalin $e");
            }
        }
    }

    /**
     * sends the request to the external api
     * @param $username
     * @param $reponame
     * @param $assignmentid
     * @param $courseid
     * @param $userid
     */
    private static function send_request($username, $reponame, $assignmentid, $courseid, $userid)
    {
        $url = "http://192.168.99.200/fgitapi/mdl_assign/$username/$reponame/$assignmentid/$courseid/$userid";
        $request = curl_init();
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_POST, 1);
        curl_setopt($request, CURLOPT_POSTFIELDS, '');
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($request);
        //error_log($response);

        curl_close($request);
    }

    private static function write_file($data) {
        $filename = uniqid() . ".json";
        $myfile = fopen("/data/grading/$filename.json", "w") or die("Unable to open file!");
        fwrite($myfile, $data);
        fclose($myfile);
    }
}