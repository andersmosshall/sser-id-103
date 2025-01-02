<?php

namespace Drupal\simple_school_reports_entities;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of meeting type entities.
 *
 * @see \Drupal\simple_school_reports_entities\Entity\SsrMeetingType
 */
class SsrMeetingTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No meeting types available. <a href=":link">Add meeting type</a>.',
      [':link' => Url::fromRoute('entity.ssr_meeting_type.add_form')->toString()]
    );

    return $build;
  }

}
