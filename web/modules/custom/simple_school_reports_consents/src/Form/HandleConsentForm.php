<?php

namespace Drupal\simple_school_reports_consents\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface;
use Drupal\simple_school_reports_core\Form\ConfirmWithSigningFormBase;
use Drupal\simple_school_reports_entities\SsrSigningInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a confirmation form to handle consents.
 */
class HandleConsentForm extends ConfirmWithSigningFormBase {

  /**
   * @var \Drupal\simple_school_reports_consents\Service\ConsentsServiceServiceInterface
   */
  protected $consentService;

  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->consentService = $container->get('simple_school_reports_consents.consent_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'handle_consent_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Handle consent');
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
  protected function beforeSigningBuildForm($form, $form_state, NodeInterface|string $node = NULL, NodeInterface|string $user = NULL): array {
    if (!$node instanceof NodeInterface) {
      if (is_numeric($node)) {
        $node = $this->entityTypeManager->getStorage('node')->load($node);
      }
    }
    if (!$user instanceof UserInterface) {
      if (is_numeric($user)) {
        $user = $this->entityTypeManager->getStorage('user')->load($user);
      }
    }
    if (!$node instanceof NodeInterface || !$user instanceof UserInterface) {
      throw new AccessDeniedHttpException();
    }
    if ($node->bundle() !== 'consent') {
      throw new AccessDeniedHttpException();
    }
    $form['consent_id'] = [
      '#type' => 'value',
      '#value' => $node->id(),
    ];

    $form['target_uid'] = [
      '#type' => 'value',
      '#value' => $user->id(),
    ];

    $form['#title'] = $this->t('Handle consent for @name', ['@name' => $user->getDisplayName()]);

    $view_builder = $this->entityTypeManager->getViewBuilder('node');

    $form['info_wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Consent'),
      '#open' => TRUE,
    ];
    $form['info_wrapper']['info'] = $view_builder->view($node);

    $consent_status = $this->consentService->getConsentStatus($node->id(), $user->id());

    if (empty($consent_status) || (count($consent_status) === 1 && isset($consent_status[0]))) {
      throw new AccessDeniedHttpException();
    }

    $form['my_answer_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('My answer'),
    ];

    $uid = $this->currentUser()->id();

    if (isset($consent_status[$uid])) {
      $this->makeAnswerFields($form, $uid, $consent_status[$uid], $this->consentService->allowConsentHandling($node->id(), $user->id(), $uid));
      unset($consent_status[$uid]);
    }
    else {
      $form['my_answer'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Not applicable'),
      ];
    }

    if (!empty($consent_status)) {
      $form['other_answers_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Other answers'),
      ];

      foreach ($consent_status as $uid => $status) {
        $this->makeAnswerFields($form, $uid, $consent_status[$uid], $this->consentService->allowConsentHandling($node->id(), $user->id(), $uid));
      }
    }

    return $form;
  }

  protected function makeAnswerFields(array &$form, int $uid, array $status, bool $allow_answer) {
    $is_own_answer = $uid == $this->currentUser->id();

    $answer_options = simple_school_reports_entities_answer_types();

    $form['status_' . $uid] = [
      '#type' => 'html_tag',
      '#tag' => 'strong',
      '#value' => $status['name'] . ': ' . $status['status'],
    ];

    if (!$allow_answer) {
      $form['give_answer_' . $uid] = [
        '#type' => 'value',
        '#value' => FALSE,
      ];
      $form['hr_' . $uid] = [
        '#type' => 'html_tag',
        '#tag' => 'hr',
      ];
      return;
    }

    $answer_id = $status['answer_id'] ?? NULL;

    $optional_give_answer = TRUE;
    if ($is_own_answer && !$answer_id) {
      $optional_give_answer = FALSE;
    }

    if ($optional_give_answer) {
      $form['answer_given_wrapper_' . $uid] = [
        '#type' => 'container',
        '#states' => [
          'visible' => [
            ':input[name="give_answer_' . $uid . '"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
      ];

      $description = $answer_id
        ? $this->t('Answer has been given, check this box if you want to change it.')
        : $this->t('No answer has been given, check this box if you want to give answer.');

      $form['answer_given_wrapper_' . $uid]['give_answer_' . $uid] = [
        '#title' => $this->t('Change'),
        '#description' => $description,
        '#type' => 'checkbox',
        '#default_value' => FALSE,
      ];
    }
    else {
      $form['give_answer_' . $uid] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }

    $value = isset($answer_options[$status['value']]) ? $status['value'] : NULL;

    $form['answer_' . $uid] = [
      '#type' => 'select',
      '#title' => $this->t('Answer'),
      '#options' => $answer_options,
      '#default_value' => $value,
      '#empty_option' => $this->t('Not answered'),
    ];

    $form['answer_date_' . $uid] = [
      '#type' => 'date',
      '#title' => $this->t('Date'),
      '#default_value' => (new \DateTime('now'))->format('Y-m-d'),
    ];

    if (!$is_own_answer) {
      $form['confirm_' . $uid] = [
        '#type' => 'checkbox',
        '#title' => $this->t('I confirm that I have duly obtained the consent answer'),
        '#default_value' => FALSE,
      ];
    }

    if ($optional_give_answer) {
      $states = [
        'visible' => [
          ':input[name="give_answer_' . $uid . '"]' => [
            'checked' => TRUE,
          ],
        ],
        'required' => [
          ':input[name="give_answer_' . $uid . '"]' => [
            'checked' => TRUE,
          ],
        ],
      ];

      $form['answer_' . $uid]['#states'] = $states;
      $form['answer_date_' . $uid]['#states'] = $states;

      if (!$is_own_answer) {
        $form['confirm_' . $uid]['#states'] = $states;
      }
    }

    $form['hr_' . $uid] = [
      '#type' => 'html_tag',
      '#tag' => 'hr',
    ];
  }

  function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ($form_state->get('step') === 'signing') {
      return;
    }

    $consent_id = $form_state->getValue('consent_id');
    $target_uid = $form_state->getValue('target_uid');

    if (!$consent_id || !$target_uid) {
      $form_state->setError($form, $this->t('Something went wrong.'));
    }

    $consent_status = $this->consentService->getConsentStatus($consent_id, $target_uid);
    foreach ($consent_status as $uid => $status) {
      if ($form_state->getValue('give_answer_' . $uid)) {
        $is_own_answer = $uid == $this->currentUser->id();

        $required_fields = [
          'answer_' . $uid,
          'answer_date_' . $uid
        ];

        if (!$is_own_answer) {
          $required_fields[] = 'confirm_' . $uid;
        }

        foreach ($required_fields as $field) {
          if (!$form_state->getValue($field) && $form_state->getValue($field) != 0) {
            $form_state->setErrorByName($field, $this->t('Required field'));
          }
        }

        $now = new \DateTime('now');
        $now->setTime(23, 59, 59);
        if ($date = $form_state->getValue('answer_date_' . $uid)) {
          $check_date = new \DateTime($date . ' 00:00:00');
          if ($check_date->getTimestamp() > $now->getTimestamp()) {
            $form_state->setErrorByName('answer_date_' . $uid, $this->t('You are not allowed to register answers in the future'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function makeSignTemplate(array $safe_form_values, FormStateInterface $form_state): string {
    $sign_template = $this->t('I hereby confirm the following:');
    $answer_values = [];

    $consent_id = $safe_form_values['consent_id'];
    $target_uid = $safe_form_values['target_uid'];

    $has_changes = FALSE;
    $answer_options = simple_school_reports_entities_answer_types();

    $consent_status = $this->consentService->getConsentStatus($consent_id, $target_uid);
    foreach ($consent_status as $uid => $status) {
      if ($safe_form_values['give_answer_' . $uid]) {
        if ($safe_form_values['answer_' . $uid] !== $status['value']) {
          $answer = $answer_options[$safe_form_values['answer_' . $uid]] ?? NULL;
          if (!$answer) {
            continue;
          }
          $has_changes = TRUE;
          $sign_template .= '<br>* ' . $this->t('I register answer: @answer for @name from @from', ['@answer' => $answer, '@name' => $status['name'], '@from' => $safe_form_values['answer_date_' . $uid]]);

          $changed = new \DateTime($safe_form_values['answer_date_' . $uid] . ' 12:00:00');

          $answer_values[] = [
            'target_uid' => ['target_id' => $target_uid],
            'consent' => ['target_id' => $consent_id],
            'changed' => $changed->getTimestamp(),
            'answer' => $safe_form_values['answer_' . $uid],
            'uid' => ['target_id' => $uid],
            'handler_uid' => ['target_id' => $this->currentUser->id()],
          ];
        }
      }
    }

    $form_state->set('consent_answer_values', $answer_values);

    return $has_changes ? $sign_template : '';
  }

  /**
   * {@inheritdoc}
   */
  protected function afterSigningSubmit(array &$form, FormStateInterface $form_state, array $safe_form_values, SsrSigningInterface $signing) {
    $answer_values = $form_state->get('consent_answer_values', []);

    foreach ($answer_values as $values) {
      $answer = $this->entityTypeManager->getStorage('ssr_consent_answer')->create($values);
      $answer->set('signing', $signing);
      $answer->save();
    }

    $this->messenger()->addStatus($this->t('Created @count answers', ['@count' => count($answer_values)]));
  }

  public function accessConsentForm(mixed $node, mixed $user, ?AccountInterface $account = NULL) {
    if (!$node instanceof NodeInterface) {
      if (is_numeric($node)) {
        $node = $this->entityTypeManager->getStorage('node')->load($node);
      }

    }
    if (!$user instanceof UserInterface) {
      if (is_numeric($user)) {
        $user = $this->entityTypeManager->getStorage('user')->load($user);
      }
    }

    if (!$node instanceof NodeInterface || !$user instanceof UserInterface) {
      return AccessResult::forbidden()->addCacheContexts(['route'])->cachePerUser();
    }

    if ($node->get('field_locked')->value) {
      return AccessResult::forbidden()->addCacheableDependency($node)->addCacheContexts(['route'])->cachePerUser();
    }

    if (!$account) {
      $account = $this->currentUser();
    }

    if ($node->bundle() !== 'consent' || $account->id() != $this->currentUser()->id()) {
      return AccessResult::forbidden()->addCacheContexts(['route'])->cachePerUser();
    }

    $consent = $node->id();
    $target_uid = $user->id();

    $access = AccessResult::allowedIf($this->consentService->allowConsentHandling($consent, $target_uid));

    $access->addCacheContexts(['route']);
    $access->cachePerUser();
    $access->addCacheableDependency($node);

    return $access;
  }

}
