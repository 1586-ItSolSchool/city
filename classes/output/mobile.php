<?php
namespace mod_certificate\output;
 
defined('MOODLE_INTERNAL') || die();
 
use context_module;
use mod_certificate_external;
 
/**
 * Mobile output class for certificate
 *
 * @package    mod_certificate
 * @copyright  2018 Juan Leyva
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
 
    /**
     * Returns the certificate course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB;
 
        $args = (object) $args;
        $cm = get_coursemodule_from_id('certificate', $args->cmid);
 
        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);
 
        $context = context_module::instance($cm->id);
 
        require_capability ('mod/certificate:view', $context);
        if ($args->userid != $USER->id) {
            require_capability('mod/certificate:manage', $context);
        }
        $certificate = $DB->get_record('certificate', array('id' => $cm->instance));
 
        // Get certificates from external (taking care of exceptions).
        try {
            $issued = mod_certificate_external::issue_certificate($cm->instance);
            $certificates = mod_certificate_external::get_issued_certificates($cm->instance);
            $issues = array_values($certificates['issues']); // Make it mustache compatible.
        } catch (Exception $e) {
            $issues = array();
        }
 
        // Set timemodified for each certificate.
        foreach ($issues as $issue) {
            if (empty($issue->timemodified)) {
                    $issue->timemodified = $issue->timecreated;
            }
        }
 
        $showget = true;
        if ($certificate->requiredtime && !has_capability('mod/certificate:manage', $context)) {
            if (certificate_get_course_time($certificate->course) < ($certificate->requiredtime * 60)) {
                    $showget = false;
            }
        }
 
        $certificate->name = format_string($certificate->name);
        list($certificate->intro, $certificate->introformat) =
                        external_format_text($certificate->intro, $certificate->introformat, $context->id,'mod_certificate', 'intro');
        $data = array(
            'certificate' => $certificate,
            'showget' => $showget && count($issues) > 0,
            'issues' => $issues,
            'issue' => $issues[0],
            'numissues' => count($issues),
            'cmid' => $cm->id,
            'courseid' => $args->courseid
        );
 
        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_certificate/mobile_view_page', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => $issues,
        ];
    }
}