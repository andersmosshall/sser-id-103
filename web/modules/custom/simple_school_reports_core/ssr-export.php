<?php

include 'Connect_DB.php';
include 'Check.php';
include 'Functions.php';
include 'Functions2.php';
$error = "";


if ($_SESSION['AnvandareID']!="4"){
  $link="mainmenu.php?error=Noaccess";
  header("Refresh: 0; url=$link");
  exit();
}

$json = [];

$json['email_map'] = [];


$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Amnelista";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);

while ($row = mysqli_fetch_assoc($result)) {
  $json['subjects'][$row['AmnelistaID']] = [
    'name' => $row['Amnenamn'],
    'field_subject_code' => $row['Forkortning'],
  ];

  if ($row['Amnenamn'] === 'Bonustimme') {
    $json['subjects'][$row['AmnelistaID']]['field_subject_code'] = 'CBT';
  }

  if ($row['Anm'] && in_array($row['Forkortning'], ['ML', 'M1', 'M2'])) {
    $json['subjects'][$row['AmnelistaID']]['field_language_code'] = $row['Anm'];
  }

  if ($row['Anm'] && $row['Forkortning'] === 'SL') {
    $json['subjects'][$row['AmnelistaID']]['field_subject_specify'] = $row['Anm'];
  }
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Anvandare";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);

while ($row = mysqli_fetch_assoc($result)) {

  $email = $row['Email'];

  $roles = [];

  if ($row['Typ'] === 'Administratör') {
    $roles[] = 'teacher';
  }
  else {
    $roles[] = 'teacher';
  }


  $data = [
    'field_last_name' => $row['Efternamn'],
    'field_first_name' => $row['Fornamn'],
    'mail' => $email,
    'field_telephone_number' => $row['Mobil'],
    'roles' => $roles,
    'status' => $row['Status'] === 'Aktiv' ? 1 : 0,
  ];

  $json['user'][$row['AnvandareID']] = $data;

  if ($email) {
    $json['email_map'][$email] = ['user' => $row['AnvandareID']];
  }
}




$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Elev WHERE Status='Aktiv'";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);

while ($row = mysqli_fetch_assoc($result)) {
  $student_address = [];
  $caregivers = [];
  $address1 = [];
  $address2 = [];

  if ($row['Email1'] && $row['Foralder1']) {
    $caregivers[] = $row['Email1'];
    if (empty($json['email_map'][$row['Email1']])) {
      $name_parts = explode(' ', $row['Foralder1']);

      $first_name = $name_parts[0];
      unset($name_parts[0]);
      $last_name = '?';
      if (!empty($name_parts)) {
        $last_name = implode(' ', $name_parts);
      }

      if ($row['Adress1'] || $row['Postnummer1'] || $row['Ort1']) {
        $address1 = [
          'field_street_address' => $row['Adress1'],
          'field_zip_code' => $row['Postnummer1'],
          'field_city' => $row['Ort1'],
        ];
      }

      if (empty($json['email_map'][$row['Email1']])) {
        $json['email_map'][$row['Email1']] = ['caregiver' => [
          'field_last_name' => $last_name,
          'field_first_name' => $first_name,
          'mail' => $row['Email1'],
          'field_telephone_number' => $row['Mobil1'],
          'field_address' => $address1,
        ]];
      }
    }
  }

  if ($row['Email2'] && $row['Foralder2']) {
    $caregivers[] = $row['Email2'];
    if (empty($json['email_map'][$row['Email2']])) {
      $name_parts = explode(' ', $row['Foralder2']);

      $first_name = $name_parts[0];
      unset($name_parts[0]);
      $last_name = '?';
      if (!empty($name_parts)) {
        $last_name = implode(' ', $name_parts);
      }

      if ($row['Adress2'] || $row['Postnummer2'] || $row['Ort2']) {
        $address2 = [
          'field_street_address' => $row['Adress2'],
          'field_zip_code' => $row['Postnummer2'],
          'field_city' => $row['Ort2'],
        ];
      }

      $json['email_map'][$row['Email2']] = ['caregiver' => [
        'field_last_name' => $last_name,
        'field_first_name' => $first_name,
        'mail' => $row['Email2'],
        'field_telephone_number' => $row['Mobil2'],
        'field_address' => $address2,
      ]];
    }
  }

  if ($row['AdressCopy1'] && !empty($address1)) {
    $student_address = $address1;
  }
  elseif ($row['AdressCopy2'] && !empty($address2)) {
    $student_address = $address1;
  }
  elseif ($row['AdressEgen']) {
    if ($row['Adress'] || $row['Postnummer'] || $row['Ort']) {
      $student_address = [
        'field_street_address' => $row['Adress'],
        'field_zip_code' => $row['Postnummer'],
        'field_city' => $row['Ort'],
      ];
    }
  }

  $data = [
    'field_last_name' => $row['Efternamn'],
    'field_first_name' => $row['Fornamn'],
    'roles' => ['student'],
    'status' => 1,
    'field_address' => $student_address,
    'field_mentor' => $row['Mentor'],
    'field_caregivers' => $caregivers,
    'field_grade' => $row['Arskurs'],
  ];

  if ($row['Kon'] === 'P') {
    $data['field_gender'] = 'male';
  }
  if ($row['Kon'] === 'F') {
    $data['field_gender'] = 'female';
  }

  $json['student'][$row['ElevID']] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Kurs";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);

while ($row = mysqli_fetch_assoc($result)) {
  $teachers = [];

  if ($row['AnvandareID']) {
    $teachers[] = $row['AnvandareID'];
  }
  if ($row['Anvandare2ID']) {
    $teachers[] = $row['Anvandare2ID'];
  }
  if ($row['Anvandare3ID']) {
    $teachers[] = $row['Anvandare3ID'];
  }

  $data = [
    'title' => $row['Namn'],
    'field_student' => [],
    'field_school_subject' => $row['AmnelistaID'],
    'field_teacher' => $teachers,
  ];
  $json['course'][$row['KursID']] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Kursdeltagare";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);

while ($row = mysqli_fetch_assoc($result)) {
  if (isset($json['course'][$row['KursID']]) && isset($json['student'][$row['ElevID']])) {
    $json['course'][$row['KursID']]['field_student'][$row['ElevID']] = $row['ElevID'];
  }
}


$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Franvaro";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);

while ($row = mysqli_fetch_assoc($result)) {
  if (!isset($json['student'][$row['ElevID']])) {
    continue;
  }

  $absence_type = 'reported';
  if ($row['Franvarotyp'] === 'L') {
    $absence_type = 'leave';
  }

  $from = NULL;
  $to = NULL;

  if ($row['FranvaroDatum']) {
    $from_string = $row['FranvaroDatum'] . ' 00:00:00';
    $to_string = $row['FranvaroDatum'] . ' 23:59:59';

    if ($row['Franvarotyp'] === 'AF') {
      $to_string = $row['FranvaroDatum'] . ' 12:00:00';
    }
    if ($row['Franvarotyp'] === 'AE') {
      $from_string = $row['FranvaroDatum'] . ' 12:00:00';
    }

    $from = (new DateTime($from_string))->getTimestamp();
    $to = (new DateTime($to_string))->getTimestamp();
  }

  if ($from && $to) {
    $title = 'Dagsfrånvaro ' . $json['student'][$row['ElevID']]['field_first_name'] . ' ' . $json['student'][$row['ElevID']]['field_last_name'] . ' ' . $row['FranvaroDatum'];

    $data = [
      'title' => $title,
      'field_student' => $row['ElevID'],
      'field_absence_type' => $absence_type,
      'field_absence_from' => $from,
      'field_absence_to' => $to,
    ];

    $json['day_absence'][$row['FranvaroID']] = $data;
  }
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Registrerad ORDER BY RegistreradDatum ASC";

$result = mysqli_query($GLOBALS['mysqli_link'], $query);

while ($row = mysqli_fetch_assoc($result)) {
  if (!isset($json['course'][$row['KursID']])) {
    continue;
  }

  $from = NULL;
  $to = NULL;

  if ($row['RegistreradDatum']) {
    $from_string = $row['RegistreradDatum'] . ' 12:00:00';
    $from = (new DateTime($from_string))->getTimestamp();
    if ($from && $row['Lektionstid']) {
      $to = $from + $row['Lektionstid'] * 60;
    }
  }

  if ($from && $to) {

    if (empty($json['course'][$row['KursID']]['created'])) {
      $json['course'][$row['KursID']]['created'] = $from;
    }

    $title = $json['course'][$row['KursID']]['title'] . ' ' . $from_string . ' (' . $row['Lektionstid'] . ' min)';
    $data = [
      'title' => $title,
      'field_course' => $row['KursID'],
      'field_duration' => $row['Lektionstid'],
      'field_class_start' => $from,
      'field_class_end' => $to,
      'field_student_course_attendance' => [],
    ];

    $json['course_attendance_report'][$row['RegistreradID']] = $data;
  }
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "Registrering WHERE Status='Aktiv'";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  if (!isset($json['course_attendance_report'][$row['RegistreradID']]) || !isset($json['student'][$row['ElevID']])) {
    continue;
  }

  $subject = NULL;

  $KursID = $json['course_attendance_report'][$row['RegistreradID']]['field_course'];
  if (!empty($json['course'][$KursID]['field_school_subject'])) {
    $subject = $json['course'][$KursID]['field_school_subject'];
  }

  $subject_code = NULL;
  $invalid_absence = $row['Registreringstid'] > 0 && $row['Registreringstid'] > $json['course_attendance_report'][$row['RegistreradID']]['field_duration'] ? $json['course_attendance_report'][$row['RegistreradID']]['field_duration'] : $row['Registreringstid'];

  if ($subject) {
    $data = [
      'field_student' => $row['ElevID'],
      'field_invalid_absence_original' => $invalid_absence,
      'field_invalid_absence' => $invalid_absence,
      'field_subject' => $subject,
      'field_attendance_type' => $invalid_absence > 0 && $json['course_attendance_report'][$row['RegistreradID']]['field_duration'] == $invalid_absence ? 'invalid_absence' : 'attending',
    ];

    $json['course_attendance_report'][$row['RegistreradID']]['field_student_course_attendance'][$row['ElevID']] = $data;
  }
}

foreach ($json['course_attendance_report'] as $key => $value) {
  if (empty($value['field_student_course_attendance'])) {
    unset($json['course_attendance_report'][$key]);
  }
}

$key_b_map = [];
$principle_map = [];


$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "B_Betygstermin";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  $key_b_map[$row['BetygsterminID']] = $row['KeyB'];
  $principle_map[$row['BetygsterminID']] = $row['RektorID'];

  $data = [
    'title' => $row['BetygsterminNamn'],
    'field_document_date' => (new DateTime($row['TerminDate'] . ' 00:00:00'))->getTimestamp(),
    'field_invalid_absence_from' => (new DateTime($row['BonussaldoFrom'] . ' 00:00:00'))->getTimestamp(),
    'field_invalid_absence_to' => (new DateTime($row['BonussaldoTom'] . ' 23:59:59'))->getTimestamp(),
    'field_term_type' => mb_strtolower($row['Termin']),
    'field_student_groups' => [],
  ];

  $json['grade_round'][$row['BetygsterminID']] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "B_Grupp";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {

  if (!isset($json['grade_round'][$row['BetygsterminID']])) {
    continue;
  }

  $grade_system = NULL;
  $document_type = NULL;

  if ($row['Betygtyp'] === 'Terminsbetyg') {
    $grade_system = 'af_grade_system';
    $document_type = 'term';
  }

  if ($row['Betygtyp'] === 'Slutbetyg') {
    $grade_system = 'af_grade_system';
    $document_type = 'final';
  }

  if ($row['Betygtyp'] === 'Endast måluppfyllelse') {
    $grade_system = 'geg_grade_system';
    $document_type = 'none';
  }

  if ($grade_system && $document_type) {
    $data = [
      'title' => $row['Gruppnamn'],
      'field_grade_system' => $grade_system,
      'field_document_type' => $document_type,
      'field_grade' => $row['Arskurs'],
      'field_principle' => $principle_map[$row['BetygsterminID']] ?? NULL,
      'field_grade_subject' => [],
      'field_student' => [],
    ];

    $json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']] = $data;
  }
}

$orphan_student_gender_map = [];

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "B_Gruppmedlem";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {

  if (!isset($json['grade_round'][$row['BetygsterminID']]) || !isset($json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']])) {
    continue;
  }
  if (!isset($json['student'][$row['ElevID']])) {
    $gender = NULL;
    if ($row['Kon'] === 'P') {
      $gender = 'male';
    }
    if ($row['Kon'] === 'F') {
      $gender = 'female';
    }
    $orphan_student_gender_map[$row['ElevID']] = $gender;
    continue;
  }

  $json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']]['field_student'][$row['ElevID']] = $row['ElevID'];
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "B_Registreringsstatus";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {

  if (!isset($json['grade_round'][$row['BetygsterminID']]) || !isset($json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']])) {
    continue;
  }

  $title = $json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']]['title'] . ' - ' . $json['subjects'][$row['AmnelistaID']]['name'];

  $state = NULL;

  if ($row['Status'] === 'Påbörjad') {
    $state = 'started';
  }

  if ($row['Status'] === 'Klar') {
    $state = 'done';
  }

  $data = [
    'title' => $title,
    'field_state' => $state,
    'field_school_subject' => $row['AmnelistaID'],
    'field_teacher' => [],
    'field_grade_registration' => [],
  ];


  $json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']]['field_grade_subject'][$row['AmnelistaID']] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "B_AnsvarigAnvandare";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {

  if (!isset($json['grade_round'][$row['BetygsterminID']]) || !isset($json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']])) {
    continue;
  }

  if (!isset($json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']]['field_grade_subject'][$row['AmnelistaID']])) {
    continue;
  }

  if (!isset($json['user'][$row['AnvandareID']])) {
    continue;
  }
  $json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$row['GruppID']]['field_grade_subject'][$row['AmnelistaID']]['field_teacher'][$row['AnvandareID']] = $row['AnvandareID'];
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "B_Betyg";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  if (!isset($key_b_map[$row['BetygsterminID']])) {
    continue;
  }
  $key_b = $key_b_map[$row['BetygsterminID']];

  $ElevID = dekryptera($row['ElevID'], $key_b);
  $GruppID = dekryptera($row['GruppID'], $key_b);
  $AmnelistaID = dekryptera($row['AmnelistaID'], $key_b);
  $Kommentar = dekryptera($row['Kommentar'], $key_b);


  if (!isset($json['grade_round'][$row['BetygsterminID']]) || empty($json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$GruppID]['field_grade_subject'][$AmnelistaID])) {
    continue;
  }

  $gender = NULL;

  if (!isset($json['student'][$ElevID])) {
    $gender = $orphan_student_gender_map[$ElevID] ?? NULL;
    // SSR need some kind of student id otherwise grade will be ignored in stats.
    $ElevID = $ElevID * -1;
  }

  $exclude_reason = NULL;
  $grade = $row['Betyg'];

  if ($row['Betyg'] === '**') {
    $grade = NULL;
    $exclude_reason = 'adapted_studies';
  }

  $grade_system = $json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$GruppID]['field_grade_system'];

  if ($grade_system === 'geg_grade_system' && $grade) {
    $temp_grade = NULL;
    if ($grade == 'F') {
      $temp_grade = 'EG';
    }
    if (in_array($grade, ['E', 'D', 'C', 'B', 'A',])) {
      $temp_grade = 'G';
    }
    $grade = $temp_grade;
  }

  $data = [
    'field_grade' => $grade,
    'field_student' => $ElevID,
    'field_exclude_reason' => $exclude_reason,
    'field_grade_round' => $row['BetygsterminID'],
    'field_comment' => $Kommentar,
    'field_gender' => $gender,
    'field_school_subject' => $AmnelistaID,
  ];

  $json['grade_round'][$row['BetygsterminID']]['field_student_groups'][$GruppID]['field_grade_subject'][$AmnelistaID]['field_grade_registration'][$row['BetygID']] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "I_IupOmgang";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  $data = [
    'title' => $row['IupOmgangNamn'],
  ];

  $json['iup_round'][$row['IupOmgangID']] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "I_Iup";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  if (!isset($json['iup_round'][$row['IupOmgangID']])) {
    continue;
  }
  if (!isset($json['student'][$row['ElevID']])) {
    continue;
  }

  $title = 'IUP för ' . $json['student'][$row['ElevID']]['field_first_name'] . ' ' . $json['student'][$row['ElevID']]['field_last_name'];

  $HurGickDet = iupDekryptera($row['HurGickDet']);
  $VarArVi = iupDekryptera($row['VarArVi']);
  $HurGorViSkola = iupDekryptera($row['HurGorViSkola']);
  $HurGorViElev = iupDekryptera($row['HurGorViElev']);
  $HurGorViVardnadshavare = iupDekryptera($row['HurGorViVardnadshavare']);


  $data = [
    'title' => $title,
    'field_student' => $row['ElevID'],
    'field_iup_round' => $row['IupOmgangID'],
    'field_hdig' => [
      'format' => 'plain_text_ck',
      'value' => nl2br($HurGickDet),
    ],
    'field_waw' => [
      'format' => 'plain_text_ck',
      'value' => nl2br($VarArVi),
    ],
    'field_hdwdi_school' => [
      'format' => 'plain_text_ck',
      'value' => nl2br($HurGorViSkola),
    ],
    'field_hdwdi_student' => [
      'format' => 'plain_text_ck',
      'value' => nl2br($HurGorViElev),
    ],
    'field_hdwdi_caregiver' => [
      'format' => 'plain_text_ck',
      'value' => nl2br($HurGorViVardnadshavare),
    ],
    'field_state' => $row['Klar'] === 'True' ? 'done' : 'started',
    'field_iup_goal_list' => [],
  ];

  $iup_key = $row['IupOmgangID'] . ':' . $row['ElevID'];

  $json['iup'][$iup_key] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "I_IupMal";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  if (!isset($json['iup_round'][$row['IupOmgangID']])) {
    continue;
  }
  if (!isset($json['student'][$row['ElevID']])) {
    continue;
  }

  $title = 'Mål ' . (count($json['iup'][$row['IupID']]['field_iup_goal_list']) + 1);

  $school_subject = NULL;
  if (isset($json['subjects'][$row['AmnelistaID']])) {
    $school_subject = $row['AmnelistaID'];
  }

  $teacher = NULL;
  if (isset($json['user'][$row['AnvandareID']])) {
    $teacher = $row['AnvandareID'];
  }

  $Mal = iupDekryptera($row['Mal']);
  $LarareKommentar = iupDekryptera($row['LarareKommentar']);

  $data = [
    'title' => $title,
    'field_teacher' => $teacher,
    'field_state' => !empty($LarareKommentar) ? 'done' : NULL,
    'field_student' => $row['ElevID'],
    'field_iup_round' => $row['IupOmgangID'],
    'field_iup_goal' => [
      'format' => 'plain_text_ck',
      'value' => nl2br($Mal),
    ],
    'field_teacher_comment' => [
      'format' => 'plain_text_ck',
      'value' => nl2br($LarareKommentar),
    ],
    'field_school_subject' => $school_subject,
  ];
  $iup_key = $row['IupOmgangID'] . ':' . $row['ElevID'];
  $json['iup'][$iup_key]['field_iup_goal_list'][$row['IupMalID']] = $data;
}


$key_s_map = [];

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "SO_SOTermin";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  $data = [
    'title' => $row['SOTerminNamn'],
    'field_document_date' =>  (new DateTime($row['SOTerminDate'] . ' 00:00:00'))->getTimestamp(),
    'field_term_type' => mb_strtolower($row['SOTermin']),
    'field_written_reviews_subject' =>[],
  ];

  $json['written_reviews_round'][$row['SOTerminID']] = $data;
  $key_s_map[$row['SOTerminID']] = $row['Key'];

}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "SO_Registreringsstatus";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {
  if (!isset($json['written_reviews_round'][$row['SOTerminID']])) {
    continue;
  }
  $title = 'Årskurs ' . $row['Arskurs'] . ' - ' . $json['subjects'][$row['AmnelistaID']]['name'];
  $data = [
    'title' => $title,
    'field_state' => 'done',
    'field_school_subject' => $row['AmnelistaID'],
    'field_grade' => $row['Arskurs'],
    'field_written_reviews' => [],
  ];
  $json['written_reviews_round'][$row['SOTerminID']]['field_written_reviews_subject'][$row['Arskurs'] . ':' . $row['AmnelistaID']] = $data;
}

$query = "SELECT * FROM " . $_SESSION['DBIndex'] . "SO_SO";
$result = mysqli_query($GLOBALS['mysqli_link'], $query);
while ($row = mysqli_fetch_assoc($result)) {

  $key_s = $key_s_map[$row['SOTerminID']];

  $ElevID = dekryptera($row['ElevID'], $key_s);
  $AmnelistaID = dekryptera($row['AmnelistaID'], $key_s);
  $Kommentar = dekryptera($row['Kommentar'], $key_s);

  if (!isset($json['student'][$ElevID])) {
    continue;
  }

  $Arskurs = $json['student'][$ElevID]['field_grade'] ?? '?';

  $review = NULL;
  if ($row['SO'] === 'O') {
    $review = 'ik';
  }
  if ($row['SO'] === 'G') {
    $review = 'ak';
  }
  if ($row['SO'] === 'M') {
    $review = 'mak';
  }

  if ($AmnelistaID != '-99') {
    if (!$review) {
      continue;
    }

    $fwr_key = $Arskurs . ':' . $AmnelistaID;

    if (empty($json['written_reviews_round'][$row['SOTerminID']]['field_written_reviews_subject'][$fwr_key])) {
      $title = 'Årskurs ' . $Arskurs. ' - ' . $json['subjects'][$AmnelistaID]['name'];
      $data = [
        'title' => $title,
        'field_state' => 'done',
        'field_school_subject' => $AmnelistaID,
        'field_grade' => $Arskurs === '?' ? NULL : $Arskurs,
        'field_written_reviews' => [],
      ];
      $json['written_reviews_round'][$row['SOTerminID']]['field_written_reviews_subject'][$fwr_key] = $data;
    }

    $data = [
      'field_student' => $ElevID,
      'field_review' => $review,
      'field_written_reviews_round' => $row['SOTerminID'],
      'field_grade' => $row['Arskurs'],
      'field_review_comment' => [
        'format' => 'wordsupported_format',
        'value' => nl2br($Kommentar),
      ],
    ];

    $json['written_reviews_round'][$row['SOTerminID']]['field_written_reviews_subject'][$fwr_key]['field_written_reviews'][$row['SOID']] = $data;
  }
  else {
    $title = 'Skriftligt omdöme för ' . $json['student'][$ElevID]['field_first_name'] . ' ' . $json['student'][$ElevID]['field_last_name'];
    $data = [
      'title' => $title,
      'field_student' => $ElevID,
      'field_school_efforts' => [
        'format' => 'wordsupported_format',
        'value' => nl2br($Kommentar),
      ],
      'field_written_reviews_round' => $row['SOTerminID'],
      'field_grade' => $Arskurs === '?' ? NULL : $Arskurs,
    ];
    $json['written_reviews'][$row['SOID']] = $data;
  }
}

$content = json_encode($json);

$file_name = 'output/ssr-import.json';

file_put_contents($file_name, $content);

echo '<br><br>Ladda ner filen här: ';
echo '<a href="' . $file_name . '">Ladda ner</a>';
echo '<br>(Filen raderas automatiskt från servern inom 10 minuter)<br>';


include 'Foot.php';
?>
