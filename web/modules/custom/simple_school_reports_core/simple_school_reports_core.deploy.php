<?php

use Drupal\simple_school_reports_core\SchoolSubjectHelper;

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_core_deploy_9001() {
  SchoolSubjectHelper::importSubjects();
}

/**
 * Implements HOOK_deploy_NAME().
 */
function simple_school_reports_core_deploy_9002() {
  SchoolSubjectHelper::importGrades();
}
