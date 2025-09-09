<?php

declare(strict_types=1);

namespace Drupal\simple_school_reports_grade_support;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of course to grade type entities.
 *
 * @see \Drupal\simple_school_reports_grade_support\Entity\GradeRegistrationCourseType
 */
final class GradeRegistrationCourseTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No course to grade types available. <a href=":link">Add course to grade type</a>.',
      [':link' => Url::fromRoute('entity.ssr_grade_reg_course_type.add_form')->toString()],
    );

    return $build;
  }

}
