<?php

namespace Drupal\simple_school_reports_core;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;

class SchoolSubjectHelper {

  public static array|null $subjectShortNameMap = NULL;

  public static function getSupportedSubjectCodes(bool $prefix_with_code = TRUE) {
    $subject_codes = [
      'BL' => 'Bild',
      'BI' => 'Biologi',
      'CBT' => 'Bonustimme',
      'EN' => 'Engelska',
      'FY' => 'Fysik',
      'GE' => 'Geografi',
      'HI' => 'Historia',
      'HKK' => 'Hem- och Konsumentkunskap',
      'IDH' => 'Idrott och Hälsa',
      'KE' => 'Kemi',
      'ML' => 'Modersmål',
      'M1' => 'Moderna språk, elevens val',
      'M2' => 'Moderna språk, språkval',
      'MA' => 'Matematik',
      'MU' => 'Musik',
      'NO' => 'Naturorienterande ämnen',
      'RE' => 'Religionskunskap',
      'SH' => 'Samhällskunskap',
      'SL' => 'Slöjd',
      'SO' => 'Samhällsorienterande ämnen',
      'SV' => 'Svenska',
      'SVA' => 'Svenska som andraspråk',
      'TK' => 'Teknik',
      'TN' => 'Teckenspråk',
    ];

    if (!\Drupal::moduleHandler()->moduleExists('simple_school_reports_absence_make_up')) {
      unset($subject_codes['CBT']);
    }

    if ($prefix_with_code) {
      foreach ($subject_codes as $code => $name) {
        if ($code === 'CBT') {
          $code = '-';
        }
        $subject_codes[$code] = '(' . $code . ') ' . $name;
      }
    }

    return $subject_codes;
  }

  public static function getSupportedLanguageSubjectCodes() {
    $language_subject_codes = ['ML', 'M1', 'M2'];

    $all_subjects = self::getSupportedSubjectCodes();

    $return = [];
    foreach ($language_subject_codes as $code) {
      $return[$code] =  $all_subjects[$code];
    }

    return $return;
  }

  public static function getSupportedLanguageCodes(bool $prefix_with_code = TRUE): array {
    $language_codes = [
      'ACE' => 'Acehnesiska, (Indonesia, Sumatra)',
      'ACH' => 'Acoli, (Uganda)',
      'AAR' => 'Afar, Danakil ( Djibouti, Eritrea, Etiopien))',
      'AFR' => 'Afrikaans',
      'AKA' => 'Akan, Asante, Fante (Ghana, Elfenbenskusten)',
      'SQI' => 'Albanska',
      'AMH' => 'Amhariska, (Etiopien)',
      'ARA' => 'Arabiska',
      'HYE' => 'Armeniska',
      'AYM' => 'Aymara, (Bolivia)',
      'AZE' => 'Azerbadjanska',
      'BAL' => 'Baluchi, Baloci, Baluci, Makrani',
      'BAM' => 'Bambara, (Västafrika)',
      'EUS' => 'Baskiska',
      'BEM' => 'Bemba, Chibemba, Chiwemba, Ichibemba, Wemba (Zambia)',
      'BEN' => 'Bengaliska',
      'BIL' => 'Bile (Nigeria)',
      'BYN' => 'Bilen, Bilein, Bileno, Bilin (Eritrea)',
      'BOS' => 'Bosniska',
      'BUL' => 'Bulgariska',
      'MYA' => 'Burmesiska',
      'CEB' => 'Cebuanska, Binisaya, Sebuano, Sugbuanon, Sugbuhanon, Visayan',
      'DAN' => 'Danska',
      'DAR' => 'Darginska, Dargi, Dargin, Dargintsy, Khiurkilinskii, Uslar',
      'PRS' => 'Dari, Parsi, Persian',
      'DIV' => 'Divehi (Maldiverna)',
      'ENG' => 'Engelska',
      'EST' => 'Estniska',
      'EWE' => 'Ewe (Ghana)',
      'FIJ' => 'Fijianska',
      'FIN' => 'Finska',
      'VLS' => 'Flamländska',
      'FRA' => 'Franska',
      'FUL' => 'Fula/Fulani',
      'FAO' => 'Färöiska',
      'GAA' => 'Ga (Ghana)',
      'KAT' => 'Georgiska',
      'GRE' => 'Grekiska',
      'KAL' => 'Grönländska',
      'GUJ' => 'Gujarati, (Indien)',
      'HEB' => 'Hebreiska',
      'HIN' => 'Hindi',
      'IND' => 'Indonesiska',
      'ISL' => 'Isländska',
      'ITA' => 'Italienska',
      'JPN' => 'Japanska',
      'YID' => 'Jiddisch',
      'KAM' => 'Kamba, Kekamba, Kikamba (Kenya)',
      'KAN' => 'Kannada, (Indien)',
      'KAR' => 'Karenska (Burma, Thailand)',
      'CAT' => 'Katalanska, (Katalonien)',
      'KAZ' => 'Kazakiska, (Kazakstan)',
      'KHM' => 'Khmer, (Kambodja)',
      'KIK' => 'Kikuyu, (Kenya)',
      'ZHO' => 'Kinesiska',
      'HAK' => 'Kinesiska, Hakka',
      'YUE' => 'Kinesiska, Kantonesiska',
      'CMN' => 'Kinesiska, Mandarin',
      'NAN' => 'Kinesiska, Min Nan',
      'KIN' => 'Kinyarwanda, (Rwanda)',
      'KIR' => 'Kirgisiska',
      'RUN' => 'Kirundi, (Burundi)',
      'KON' => 'Kongo',
      'KOR' => 'Koreanska, (Korea)',
      'ROP' => 'Kreolska',
      'HRV' => 'Kroatiska',
      'CKB' => 'Kurdiska, centr. (Irak)',
      'KMR' => 'Kurdiska, norra, (Turkiet)',
      'SDH' => 'Kurdiska, södra, (Iran)',
      'LAO' => 'Laotiska, (Laos)',
      'LAV' => 'Lettiska, (Lettland)',
      'LMA' => 'Limba',
      'LIN' => 'Lingala, (Kongo-Kinshasa, Kongo-Brazzaville)',
      'LIT' => 'Litauiska, (Litauen)',
      'LUG' => 'Luganda/Ganda, (Uganda)',
      'LUO' => 'Luo, (Kenya och Tanzania)',
      'MKD' => 'Makedonska, (Makedonien)',
      'MLG' => 'Malagaskiska',
      'MSA' => 'Malajiska',
      'MAL' => 'Malayalam (Indien)',
      'MLT' => 'Maltesiska, (Malta)',
      'MNK' => 'Mandinka (Gambia, Senegal)',
      'MRI' => 'Maori (Nya Zeeland)',
      'MAR' => 'Marathi, (Indien)',
      'MYX' => 'Masaaba, Gisu, Gugisu, Lumasaaba, Masaba (Uganda)',
      'FIT' => 'Meänkieli (Norrbotten, Tornedalen)',
      'MON' => 'Mongoliska',
      'NLD' => 'Nederländska',
      'NEP' => 'Nepalesiska',
      'NOR' => 'Norska',
      'NYA' => 'Nyanja',
      'ORM' => 'Oromo, (Etiopien, Kenya)',
      'PUS' => 'Pashto, (Afghanistan, Iran, Pakistan)',
      'PTN' => 'Patani, (Indonesien)',
      'FAS' => 'Persiska',
      'POL' => 'Polska',
      'POR' => 'Portugisiska',
      'PAN' => 'Punjabi, (Indien)',
      'RMO' => 'Romani, Abbruzzesi, Serbisk romani, Slovensk-kroatisk romani)',
      'RMN' => 'Romani, Arli, Dzambasi, Gurbeti',
      'RMC' => 'Romani, Bashaldo, Ungersk-Slovakisk romani, Romungro',
      'RMF' => 'Romani, Kale, Kalo',
      'RMY' => 'Romani, Lovara, Kalderash',
      'RML' => 'Romani, Polsk romani, Estnisk, Lettisk, Nordrysk,)',
      'RMU' => 'Romani, Resande romani, Svensk romani, Romani)',
      'RON' => 'Rumänska',
      'RUS' => 'Ryska',
      'SSY' => 'Saho (Eritrea)',
      'SMJ' => 'Samiska, Lulesamiska',
      'SME' => 'Samiska, Nordsamiska',
      'SJE' => 'Samiska, Pitesamiska',
      'SMA' => 'Samiska, Sydsamiska',
      'SJU' => 'Samiska, Umesamiska',
      'SMO' => 'Samoanska',
      'SRP' => 'Serbiska',
      'HBS' => 'Serbokroatiska',
      'SOT' => 'Sesotho, Sisutho, Souto, Suthu, Suto',
      'SNA' => 'Shona (Zimbabwe)',
      'SIN' => 'Singalesiska, (Sri lanka)',
      'SLK' => 'Slovakiska',
      'SLV' => 'Slovenska',
      'SOM' => 'Somaliska',
      'SPA' => 'Spanska',
      'SWA' => 'Swahili',
      'SYR' => 'Syriska',
      'AII' => 'Swadaya)',
      'CLD' => 'Syriska, Nyöstarameiska, Keldanska, Fallani, Kilani, Soorath, Suras, Sureth)',
      'TRU' => 'Syriska, Turoyo, nyarameiska, Suryoyo, Syryoyo, Surayt, Suriani, Turani)',
      'TGL' => 'Tagalog, (Filippinerna)',
      'TAM' => 'Tamil, (Indien, Sri lanka, Singapore)',
      'TAT' => 'Centralasien))',
      'TEL' => 'Telugu, (Indien)',
      'THA' => 'Thai, (Thailand)',
      'BOD' => 'Tibetanska, (Kina, Bhutan, Indien, Nepal)',
      'TIG' => 'Tigre, (Eritrea)',
      'TIR' => 'Tigrinja, (Etiopien, Eritrea)',
      'CES' => 'Tjeckiska',
      'CHE' => 'Tjetjenska',
      'TON' => 'Tonganska',
      'TSN' => 'Tswana, Setswana (Botswana, Sydafrika, Zimbabwe, Namibia))',
      'TUR' => 'Turkiska',
      'TUK' => 'Turkmenska',
      'DEU' => 'Tyska',
      'UIG' => 'Uiguriska, (Kina, Kazakstan, Pakistan, Pirgizistan, Padzjikistan, Indien)',
      'UKR' => 'Ukrainska',
      'HUN' => 'Ungerska',
      'URD' => 'Urdu, (Pakistan, Indien)',
      'UZB' => 'Uzbekiska, (Uzbekistan)',
      'VIE' => 'Vietnamesiska',
      'WOL' => 'Wolof, (Senegal, Gambia, Mauretanien)',
      'YOR' => 'Yoruba, Yariba, Yooba',
      'ZUL' => 'Zulu, (Sydafrika, Malawi, Moçambique, Swaziland)',
    ];

    if ($prefix_with_code) {
      foreach ($language_codes as $code => $name) {
        $language_codes[$code] = '(' . $code . ') ' . $name;
      }
    }

    return $language_codes;

  }

  public static function getSubjectShortName(?string $subject_tid): string {
    /** @var \Drupal\simple_school_reports_core\Service\SchoolSubjectServiceInterface $subject_service */
    $subject_service = \Drupal::service('simple_school_reports_core.school_subjects');
    return $subject_service->getSubjectShortName($subject_tid);
  }

  public static function importSubjects() {
    // @ToDO: Convert to import CBT as a syllabus. REMEMBER TO CHECK MAKE UP MODULE.

    $vid = 'school_subject';
    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');


    $subjects = $termStorage->loadTree($vid, 0, NULL, TRUE);
    $subject_code_exist = [];
    foreach ($subjects as $subject) {
      if ($subject->get('field_subject_code_new')->value) {
        $subject_code_exist[$subject->get('field_subject_code_new')->value] = TRUE;
      }
    }

    $to_import = [
      'BL' => 'Bild',
      'BI' => 'Biologi',
      'EN' => 'Engelska',
      'FY' => 'Fysik',
      'GE' => 'Geografi',
      'HI' => 'Historia',
      'HKK' => 'Hem- och Konsumentkunskap',
      'IDH' => 'Idrott och Hälsa',
      'KE' => 'Kemi',
      'MA' => 'Matematik',
      'MU' => 'Musik',
      'NO' => 'Naturorienterande ämnen',
      'RE' => 'Religionskunskap',
      'SH' => 'Samhällskunskap',
      'SL' => 'Slöjd',
      'SO' => 'Samhällsorienterande ämnen',
      'SV' => 'Svenska',
      'SVA' => 'Svenska som andraspråk',
      'TK' => 'Teknik',
      'TN' => 'Teckenspråk',
    ];

    $mandatory = [
      'SV' => TRUE,
      'EN' => TRUE,
      'MA' => TRUE,
    ];

    $variants = [
      'SL' => [
        '',
        'Textilslöjd',
        'Trä-/Metallslöjd',
      ],
    ];

    $term_map = [];

    /**
     * Create links in personalisation group to medlemsform.
     * $type is not a fully loaded term, BTW.
     */
    foreach ($to_import as $code => $label) {
      if (empty($subject_code_exist[$code])) {
        $variants = $variants[$code] ?? [''];

        foreach ($variants as $variant) {
          $term_label = $label;
          if (!empty($variant)) {
            $term_label .= ' ' . mb_strtolower($variant);
          }
          $status = 1;
          if ($code === 'SL' && !empty($variant)) {
            $status = 0;
          }
          if ($code === 'SVA' || $code === 'TN') {
            $status = 0;
          }
          $term = $termStorage->create([
            'name' => $term_label,
            'vid' => $vid,
            'langcode' => 'sv',
            'field_subject_code_new' => $code,
            'status' => $status,
          ]);

          if (isset($mandatory[$code])) {
            $term->set('field_mandatory', TRUE);
          }

          if (!empty($variant)) {
            $term->set('field_subject_specify', $variant);
          }

          $term->save();
          $term_map[$code] = $term;
        }
      }
    }

    $block_parent = [
      'BI' => 'NO',
      'FY' => 'NO',
      'KE' => 'NO',
      'GE' => 'SO',
      'HI' => 'SO',
      'RE' => 'SO',
      'SH' => 'SO',
    ];

    foreach ($block_parent as $code => $parent_code) {
      if (!empty($term_map[$code]) && !empty($term_map[$parent_code])) {
        $term_map[$code]->set('field_block_parent', $term_map[$parent_code]);
        $term_map[$code]->save();
      }
    }
  }

  public static function importGrades() {
    $grades = [
      'geg_grade_system' => [
        'G' => [
          'field_merit' => 10.00,
        ],
        'EG' => [
          'field_merit' => 0.00,
        ],
      ],
      'af_grade_system' => [
        'A' => [
          'field_merit' => 20.00,
        ],
        'B' => [
          'field_merit' => 17.50,
        ],
        'C' => [
          'field_merit' => 15.00,
        ],
        'D' => [
          'field_merit' => 12.50,
        ],
        'E' => [
          'field_merit' => 10.00,
        ],
        'F' => [
          'field_merit' => 0.00,
        ],
        '-' => [
          'field_merit' => 0.00,
        ],
      ],
    ];

    /** @var \Drupal\taxonomy\TermStorageInterface $termStorage */
    $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    foreach ($grades as $vid => $grade_data) {
      $map = [];
      $terms = $termStorage->loadTree($vid, 0, NULL, TRUE);

      /** @var \Drupal\taxonomy\TermInterface $term */
      foreach ($terms as $term) {
        $map[$term->label()] = $term->id();
      }

      foreach ($grade_data as $name => $fields) {
        if (isset($map[$name])) {
          continue;
        }

        $term = $termStorage->create([
          'name' => $name,
          'vid' => $vid,
          'langcode' => 'sv',
          'status' => 1,
        ]);

        foreach ($fields as $field => $value) {
          $term->set($field, $value);
        }

        $term->save();
      }
    }
  }
}
