<?php

namespace Drupal\related_content\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\related_content\ArticleService;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Related Content' Block.
 *
 * @Block(
 *   id = "related_article",
 *   admin_label = @Translation("Related Article"),
 *   category = @Translation("Related Article")
 * )
 */
class RelatedArticle extends BlockBase implements ContainerFactoryPluginInterface {
  protected $articleService;
  protected $requestmatch;

  /**
   * Constructs new objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ArticleService $article_service, CurrentRouteMatch $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->articleService = $article_service;
    $this->routematch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('related_content.articles'),
      $container->get('current_route_match')
    );
  }

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
   * This method is created to send data to Related Content block.
   */
  private function getData() {
    $uid = 0;
    $nid = 0;
    $node = $this->routematch->getParameter('node');
    if ($node instanceof NodeInterface) {
      $nid = $node->id();
      $categories = $node->get('field_tags')->getValue();
      $uid = $node->get('uid')->getString();
    }

    $categories_array = [];
    foreach ($categories as $row) {
      $categories_array[] = $row['target_id'];
    }
    $limit = 5;
    // Criteria-1: Fetch same category article by same user.
    $entity_ids = $this->articleService->fetchRelatedArticlesSameCategory(TRUE, $nid, $uid, $categories_array, $limit);

    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Criteria-2: Fetch same category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $this->articleService->fetchRelatedArticlesSameCategory(FALSE, $nid, $uid, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }

    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Criteria-3: Fetch different category article by same user.
      $limit = $limit - $count_article;
      $new_articles = $this->articleService->fetchRelatedArticlesDifferentCategory(TRUE, $nid, $uid, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }

    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Criteria-4: Fetch different category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $this->articleService->fetchRelatedArticlesDifferentCategory(FALSE, $nid, $uid, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }

    foreach ($entity_ids as $art) {
      $url = $art->toUrl();
      $author_name = $art->getOwner()->getAccountName();
      if (!$author_name) {
        $author_name = "Anonymous";
      }

      $title = $art->getTitle();
      $items[] = [
        '#markup' => Link::fromTextAndUrl($title, $url)->toString() . " (Author: " . $author_name . ")",
        '#wrapper_attributes' => [
          'class' => [
            'wrapper__links__link',
          ],
        ],
      ];
    }

    $item_list = [
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
    return $item_list;
  }

}
