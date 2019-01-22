<?php

/**
 * @file providing the service that return list of related articles.
 *
 */

namespace Drupal\related_content;

class ArticleService {

  public function fetchRelatedArticles($same_category = TRUE, $nid, $user_id = NULL, $categories, $limit = 5) {
    $query = \Drupal::entityQuery('node');
    $query->condition('status', 1);
    $query->condition('nid', $nid, '!=');
    if ($user_id !== NULL) {
      $query->condition('uid', $user_id);
    }
    $query->condition('type', 'article');
    $query->condition('field_tags', $categories, 'IN');
    $query->groupBy('uid');
    $query->sort('title', 'ASC');
    $query->range(0, $limit);
    $entity_ids = $query->execute();
    return $entity_ids;
  }

}
