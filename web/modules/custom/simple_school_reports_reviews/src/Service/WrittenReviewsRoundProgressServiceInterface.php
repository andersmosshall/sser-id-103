<?php

namespace Drupal\simple_school_reports_reviews\Service;

/**
 * Provides an interface defining WrittenReviewsRoundProgressService.
 */
interface WrittenReviewsRoundProgressServiceInterface {

  /**
   * @param string $written_reviews_round_nid
   *
   * @return string
   */
  public function getProgress(string $written_reviews_round_nid) : string;

  /**
   * @param string $written_reviews_round_nid
   * @param string $student_uid
   *
   * @return string|null
   */
  public function getWrittenReviewsNid(string $written_reviews_round_nid, string $student_uid) : ?string;

}
