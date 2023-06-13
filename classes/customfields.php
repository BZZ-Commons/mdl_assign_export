<?php

/**
 * creates an array of the id's for all required custom fields
 */

function custom_field_ids() {
    global $DB;
    $output = [];
    $custom_fields = $DB->get_records(
        'customfield_field',
        null,
        'id,shortname,categoryid');
    set_output($output, $custom_fields);

    $user_fields = $DB->get_records(
        'user_info_field',
        null,
        'id,shortname'
    );
    set_output($output, $user_fields);

    return $output;
}

function set_output(&$output, $fields) {
    foreach ($fields as $field) {
        $shortname = $field->shortname;
        $fieldId = $field->id;
        $output[$shortname] = $fieldId;
    }
    return $output;
}