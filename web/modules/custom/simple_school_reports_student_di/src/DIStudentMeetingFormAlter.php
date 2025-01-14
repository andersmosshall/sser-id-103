<?php

namespace Drupal\simple_school_reports_student_di;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class DIStudentMeetingFormAlter {

  public static function handleStudentChange($form, FormStateInterface $form_state) {
    if (!empty($form['field_student']['widget'])) {
      $form['field_student']['widget']['#ajax'] = [
        'callback' => [static::class, 'handleStudentChangeAjax'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'attending-element-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Loading...'),
        ],
      ];

      if (!empty($form['field_teachers']['widget'])) {
        foreach (Element::children($form['field_teachers']['widget']) as $key) {
          if (isset($form['field_teachers']['widget'][$key]['target_id'])) {
            if (!empty($form['field_teachers']['widget'][$key]['target_id']['#type']) && $form['field_teachers']['widget'][$key]['target_id']['#type'] === 'entity_autocomplete') {
              $form['field_teachers']['widget'][$key]['target_id']['#ajax'] = [
                'callback' => [static::class, 'handleStudentChangeAjax'],
                'disable-refocus' => FALSE,
                'event' => 'change',
                'wrapper' => 'attending-element-wrapper',
                'progress' => [
                  'type' => 'throbber',
                  'message' => t('Loading...'),
                ],
              ];
            }
          }
        }
      }

      $form['attending']['#prefix'] = '<div id="attending-element-wrapper">';
      $form['attending']['#suffix'] = '</div>';

      $form['validate_availability'] = [
        '#type' => 'checkbox',
        '#title' => t('Validate availability'),
        '#description' => t('Validate that selected attendances are available for this meeting date.'),
        '#default_value' => TRUE,
        '#weight' => 100,
      ];

      $form['actions']['#weight'] = 200;
      $form['#validate'][] = [static::class, 'validateDates'];
      $form['#validate'][] = [static::class, 'validateAvailability'];
    }

    return $form;
  }

  public static function getFromToDates(FormStateInterface $form_state): array {
    $from_value = $form_state->getValue('from', []);
    $from_value = !empty($from_value[0]['value']) ? $from_value[0]['value'] : NULL;


    $to_value = $form_state->getValue('to', []);
    $to_value = !empty($to_value[0]['value']) ? $to_value[0]['value'] : NULL;


    return [
      $from_value,
      $to_value,
    ];

  }

  public static function validateDates($form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement() && $form_state->getTriggeringElement()['#type'] === 'submit') {
      [$from, $to] = self::getFromToDates($form_state);

      if (!$form || !$to) {
        return;
      }

      if ($from > $to) {
        $form_state->setErrorByName('from', t('From date must be before to date.'));
        $form_state->setErrorByName('to', t('To date must be after from date.'));
      }
      elseif ($to->getTimestamp() - $from->getTimestamp() > 3600 * 8) {
        $form_state->setErrorByName('to', t('The meeting is too long.'));
      }
    }
  }

  public static function validateAvailability($form, FormStateInterface $form_state) {
    if ($form_state->getTriggeringElement() && $form_state->getTriggeringElement()['#type'] === 'submit' && $form_state->getValue('validate_availability')) {
      if ($form_state->getErrors()) {
        return;
      }

      $this_meeting = self::getMeeting($form_state);
      if (!$this_meeting) {
        return;
      }


      [$from, $to] = self::getFromToDates($form_state);
      if (!$from || !$to) {
        return;
      }

      $attendances = array_column($form_state->getValue('attending', []), 'target_id');

      $ui = $form_state->getUserInput();
      if (!empty($ui['attending'])) {
        $attendances = [];
        foreach ($ui['attending'] as $value) {
          if ($value) {
            $attendances[] = $value;
          }
        }
      }

      if (empty($attendances)) {
        return;
      }

      // Validate availability
      $start_time = $from->getTimestamp();
      $end_time = $to->getTimestamp();

      $double_booked = [];

      $meeting_ids = \Drupal::entityTypeManager()->getStorage('ssr_meeting')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'student_di')
        ->condition('to', $start_time, '>')
        ->condition('from', $end_time, '<')
        ->condition('id', $this_meeting->id(), '<>')
        ->condition('attending', $attendances, 'IN')
        ->execute();

      if (empty($meeting_ids)) {
        return;
      }

      $meetings = \Drupal::entityTypeManager()->getStorage('ssr_meeting')->loadMultiple($meeting_ids);

      foreach ($meetings as $meeting) {
        $meeting_start = $meeting->get('from')->value;
        $meeting_end = $meeting->get('to')->value;

        // Double check to be sure. This should not be possible.
        if (!($meeting_end <= $start_time || $meeting_start >= $end_time)) {
          foreach ($meeting->get('attending')->referencedEntities() as $attending) {
            if (!in_array($attending->id(), $attendances)) {
              continue;
            }
            $double_booked[$attending->id()] = $attending->getDisplayName();
          }
        }
      }

      if (!empty($double_booked)) {
        $form_state->setError($form, t('The following people are not available for the meeting: @people', ['@people' => implode(', ', $double_booked)]));
      }
    }
  }

  public static function getMeeting(FormStateInterface $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof EntityFormInterface) {
      return $form_object->getEntity();
    }
    return NULL;
  }

  public static function handleStudentChangeAjax($form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#attending-element-wrapper', $form['attending']));
    return $response;
  }

}
