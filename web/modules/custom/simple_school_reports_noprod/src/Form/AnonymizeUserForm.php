<?php

namespace Drupal\simple_school_reports_noprod\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\simple_school_reports_core\AbsenceDayHandler;
use Drupal\simple_school_reports_core\Service\EmailService;
use Drupal\simple_school_reports_core\Service\EmailServiceInterface;
use Drupal\simple_school_reports_core\Service\ReplaceTokenServiceInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form for AnonymizeUserForm.
 */
class AnonymizeUserForm extends ConfirmFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymize_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Anonymize all user');
  }

  public function getCancelRoute() {
    return '<front>';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRoute());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Save');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if ($this->currentUser()->id() != 1) {
      $form_state->setError($form, $this->t('Forbidden action'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('confirm')) {
      $this->logger('confirm_form')->error('Confirm issue!');
      $this->messenger()->addError($this->t('Something went wrong. Try again.'));
      $form_state->setRebuild(TRUE);
      return;
    }

    if ($form_state->getValue('confirm')) {

      // Initialize batch (to set title).
      $batch = [
        'title' => $this->t('Processing'),
        'init_message' => $this->t('Processing'),
        'progress_message' => $this->t('Processed @current out of @total.'),
        'operations' => [],
      ];

      $student_uids = $this->entityTypeManager
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('roles', 'student')
        ->execute();

      foreach ($student_uids as $student_uid) {
        $batch['operations'][] = [[self::class, 'anonymizeUser'], [$student_uid, [], []]];
      }

      $all_uids = $this->entityTypeManager
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->execute();

      foreach ($all_uids as $uid) {
        $batch['operations'][] = [[self::class, 'anonymizeUser'], [$uid, [], []]];
      }

      if (!empty($batch['operations'])) {
        batch_set($batch);
      }
      else {
        $this->messenger()->addWarning($this->t('No mail has been sent.'));
      }
    }
  }

  public static function anonymizeUser($uid, $overrides, $address_fields, $context) {
    if (!empty($context['results']['processed_uid'][$uid])) {
      return;
    }

    if ($uid <= 1) {
      return;
    }

    /** @var \Drupal\user\UserInterface $user */
    $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);

    if (!$user) {
      return;
    }

    $gender = $overrides['field_gender'] ?? $user->get('field_gender')->value;
    if ($gender !== 'male' && $gender !== 'female') {
      $gender = mt_rand(0,1) ? 'male' : 'female';
      $user->set('field_gender', $gender);
    }

    $last_name = self::getRandomLastName();
    $first_name = self::getRandomFirstName($gender);

    $address = self::getRandomAddress($address_fields);
    $user->set('field_first_name', $first_name);
    $user->set('field_last_name', $last_name);
    $user->set('field_address', $address);

    if ($user->hasRole('student')) {
      $now = new \DateTime();
      $this_year = $now->format('Y');
      $this_month = $now->format('n');
      $is_spring = $this_month <= 6;

      $grade = $user->get('field_grade')->value;
      if ($grade === NULL || $grade < 0) {
        $age = mt_rand(3, 5);
      }
      elseif ($grade > 9) {
        $age = mt_rand(17, 20);
      }
      else {
        $age = 6 + $grade;
      }

      if ($is_spring) {
        $age++;
      }

      $birth_year = $this_year - $age;

      $birth_date = new \DateTime('2000-01-01 00:00:00');
      $birth_date->setDate($birth_year, mt_rand(1,12), mt_rand(1,28));

      $user->set('field_birth_date_source', 'birth_date');
      $user->set('field_birth_date', $birth_date->getTimestamp());
    }

    $i = 0;
    /** @var UserInterface $caregiver */
    foreach ($user->get('field_caregivers')->referencedEntities() as $caregiver) {
      $caregiver_fields = [
        'field_last_name' => $last_name,
        'field_gender' => $i % 2 ? 'male' : 'female',
      ];

      $caregiver_address_fields = [];
      $fields_to_copy = ['field_zip_code', 'field_street_address', 'field_city'];

      foreach ($fields_to_copy as $field_to_copy) {
        $caregiver_address_fields[$field_to_copy] = $address->get($field_to_copy)->value ?? '';
      }
      self::anonymizeUser($caregiver->id(), $caregiver_fields, $caregiver_address_fields, $context);
      $i++;
    }

    foreach ($overrides as $field => $value) {
      $user->set($field, $value);
    }

    $last_name =  $user->get('field_last_name')->value;
    $first_name = $user->get('field_first_name')->value;
    $email = mb_strtolower($first_name) . '.' . mb_strtolower($last_name) . '_' . $user->id() . '@example.com';
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $user->set('mail', $email);

    $phone = '070' . mt_rand(1234567, 9999999);
    $user->set('field_telephone_number', $phone);

    $user->save();
    $context['results']['processed_uid'][$uid] = TRUE;
  }

  public static function getRandomAddress(array $address_fields) {
    $zip = mt_rand(12345, 99999);
    $city = 'Demostad';
    $street_suffixes = ['gränd', 'gatan', 'väg'];
    $street = self::getRandomFirstName('male') . $street_suffixes[mt_rand(0, count($street_suffixes) - 1)] . ' ' . mt_rand(1,40);

    /** @var \Drupal\Core\Entity\EntityStorageInterface $paragraph_storage */
    $paragraph_storage = \Drupal::entityTypeManager()
      ->getStorage('paragraph');

    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $address = $paragraph_storage->create([
      'type' => 'address',
      'langcode' => 'sv',
    ]);

    $address->set('field_city', $city);
    $address->set('field_street_address', $street);
    $address->set('field_zip_code', $zip);

    foreach ($address_fields as $field => $value) {
      $address->set($field, $value);
    }
    $address->save();
    return $address;
  }

  public static function getRandomLastName() {
    $source = [
      'Andersson',
      'Johansson',
      'Karlsson',
      'Nilsson',
      'Eriksson',
      'Larsson',
      'Olsson',
      'Persson',
      'Svensson',
      'Gustafsson',
      'Pettersson',
      'Jonsson',
      'Jansson',
      'Hansson',
      'Bengtsson',
      'Jönsson',
      'Lindberg',
      'Jakobsson',
      'Magnusson',
      'Olofsson',
      'Lindström',
      'Lindqvist',
      'Lindgren',
      'Berg',
      'Axelsson',
      'Bergström',
      'Lundberg',
      'Lind',
      'Lundgren',
      'Lundqvist',
      'Mattsson',
      'Berglund',
      'Fredriksson',
      'Sandberg',
      'Henriksson',
      'Forsberg',
      'Sjöberg',
      'Ali',
      'Wallin',
      'Mohamed',
      'Engström',
      'Eklund',
      'Danielsson',
      'Lundin',
      'Håkansson',
      'Björk',
      'Bergman',
      'Gunnarsson',
      'Holm',
      'Wikström',
      'Samuelsson',
      'Isaksson',
      'Fransson',
      'Bergqvist',
      'Nyström',
      'Holmberg',
      'Arvidsson',
      'Löfgren',
      'Söderberg',
      'Nyberg',
      'Blomqvist',
      'Claesson',
      'Nordström',
      'Ahmed',
      'Mårtensson',
      'Lundström',
      'Hassan',
      'Viklund',
      'Björklund',
      'Eliasson',
      'Pålsson',
      'Berggren',
      'Sandström',
      'Lund',
      'Nordin',
      'Ström',
      'Åberg',
      'Falk',
      'Ekström',
      'Hermansson',
      'Holmgren',
      'Hellström',
      'Dahlberg',
      'Hedlund',
      'Sundberg',
      'Sjögren',
      'Ek',
      'Blom',
      'Abrahamsson',
      'Martinsson',
      'Öberg',
      'Andreasson',
      'Strömberg',
      'Månsson',
      'Hansen',
      'Åkesson',
      'Dahl',
      'Lindholm',
      'Norberg',
      'Holmqvist',
    ];

    return $source[mt_rand(0, count($source) - 1)];
  }

  public static function getRandomFirstName(string $gender) {
    $source = [
      'male' => [
        'Lars',
        'Anders',
        'Johan',
        'Peter',
        'Jan',
        'Daniel',
        'Mikael',
        'Erik',
        'Per',
        'Fredrik',
        'Hans',
        'Andreas',
        'Stefan',
        'Magnus',
        'Mats',
        'Jonas',
        'Bengt',
        'Alexander',
        'Martin',
        'Thomas',
        'Bo',
        'Karl',
        'Nils',
        'Björn',
        'Leif',
        'David',
        'Emil',
        'Ulf',
        'Sven',
        'Simon',
        'Henrik',
        'Mattias',
        'Marcus',
        'Anton',
        'Patrik',
        'Robert',
        'William',
        'Kjell',
        'Joakim',
        'Håkan',
        'Tommy',
        'Göran',
        'Christer',
        'Adam',
        'Carl',
        'Rolf',
        'Lennart',
        'Robin',
        'Niklas',
        'Oscar',
        'Sebastian',
        'Elias',
        'Tobias',
        'John',
        'Tomas',
        'Gustav',
        'Stig',
        'Michael',
        'Filip',
        'Axel',
        'Linus',
        'Christian',
        'Viktor',
        'Hugo',
        'Roger',
        'Oskar',
        'Jonathan',
        'Jesper',
        'Oliver',
        'Albin',
        'Kent',
        'Rasmus',
        'Ali',
        'Max',
        'Jörgen',
        'Joel',
        'Gunnar',
        'Victor',
        'Olle',
        'Liam',
        'Lucas',
        'Leo',
        'Jimmy',
        'Åke',
        'Pontus',
        'Markus',
        'Mohammad',
        'Kenneth',
        'Samuel',
        'Kevin',
        'Dennis',
        'Christoffer',
        'Gabriel',
        'Arvid',
        'Felix',
        'Isak',
        'Philip',
        'Lukas',
        'Dan',
        'Hampus',
        'Rickard',
        'Torbjörn',
        'Ludvig',
        'Olof',
        'Jacob',
        'Jens',
        'Benjamin',
        'Kurt',
        'Arne',
        'Jakob',
        'Bertil',
        'Johannes',
        'Mohamed',
        'Roland',
        'Adrian',
        'Noah',
        'Mathias',
        'Niclas',
        'Alfred',
        'Vincent',
        'Tony',
        'Ahmad',
        'Ola',
        'Charlie',
        'Eric',
        'Claes',
        'Edvin',
        'Richard',
        'Sten',
        'Ahmed',
        'Mohammed',
        'Alf',
        'Tim',
        'Theo',
        'Kim',
        'Conny',
        'Elliot',
        'Bernt',
        'Ove',
        'Pär',
        'Ingemar',
        'Mohamad',
        'Nicklas',
        'Kristoffer',
        'Leon',
        'Melvin',
        'Harry',
        'Staffan',
        'Johnny',
        'Krister',
        'Gustaf',
        'Melker',
        'Viggo',
        'Rune',
        'Börje',
        'Ronny',
        'André',
        'Malte',
        'Jack',
        'Alex',
        'Benny',
        'Sören',
        'Omar',
        'Sixten',
        'Ibrahim',
        'Paul',
        'Tom',
        'Frank',
        'Noel',
        'Jonny',
        'Kristian',
        'Klas',
        'Sam',
        'Theodor',
        'Casper',
        'Henry',
        'August',
        'Morgan',
        'Alvin',
        'Christopher',
        'Hannes',
        'Josef',
        'Jonatan',
        'Petter',
        'Urban',
      ],
      'female' => [
        'Anna',
        'Maria',
        'Eva',
        'Karin',
        'Lena',
        'Emma',
        'Kerstin',
        'Sara',
        'Malin',
        'Ingrid',
        'Linda',
        'Elin',
        'Birgitta',
        'Marie',
        'Inger',
        'Johanna',
        'Hanna',
        'Sofia',
        'Annika',
        'Ulla',
        'Julia',
        'Susanne',
        'Jenny',
        'Carina',
        'Ida',
        'Christina',
        'Helena',
        'Åsa',
        'Kristina',
        'Camilla',
        'Gunilla',
        'Sandra',
        'Anita',
        'Monica',
        'Amanda',
        'Cecilia',
        'Emelie',
        'Margareta',
        'Jessica',
        'Frida',
        'Elsa',
        'Alice',
        'Barbro',
        'Marianne',
        'Lisa',
        'Elisabeth',
        'Siv',
        'Maja',
        'Ulrika',
        'Anette',
        'Ebba',
        'Caroline',
        'Katarina',
        'Agneta',
        'Lina',
        'Matilda',
        'Pia',
        'Berit',
        'Gun',
        'Ella',
        'Ellen',
        'Astrid',
        'Yvonne',
        'Moa',
        'Louise',
        'Agnes',
        'Mona',
        'Linnéa',
        'Olivia',
        'Britt',
        'Emilia',
        'Therese',
        'Alva',
        'Ann',
        'Anneli',
        'Felicia',
        'Linnea',
        'Alexandra',
        'Pernilla',
        'Sofie',
        'Wilma',
        'Gunnel',
        'Monika',
        'Lovisa',
        'Nina',
        'Ingela',
        'Stina',
        'Madeleine',
        'Charlotte',
        'Linn',
        'Saga',
        'Alma',
        'Sonja',
        'Petra',
        'Birgit',
        'Rebecca',
        'Erika',
        'Alicia',
        'Josefin',
        'Josefine',
        'Britt-Marie',
        'Inga',
        'Klara',
        'Gerd',
        'Elisabet',
        'Isabelle',
        'Jeanette',
        'Jennifer',
        'Vera',
        'Lisbeth',
        'Evelina',
        'Nathalie',
        'Anne',
        'Veronica',
        'Signe',
        'Isabella',
        'Helen',
        'Sanna',
        'Marita',
        'Clara',
        'Irene',
        'Jennie',
        'Annelie',
        'Selma',
        'Kajsa',
        'Molly',
        'Stella',
        'Victoria',
        'Solveig',
        'Rebecka',
        'Angelica',
        'Fanny',
        'Laila',
        'Elvira',
        'Ylva',
        'Filippa',
        'Martina',
        'Nora',
        'Märta',
        'Maj',
        'Nellie',
        'Rut',
        'Helene',
        'Freja',
        'Ann-Charlotte',
        'Mikaela',
        'Maud',
        'Tina',
        'Nova',
        'Annette',
        'Sarah',
        'Marina',
        'Anna-Karin',
        'Tilda',
        'Tove',
        'Anna-Lena',
        'Susanna',
        'Tuva',
        'Gudrun',
        'Gunvor',
        'Iris',
        'Sigrid',
        'My',
        'Lilly',
        'Cornelia',
        'Tilde',
        'Karolina',
        'Annica',
        'Gabriella',
        'Elina',
        'Mia',
        'Charlotta',
        'Ingegerd',
        'Ellinor',
        'Tyra',
        'Carolina',
        'Thea',
        'Marie-Louise',
        'Maj-Britt',
        'Annie',
        'Siri',
        'Ann-Christin',
        'Lilian',
        'Magdalena',
        'Ester',
        'Diana',
        'Ulla-Britt',
        'Ann-Marie',
        'Viktoria',
        'Britta',
        'Ronja',
        'Ewa',
        'Rose-Marie',
        'Inga-Lill',
        'Fatima',
        'Natalie',
        'Carin',
        'Mathilda',
        'Liv',
      ],
    ];
    return $source[$gender][mt_rand(0, count($source[$gender]) - 1)];
  }
}
