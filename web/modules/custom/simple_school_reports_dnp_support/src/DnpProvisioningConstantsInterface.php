<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_dnp_support;

/**
 * Provides an interface defining a dnp provisioning entity type.
 */
interface DnpProvisioningConstantsInterface {

  public const DNP_CLASSES_SHEET = 'classes';

  public const DNP_SUBJECT_GROUPS_SHEET = 'subjects';

  public const DNP_STUDENTS_SHEET = 'students';

  public const DNP_STAFF_SHEET = 'staff';

  public const FILE_SHEET_MAP = [
    3 => self::DNP_CLASSES_SHEET,
    4 => self::DNP_SUBJECT_GROUPS_SHEET,
    5 => self::DNP_STUDENTS_SHEET,
    6 => self::DNP_STAFF_SHEET,
  ];


  public const HEADERS_CLASSES = [
    'id' => 'Klass-ID',
    'name' => 'Klass',
    'school_form' => 'Skolform',
    'school_unit_code' => 'Skolenhetskod',
    'remove' => 'Ta bort',
  ];

  public const CELL_SHEET_MAP_CLASSES = [
    'first_row' => 3,
    'id' => 'A',
    'name' => 'B',
    'school_form' => 'C',
    'school_unit_code' => 'D',
    'remove' => 'E',
  ];

  public const HEADERS_SUBJECT_GROUPS = [
    'id' => 'Grupp-ID',
    'name' => 'Ämnesgrupp',
    'school_form' => 'Skolform',
    'test_activity_name' => 'Provaktivitetsnamn',
    'school_unit_code' => 'Skolenhetskod',
    'remove' => 'Ta bort',
  ];

  public const CELL_SHEET_MAP_SUBJECT_GROUPS = [
    'first_row' => 3,
    'id' => 'A',
    'name' => 'B',
    'school_form' => 'C',
    'test_activity_name' => 'D',
    'school_unit_code' => 'E',
    'remove' => 'F',
  ];

  public const HEADERS_STUDENTS = [
    'id' => 'Person-ID',
    'username' => 'Federationsanvändarnamn',
    'ssn' => 'Personnummer',
    'secrecy_marking' => 'Sekretessmarkering',
    'first_name' => 'Förnamn',
    'middle_name' => 'Mellannamn',
    'last_name' => 'Efternamn',
    'email' => 'E-postadress',
    'school_form' => 'Skolform',
    'school_unit_code' => 'Skolenhetskod',
    'grade' => 'Årskurs',
    'study_path_code' => 'Studievägskod',
    'class' => 'Klass',
    'subject_groups' => 'Ämnesgrupper',
    'remove' => 'Ta bort',
  ];

  public const CELL_SHEET_MAP_STUDENTS = [
    'first_row' => 3,
    'id' => 'A',
    'username' => 'B',
    'ssn' => 'C',
    'secrecy_marking' => 'D',
    'first_name' => 'E',
    'middle_name' => 'F',
    'last_name' => 'G',
    'email' => 'H',
    'school_form' => 'I',
    'school_unit_code' => 'J',
    'grade' => 'K',
    'study_path_code' => 'L',
    'class' => 'M',
    'subject_groups' => 'N',
    'remove' => 'O',
  ];

  public const HEADERS_STAFF = [
    'staff_id' => 'Tjänstgörings-ID',
    'id' => 'Person-ID',
    'username' => 'Federationsanvändarnamn',
    'eduid_username' => 'EduID-Federationsanvändarnamn',
    'ssn' => 'Personnummer',
    'secrecy_marking' => 'Sekretessmarkering',
    'first_name' => 'Förnamn',
    'middle_name' => 'Mellannamn',
    'last_name' => 'Efternamn',
    'email' => 'E-postadress',
    'school_unit_code' => 'Skolenhetskod',
    'staff_category' => 'Personalkategori',
    'subject_groups' => 'Ämnesgrupper',
    'remove' => 'Ta bort',
  ];

  public const CELL_SHEET_MAP_STAFF = [
    'first_row' => 3,
    'staff_id' => 'A',
    'id' => 'B',
    'username' => 'C',
    'eduid_username' => 'D',
    'ssn' => 'E',
    'secrecy_marking' => 'F',
    'first_name' => 'G',
    'middle_name' => 'H',
    'last_name' => 'I',
    'email' => 'J',
    'school_unit_code' => 'K',
    'staff_category' => 'L',
    'subject_groups' => 'M',
    'remove' => 'N',
  ];

  public const HEADERS_MAP = [
    self::DNP_CLASSES_SHEET => self::HEADERS_CLASSES,
    self::DNP_SUBJECT_GROUPS_SHEET => self::HEADERS_SUBJECT_GROUPS,
    self::DNP_STUDENTS_SHEET => self::HEADERS_STUDENTS,
    self::DNP_STAFF_SHEET => self::HEADERS_STAFF,
  ];

  public const CELL_SHEET_MAP = [
    self::DNP_CLASSES_SHEET => self::CELL_SHEET_MAP_CLASSES,
    self::DNP_SUBJECT_GROUPS_SHEET => self::CELL_SHEET_MAP_SUBJECT_GROUPS,
    self::DNP_STUDENTS_SHEET => self::CELL_SHEET_MAP_STUDENTS,
    self::DNP_STAFF_SHEET => self::CELL_SHEET_MAP_STAFF,
  ];

  public const TEST_ACTIVITY_MAP = [
    6 => [
      'BI' => 'GRGRBIO01_6',
      'EN' => 'GRGRENG01_6',
      'FY' => 'GRGRFYS01_6',
      'KE' => 'GRGRKEM01_6',
      'MA' => 'GRGRMAT01_6',
      'SH' => 'GRGRSAM01_6',
      'SV' => 'GRGRSVE01_6',
      'SVA' => 'GRGRSVA01_6',
    ],
    9 => [
      'BI' => 'GRGRBIO01_9',
      'EN' => 'GRGRENG01_9',
      'FY' => 'GRGRFYS01_9',
      'GE' => 'GRGRGEO01_9',
      'HI' => 'GRGRHIS01_9',
      'KE' => 'GRGRKEM01_9',
      'MA' => 'GRGRMAT01_9',
      'RE' => 'GRGRREL01_9',
      'SH' => 'GRGRSAM01_9',
      'SV' => 'GRGRSVE01_9',
      'SVA' => 'GRGRSVA01_9',
      // Modern languages (Spanish, French, German).
      'FRA' => 'GRGRMSP01FRA_9',
      'SPA' => 'GRGRMSP01SPA_9',
      'DEU' => 'GRGRMSP01TYS_9',
    ],
  ];

  public const DNP_LIST_EXCLUDE = 1;

  public const DNP_LIST_INCLUDE = 2;

}
