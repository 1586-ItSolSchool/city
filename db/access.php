<?php 
/**
 * changelog: 2019010601 initial release
 * @author ddsh
 * @see https://docs.moodle.org/dev/NEWMODULE_Adding_capabilities
 * @see https://github.com/1586-ItSolSchool/city/wiki
 */
$capabilities = array(
 
 'mod/city:editalltransactions' => array(
    'riskbitmask' => RISK_PERSONAL,
    'captype' => 'write',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => array(
        'manager' => CAP_ALLOW
    ),
),
'mod/city:editallcontracts' => array(
    'riskbitmask' => RISK_PERSONAL,
    'captype' => 'write',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => array(
        'manager' => CAP_ALLOW
    ),
),
'mod/city:commentasminecon' => array(
    'riskbitmask' => RISK_PERSONAL,
    'captype' => 'write',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => array(
        'manager' => CAP_ALLOW
    ),
),
'mod/city:createcompanies' => array(
    'riskbitmask' => RISK_PERSONAL,
    'captype' => 'write',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => array(
        'manager' => CAP_ALLOW
    ),
),

// see https://docs.moodle.org/dev/Activity_modules#access.php about this capability
'mod/city:addinstance' => array(
    'riskbitmask' => RISK_XSS,
    'captype' => 'write',
    'contextlevel' => CONTEXT_COURSE,
    'archetypes' => array(
        'editingteacher' => CAP_ALLOW,
        'manager' => CAP_ALLOW
    ),
    'clonepermissionsfrom' => 'moodle/course:manageactivities'
),
);
?>