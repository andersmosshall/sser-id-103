<?php

namespace Drupal\simple_school_reports_module_info\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\simple_school_reports_module_info\Events\GetHelpPagesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ModuleInfoServiceInterface
 */
class ModuleInfoService implements ModuleInfoServiceInterface, EventSubscriberInterface {
  use StringTranslationTrait;

  public function __construct(
    protected Connection $connection,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected ModuleHandlerInterface $moduleHandler,
    protected EventDispatcherInterface $dispatcher,
    protected LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {}


  protected function getFormattedPrice(string $type, string $suffix = 'kr + moms'): string {
    $price = 0;
    switch ($type) {
      case 'core':
        $price = self::CORE_PRICE;
        break;
      case 'module':
        $price = self::MODULE_PRICE;
        break;
      case 'mini_module':
        $price = self::MINI_MODULE_PRICE;
        break;
      case 'core_annual':
        $price = self::CORE_ANNUAL_FEE;
        break;
      case 'core_big_annual':
        $price = self::CORE_BIG_ANNUAL_FEE;
        break;
      case 'module_annual':
        $price = self::MODULE_ANNUAL_FEE;
        break;
      case 'mini_module_annual':
        $price = self::MINI_MODULE_ANNUAL_FEE;
        break;
    }
    $value = number_format($price, 0, ',', ' ');
    if ($suffix) {
      $value .= ' ' . $suffix;
    }
    return $value;
  }

  protected function replacePrice(string $text): string {
    $text = str_replace('[[CORE_PRICE]]', $this->getFormattedPrice('core'), $text);
    $text = str_replace('[[MODULE_PRICE]]', $this->getFormattedPrice('module'), $text);
    $text = str_replace('[[MINI_MODULE_PRICE]]', $this->getFormattedPrice('mini_module'), $text);
    $text = str_replace('[[CORE_ANNUAL_FEE]]', $this->getFormattedPrice('core_annual'), $text);
    $text = str_replace('[[CORE_BIG_ANNUAL_FEE]]', $this->getFormattedPrice('core_big_annual'), $text);
    $text = str_replace('[[MODULE_ANNUAL_FEE]]', $this->getFormattedPrice('module_annual'), $text);
    $text = str_replace('[[MINI_MODULE_ANNUAL_FEE]]', $this->getFormattedPrice('mini_module_annual'), $text);
    return $text;
  }

  protected function getMap(): array {
    $map = [];

    $is_ssr_promo = $this->moduleHandler->moduleExists('ssr_promo_core');

    $core_description = '<p>Simple school reports kärna för grundskolan innefattar grundläggande funktionalitet för att kunna skapa elever, lärare och andra användare. I denna modul finns även inkluderat möjlighet att skicka ut mail till vårdnadshavare, registrera närvaro på lektioner samt registrera frånvaro för hela eller delar av dagar.</p>';

    if ($is_ssr_promo) {
//      $core_description = '
//<p>Här finner du en lista av de moduler som finns tillgängliga i Simple School Reports.</p>
//<p>Simple School Reports Kärna ligger all grundfunktionalitet och är därför inte valfri. Kärnmodulen kostar [[CORE_PRICE]]. Övriga moduler är valfria och har ett grundpris på [[MODULE_PRICE]]. Minimoduler har ett grundpris på [[MINI_MODULE_PRICE]]. Oavsett vilka moduler man väljer så gäller en kostnad på [[CORE_ANNUAL_FEE]] för grundläggande drift av Simple School Reports.</p>
//<p>Om Simple School Reports beställs med flera moduler samtidigt kan viss rabatt ges beroende på antal moduler som önskas.<a href="https://simpleschoolreports.se/kontakt" data-type="URL" data-id="https://simpleschoolreports.se/kontakt"> Kontakta Anders Mosshäll för offert här.</a></p>
//<p>Om det finns önskemål på ny funktionalitet så går det i de flesta fall att ordna genom att en ny modul skapas. Denna lista på moduler uppdateras så fort nya moduler finns tillgängliga.</p>
//<h3>Simple School Reports Kärna</h3>
//<p>Kärnmodulen innefattar grundläggande funktionalitet för att kunna skapa elever, lärare och andra användare. I denna modul finns även inkluderat möjlighet att skicka ut mail till vårdnadshavare, registrera närvaro på lektioner samt registrera frånvaro för hela eller delar av dagar.</p>';

      $core_description = '
<p>Här finner du en lista av de moduler som finns tillgängliga i Simple School Reports.</p>
<p>Simple School Reports Kärna ligger all grundfunktionalitet och är därför inte valfri.</p>
<p>Om det finns önskemål på ny funktionalitet så går det i de flesta fall att ordna genom att en ny modul skapas. Denna lista på moduler uppdateras så fort nya moduler finns tillgängliga.</p>
<h3>Simple School Reports Kärna</h3>
<p>Kärnmodulen innefattar grundläggande funktionalitet för att kunna skapa elever, lärare och andra användare. I denna modul finns även inkluderat möjlighet att skicka ut mail till vårdnadshavare, registrera närvaro på lektioner samt registrera frånvaro för hela eller delar av dagar.</p>';
    }

    $map[] = [
      'module' => 'simple_school_reports_core_gr',
      'label' => 'Simple school report kärna för grundskolan',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[CORE_PRICE]]',
      'annual_fee' => '[[CORE_ANNUAL_FEE]]',
      'description' => $core_description,
    ];

    $map[] = [
      'module' => 'simple_school_reports_core_gy',
      'label' => 'Simple school report kärna för gymnasiet',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[CORE_PRICE]]',
      'annual_fee' => '[[CORE_BIG_ANNUAL_FEE]]',
      'description' => '<p>Simple school reports kärna för gymnasiet innefattar innefattar samma grundfunktionalitet som kärnan för grundskolan men anpassad för gymnasieskolan. Både Gy2011 och Gy2025</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_absence_matrix',
      'label' => 'Frånvaromatris',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I minimodulen Frånvaromatris kan man ange dagsfrånvaro för eleverna i en matrisvy för hela veckor. Detta ger en bättre överblick över registerade frånvarodagar och man kan snabbare registera frånvaro.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_absence_make_up',
      'label' => 'Bonustimme',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Bonustimme är modul för koncept att kunna återta missad lektionstid på grund av ogiltig frånvaro. Bonustimme är ett ämne som man lägger in på en kurs och närvarotid på dessa lektioner minskar elevens totala ogiltiga frånvaro i statistiken i systemet.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_caregiver_login',
      'label' => 'Login för vårdnadshavare',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_iup', 'simple_school_reports_reviews', 'simple_school_reports_leave_application', 'simple_school_reports_consents'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen Login för vårdnadshavare kan man göra det möjligt för vårdnadshavare att logga in på Simple School Reports och t.ex. anmäla frånvaro. Som vårdnadshavare kan man också se statistik för närvaro/frånvaro samt även sammanställning av information från vissa andra moduler. T.ex. betygsstatistik eller IUP.</p><p>Det är möjligt att låsa ner inloggning för enskilda eller alla vårdnashavare även om man har denna modul installerad.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_grade_registration',
      'label' => 'Betygsregistrering',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_grade_stats', 'simple_school_reports_class'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen Betygsregistrering kan man som administratör i Simple School Reports sätta upp omgångar för betygsregistrering. Man anger där betygssättande lärare för de ämnen man vill ha aktiva och lärare kan då själva registrera betyg bara i de angivna ämnena. Genom en enkel knapptryckning kan därefter betygsdokument generas och laddas ner.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_extens_grade_export',
      'label' => 'Extensexport (MGBETYG)',
      'required_modules' => ['simple_school_reports_grade_registration'],
      'recommended_modules' => [],
      'price' => '[[MINI_MODULE_PRICE]]',
      'annual_fee' => '[[MINI_MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen Extensexport (MGBETYG) kan man i samband med export av betyg välja elevgrupper som ska inkluderas i en export för betygssystemet Extens (MGBETYG). Man kan välja om elevers kontaktuppgifter ska inkluderas i exportfilen till Extens.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_geg_grade_registration',
      'label' => 'Betygsregistrering - omdömen',
      'required_modules' => ['simple_school_reports_grade_registration'],
      'recommended_modules' => ['simple_school_reports_grade_stats'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, Betygsregistrering - omdömen, är en påbyggnad av modulen betygsregistrering och kan inte användas enskilt. I denna modul har man möjlighet att inom omgångar för betygsregistrering även lägga in omdömmen i from av "Godkänd" och "Ej godkänd" och de sammanställs enligt samma princip som betygsdokument, dock utan enskilda elevers betygsdokument. De finns även tillgängliga för betygsstatistik. Är lämpligt att använda i fall man vill dokumentera omdömen för årskurser som inte innefattar krav på betygsdokument.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_grade_stats',
      'label' => 'Betygsstatistik',
      'required_modules' => ['simple_school_reports_grade_registration'],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, Betygsstatistik, är en påbyggnad av modulen betygsregistrering och kan inte användas enskilt. I denna modul kan man skapa grafer och tabeller för betyg. Man kan jämföra olika årskurser, ämnen och betygsomgångar och man får stora möjligheter att ta fram precis de betygsstatistikunderlag man vill ha. Lämpligt för att ha med som underlag i kvalitetsrapporter eller i samband med skolinspektioner.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_reviews',
      'label' => 'Registrering av skriftliga omdömen',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen Registrering av skriftliga omdömen kan man som administratör i Simple School Reports sätta upp omgångar för skriftliga omdömen. Som lärare kan man i sina ämnen registrera omdömmen och skriva kommentarer. Man kan även skriva övergripande information om skolans insatser. Genom en enkel knapptryckning kan dokument för skriftliga omdömen genereras som följer skolverkets mall för skriftliga omdömen.</p><p>Som administratör kan man sätta upp personaliserade standardfraser som man med enkla knapptryckningar kan föra in i dokumenten.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_iup',
      'label' => 'IUP-registrering',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_class'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen IUP-Registrering kan man som administratör i Simple School Reports sätta upp omgångar för IUP-registrering (Individuell UtvecklingsPlan). Som lärare kan man då lägga till mål och utvärderingar av olika slag. Mål kan återkopplas och sammanställas till nästa omgång av IUP. IUP:er kan man med en enkel knapptryckning generera IUP-dokument som följer skolverkets mall för IUP.</p><p>Som administratör kan man sätta upp personaliserade standardfraser som man med enkla knapptryckningar kan föra in i IUP-dokumenten.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_list_templates',
      'label' => 'Listmallar',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_special_diet'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Listmallar är en modul för att skapa egna listor för eleverna. Till exempel listor för närvaro som tar hänsyn till inevarande dags frånvaroregisteringar. Eller listor för kontaktuppgifter till elever eller vårdnadshavare utifrån ett visst urval. Flera moduler ger ytterligare val för listmallar.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_budget',
      'label' => 'Budget',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I budgetmodulen kan man utfrån antal elever skapa budgetkalkyl för skolans bidrag/intäcker och utgifter. Budgeten kan följas upp och man kan få en aktuell fingervisning över balansen mellan bidrag och utgifter på så specifika budgetposter man önskar.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_consents',
      'label' => 'Samtycken',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_caregiver_login'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen för samtyken kan man skapa och inhämta samtycken för användare i Simple School Reports. Det kan vara interna samtycken för lärare eller annan personal eller samtycken från vårdnadshavare, t.ex. samtycken om att få använda bilder i sociala medier eller samla in samtycken för vaccinationer. Modulen fungerar bra ihop med modulen för login av vårdnadshavare.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_email_attachments',
      'label' => 'E-postbilagor',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MINI_MODULE_PRICE]]',
      'annual_fee' => '[[MINI_MODULE_ANNUAL_FEE]]',
      'description' => '<p>Med minimodulen E-postbilagor kan man i e-postutskick inkludera en eller flera bilagor</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_maillog_mini',
      'label' => 'E-postlogg - maillog',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MINI_MODULE_PRICE]]',
      'annual_fee' => '[[MINI_MODULE_ANNUAL_FEE]]',
      'description' => '<p>Med minimodulen E-postlogg - maillog kan man se logg över alla epost som gått ut ur systemet. På så sätt har man möjlighet att spåra om vårdnadshavare blivit meddelad eller inte. Man kan se vad som meddelats, av vem och till vem. Observera att när epost skickas ut kan man inte garantera att epost kommer fram, t.ex. kan systemet inte veta om epostadressen är rätt stavad.</p><p>Man kan endast se epostlogg för upp till 3 månader bakåt i tiden.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_special_diet',
      'label' => 'Specialkost',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_list_templates'],
      'price' => '[[MINI_MODULE_PRICE]]',
      'annual_fee' => '[[MINI_MODULE_ANNUAL_FEE]]',
      'description' => '<p>I minimodulen Specialkost kan man per eleve registrera eventuell specialkost så att skolpersonal lätt kan se den informationen, t.ex. vid skolutflykter. Har man dessutom modulen listmallar kan inkludera specialkost i listor för elevgrupper vilket kan vara användbart får skolbespisning eller liknande.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_student_di',
      'label' => 'Utvecklingssamtal för elever',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_caregiver_login'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen för Utvecklingssamtal för elever kan man skapa omgångar för utvecklingssamtal för att hålla koll och hantera möten. Mail skickas för påminnelser om mötena och man kan ställa in hur lång tid innan mötet påminnelser ska skickas. Om man har modulen för login för vårdnadshavare kan vårdnadshavare själva boka/avboka utvecklingssamtal.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_attendance_analyse',
      'label' => 'Närvaroanalys',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_class'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>I modulen närvaroanalys kan man definera skolveckor längd och ramtider. Med detta som grund kan man få fram procentuell närvaro/frånvaro för elever utifrån närvarorapporteringarna och registrerade frånvarodagar. Analysen visar närvaro giltig frånvaro samt ogiltig frånvaro.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_attendance_period_analyse',
      'label' => 'Närvaroanalys för period',
      'required_modules' => ['simple_school_reports_attendance_analyse'],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, Närvaroanalys för period, är en påbyggnad av modulen närvarostatistik och kan inte användas enskilt. I modulen närvaroanalys för period kan man ta fram närvarostatistik för en valfri period alla eller grupper av elever. Frånvaro visas även grupperat utifrån definerade procentuella grupper så att exempelvis man kan ta fram antal elever i perioden som har 15-25% frånvaro. Procentgrupperna kan man ändra själv.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_leave_application',
      'label' => 'Ledighetsansökningar',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_caregiver_login'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, Ledighetsansökningar, skapar möjlighet att lärare eller vårdnadshavare (om modul för inloggning är aktiv) kan skapa ledighetsansökningar. Dessa kan sedan hanteras av mentor eller rektor och vid godkänd ansökan registreras ledigheten automatiskt i systemet.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_extra_adaptations',
      'label' => 'Extra anpassningar',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, Extra anpassningar, skapar möjlighet att registrera extra anpassningar som en elev har i enskilda ämnen eller generellt. Vilka extra anpassningar som finns att välja mellan hanteras av administratör. Man kan lista och söka bland alla extra anpassningar bland alla elver.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_class',
      'label' => 'Klasser',
      'required_modules' => [],
      'recommended_modules' => [],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, Klasser, skapar möjlighet att gruppera elever i klasser utöver den inkluderade grupperingen på årskurs. Det passar sig särkskilt väl i fall där man har flera klasser i samma årskurs eller flera årskurser som slås ihop till en klass i skolan. Man kan använda klass för att skapa kurser så att man inte behöver lägga till varje enskild elev i varje kurs.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_examinations',
      'label' => 'Examinationslistor',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_class', 'simple_school_reports_caregiver_login'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, Examinationslistor, skapar möjlighet att lista examinationer som elever genomfört eller ska genomföra så att man (persona eller vårdnadshavare) kan få en överblick om det är uppgifter eleven ligger efter med. Modulen lagrar eller presenterar inte resultat från examinationerna.</p><p>Om man har modulen Klasser kan man skapa bedömningsgrupper utifrån klasstillhörighet så att man inte behöver lägga till varje enskild elev i varje bedömningsgrupp.</p>',
    ];

    $map[] = [
      'module' => 'simple_school_reports_schema_ssr',
      'label' => 'SSR-schema',
      'required_modules' => [],
      'recommended_modules' => ['simple_school_reports_class', 'simple_school_reports_attendance_analyse'],
      'price' => '[[MODULE_PRICE]]',
      'annual_fee' => '[[MODULE_ANNUAL_FEE]]',
      'description' => '<p>Denna modul, SSR-schema, skapar en lätthanterlig lista över lektioner som lärare förväntas rapportera utifrån schema som skapats på kurserna i Simple School Reports. Om man har modulen närvaroanalys listas lektionerna tydligt i analysen och man kan även se vilka lektioner i vilka ämnen som inte har närvarorapporterats.</p>',
    ];

    $event = new GetHelpPagesEvent();
    $this->dispatcher->dispatch($event, GetHelpPagesEvent::EVENT_NAME);

    $formatted_map = [];
    foreach ($map as $weight => $module) {
      $module_name = $module['module'];
      $enabled = $this->moduleHandler->moduleExists($module_name);
      $module['enabled'] = $enabled;

      if (!($module_name === 'simple_school_reports_core' && $is_ssr_promo)) {
        $module['description'] = '<h3>' . $module['label'] . '</h3>' . $module['description'];
      }

      $module['module_type'] = $this->getModuleType($module_name);

      $help_pages_targets = [];
      foreach ($event->getHelpPageNids($module_name) as $nid) {
        $help_pages_targets[] = [
          'target_id' => $nid,
        ];
      }
      $module['help_pages'] = $help_pages_targets;
      $module['weight'] = $weight;

      $formatted_map[$module['module']] = $module;
    }

    return $formatted_map;
  }

  public function syncModuleInfo(bool $force = FALSE): bool {
    try {
      $map = $this->getMap();
      $current_sync_hash = sha1(json_encode($map));

      if (!$force) {
        $last_sync_hash = $this->state->get('simple_school_reports_module_info.last_sync_hash');
        if ($last_sync_hash === $current_sync_hash) {
          return TRUE;
        }
      }

      foreach ($map as $module_name  => $data) {
        $module_info = current($this->entityTypeManager->getStorage('ssr_module_info')->loadByProperties(['module' => $module_name]));
        if (empty($module_info)) {
          $module_info = $this->entityTypeManager->getStorage('ssr_module_info')->create([
            'langcode' => 'sv',
            'uid' => 1,
          ]);
        }

        $module_info->set('module', $module_name);
        $module_info->set('label', $this->t($data['label']));
        $module_info->set('module_type', $data['module_type']);
        $module_info->set('required_modules', $data['required_modules']);
        $module_info->set('recommended_modules', $data['recommended_modules']);
        $module_info->set('enabled', $data['enabled']);
        $module_info->set('help_pages', $data['help_pages']);

        if (!empty($data['help_pages'])) {
          $module_info->set('ssr_demo_link', [
            'uri' => 'https://simpleschoolreports.se/demofilmer?title=&field_module_value=' . $module_name,
            'title' => 'Se demonstration av funktioner i denna modul här',
          ]);
        }

        $module_info->set('description', [
          'value' => $this->replacePrice($data['description']),
          'format' => 'full_html',
        ]);
        $module_info->set('weight', $data['weight']);

        if (isset($data['price'])) {
          $module_info->set('price', $this->replacePrice($data['price']));
        }
        else {
          $module_info->set('price', NULL);
        }

        if (isset($data['annual_fee'])) {
          $module_info->set('annual_fee', $this->replacePrice($data['annual_fee']));
        }
        else {
          $module_info->set('annual_fee', NULL);
        }

        $module_info->save();
      }

      // Remove all module info entities that are not in the map.
      $ids_to_delete = $this->entityTypeManager->getStorage('ssr_module_info')->getQuery()
        ->accessCheck(FALSE)
        ->condition('module', array_keys($map), 'NOT IN')
        ->execute();
      if (!empty($ids_to_delete)) {
        $this->entityTypeManager->getStorage('ssr_module_info')->delete($this->entityTypeManager->getStorage('ssr_module_info')->loadMultiple($ids_to_delete));
      }

      $this->state->set('simple_school_reports_module_info.last_sync_hash', $current_sync_hash);
    } catch (\Exception $e) {
      return FALSE;
    }

    return TRUE;
  }

  protected function getModuleNameMap(): array {
    return [
      // Core modules.
      'simple_school_reports_core' => $this->t('Simple school report core'),
      'simple_school_reports_core_gr' => $this->t('Simple school report core for elementary school'),
      'simple_school_reports_core_gy' => $this->t('Simple school report core for upper secondary school'),

      // Ordinary modules.
      'simple_school_reports_absence_make_up' => $this->t('Make up time'),
      'simple_school_reports_caregiver_login' => $this->t('Caregiver login'),
      'simple_school_reports_grade_registration' => $this->t('Grade registration'),
      'simple_school_reports_geg_grade_registration' => $this->t('Review grade registration'),
      'simple_school_reports_grade_stats' => $this->t('Grade statistics'),
      'simple_school_reports_iup' => $this->t('IUP registration'),
      'simple_school_reports_reviews' => $this->t('Written reviews registration'),
      'simple_school_reports_list_templates' => $this->t('List templates'),
      'simple_school_reports_budget' => $this->t('Budget'),
      'simple_school_reports_help' => $this->t('Help'),
      'simple_school_reports_consents' => $this->t('Consents'),
      'simple_school_reports_student_di' => $this->t('Development interview for students'),
      'simple_school_reports_attendance_analyse' => $this->t('Attendance analyse'),
      'simple_school_reports_attendance_period_analyse' => $this->t('Attendance analyse for a period'),
      'simple_school_reports_leave_application' => $this->t('Leave applications'),
      'simple_school_reports_extra_adaptations' => $this->t('Extra adaptations'),
      'simple_school_reports_class' => $this->t('Classes'),
      'simple_school_reports_examinations' => $this->t('Examination lists'),
      'simple_school_reports_schema_ssr' => $this->t('SSR schema'),

      // Mini modules.
      'simple_school_reports_extens_grade_export' => $this->t('Extens export (MGBETYG)'),
      'simple_school_reports_special_diet' => $this->t('Special diet'),
      'simple_school_reports_maillog_mini' => $this->t('Mail log'),
      'simple_school_reports_email_attachments' => $this->t('Email attachments'),
      'simple_school_reports_absence_matrix' => $this->t('Absence matrix'),
    ];
  }

  public function getModules(?string $module_type = NULL): array {
    $ssr_modules = $this->getModuleNameMap();

    if (!$module_type) {
      return $ssr_modules;
    }

    $filtered_modules = [];
    foreach ($ssr_modules as $module_name => $module_label) {
      if ($this->isSsrModule($module_name, $module_type)) {
        $filtered_modules[$module_name] = $module_label;
      }
    }

    return $filtered_modules;
  }

  public function getModuleType(string $module_name): ?string {
    $ssr_module = $this->getModuleNameMap()[$module_name] ?? NULL;
    if (!$ssr_module) {
      return NULL;
    }

    return match ($module_name) {
      'simple_school_reports_core',
      'simple_school_reports_core_gr',
      'simple_school_reports_core_gy' => 'core',
      'simple_school_reports_extens_grade_export',
      'simple_school_reports_special_diet',
      'simple_school_reports_maillog_mini',
      'simple_school_reports_email_attachments',
      'simple_school_reports_absence_matrix' => 'mini_module',
      default => 'module',
    };
  }

  public function isSsrModule(string $module_name, ?string $module_type = NULL): bool {
    if (!$module_type) {
      return isset($this->getModuleNameMap()[$module_name]);
    }
    return $this->getModuleType($module_name) === $module_type;
  }

  public static function getSubscribedEvents() {
    $events['ssr_post_deploy'][] = 'onSsrPostDeploy';
    return $events;
  }

  public function onSsrPostDeploy() {
    $result = $this->syncModuleInfo();
    if ($result) {
      $this->loggerChannelFactory->get('simple_school_reports_module_info')->info('Module info sync completed successfully.');
    }
    else {
      $this->loggerChannelFactory->get('simple_school_reports_module_info')->error('Module info sync failed.');
    }
  }
}
