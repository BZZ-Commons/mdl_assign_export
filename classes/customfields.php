<?php

/**
 * creates an array of the id's for all required custom fields
 */

function custom_field_ids() {
    global $DB;
    $output = [];
    $customField = $DB->get_records(
        'customfield_field',
        null,
        'id,shortname,categoryid');
    foreach ($customField as $field) {
        $shortname = $field->shortname;
        $fieldId = $field->id;
        error_log("Field: $shortname = $fieldId" );
        $output[$shortname] = $fieldId;
    }
    return $output;
}