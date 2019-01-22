<?php

namespace Drupal\related_content;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Article service to get related article.
 */
class ArticleService {
  protected $entityTypeManager;

  /**
   * Constructs a new EntityTypeManager object.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Function to fetch articles by same categories.
   */
  public function fetchRelatedArticlesSameCategory($same_user, $nid, $user_id, $categories, $limit = 5) {
    $query = $this->entityTypeManager->getStorage('node');
    if ($same_user) {
      $query_result = $query->getQuery()
        ->condition('status', 1)
        ->condition('uid', $user_id)
        ->condition('nid', $nid, '!=')
        ->condition('field_tags', $categories, 'IN')
        ->condition('type', 'article')
        ->sort('title', 'ASC')
        ->range(0, $limit)
        ->execute();
    }
    else {
      $query_result = $query->getQuery()
        ->condition('status', 1)
        ->condition('uid', $user_id, '!=')
        ->condition('nid', $nid, '!=')
        ->condition('field_tags', $categories, 'IN')
        ->condition('type', 'article')
        ->sort('title', 'ASC')
        ->range(0, $limit)
        ->execute();
    }
    $entities = $query->loadMultiple($query_result);
    return $entities;
  }

  /**
   * Function to fetch article by Different categories.
   */
  public function fetchRelatedArticlesDifferentCategory($same_user, $nid, $user_id, $categories, $limit = 5) {
    $query = $this->entityTypeManager->getStorage('node');
    if ($same_user) {
      $query_result = $query->getQuery()
        ->condition('status', 1)
        ->condition('uid', $user_id)
        ->condition('nid', $nid, '!=')
        ->condition('field_tags', $categories, 'NOT IN')
        ->condition('type', 'article')
        ->sort('title', 'ASC')
        ->range(0, $limit)
        ->execute();
    }
    else {
      $query_result = $query->getQuery()
        ->condition('status', 1)
        ->condition('uid', $user_id, '!=')
        ->condition('nid', $nid, '!=')
        ->condition('field_tags', $categories, 'NOT IN')
        ->condition('type', 'article')
        ->sort('title', 'ASC')
        ->range(0, $limit)
        ->execute();
    }
    $entities = $query->loadMultiple($query_result);
    return $entities;
  }

}
