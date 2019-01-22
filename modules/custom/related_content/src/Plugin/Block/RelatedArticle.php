<?php

namespace Drupal\related_content\Plugin\Block;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;

/**
 * Provides a 'Related Content' Block.
 *
 * @Block(
 *   id = "related_article",
 *   admin_label = @Translation("Related Article"),
 *   category = @Translation("Related Article")
 * )
 */
class RelatedArticle extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $data = [
      'title' => $this->t('Related Articles'),
      'articles' => $this->getData(),
    ];
    return [
      '#theme' => 'related_article_block',
      '#data' => $data,
      '#cache' => [
        'contexts' => ['url.path'],
        'max-age' => 0,
      ],
    ];
  }

  /**
   * This method is created to send data to header section block
   * 
   * @return array
   */
  private function getData() {
    $uid = 0;
    $nid = 0;
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface) {
      $nid = $node->id();
      $categories = $node->get('field_tags')->getValue();
      $uid = $node->get('uid')->getString();
    }

    $categories_array = [];
    foreach ($categories as $row) {
      $categories_array[] = $row['target_id'];
    }
    $limit = 5;
    $service = \Drupal::service('related_content.articles');
    // Fetch same category article by same user.
    $entity_ids = $service->fetchRelatedArticles(TRUE, $nid, $uid, $categories_array, $limit);
    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Fetch same category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $service->fetchRelatedArticles(TRUE, $nid, NULL, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }

    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Fetch same category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $service->fetchRelatedArticles(FALSE, $nid, $uid, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }
    //dsm('using services');
    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Fetch same category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $service->fetchRelatedArticles(FALSE, $nid, NULL, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }

    foreach ($entity_ids as $art) {
      $options = ['absolute' => TRUE];
      $url = Url::fromRoute('entity.node.canonical', ['node' => $art], $options);
      $article = entity_load('node', $art);
      $node = \Drupal::entityManager()->getStorage('node')->load($art);
      $author_user = \Drupal::entityManager()->getStorage('user')->load($node->getOwnerId());
      $author_name = $author_user->getUsername();
      $title = $article->get('title')->getString();
      $items[] = [
        '#markup' => Link::fromTextAndUrl($title . " (" . $author_name . ")", $url)->toString(),
        '#wrapper_attributes' => [
          'class' => [
            'wrapper__links__link',
          ],
        ],
      ];
    }

    $build['item_list'] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => [
        'class' => [
          'wrapper',
        ],
      ],
      '#attributes' => [
        'class' => [
          'wrapper__links',
        ],
      ],
      '#items' => $items,
    ];

    return $build['item_list'];
  }

}
