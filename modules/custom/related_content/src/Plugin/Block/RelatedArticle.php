<?php

namespace Drupal\related_content\Plugin\Block;

use Drupal\node\NodeInterface;
use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\related_content\ArticleService;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManager;

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
  protected $article_service;
  protected $requestmatch;
  protected $entity_type_manager;

  /**
   *
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ArticleService $article_service, CurrentRouteMatch $route_match, EntityTypeManager $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->article_service = $article_service;
    $this->routematch = $route_match;
    $this->entity_type_manager = $entity_manager;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('related_content.articles'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
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
   * This method is created to send data to header section block.
   *
   * @return array
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
    // Fetch same category article by same user.
    $entity_ids = $this->article_service->fetchRelatedArticlesSameCategory(TRUE, $nid, $uid, $categories_array, $limit);

    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Fetch same category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $this->article_service->fetchRelatedArticlesSameCategory(FALSE, $nid, $uid, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }

    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Fetch same category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $this->article_service->fetchRelatedArticlesDifferentCategory(TRUE, $nid, $uid, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }
    // dsm('using services');.
    $count_article = count($entity_ids);
    if ($count_article < 5) {
      // Fetch same category article by different user.
      $limit = $limit - $count_article;
      $new_articles = $this->article_service->fetchRelatedArticlesDifferentCategory(FALSE, $nid, $uid, $categories_array, $limit);
      $entity_ids = array_merge($entity_ids, $new_articles);
    }

    foreach ($entity_ids as $art) {
      $options = ['absolute' => TRUE];
      $url = Url::fromRoute('entity.node.canonical', ['node' => $art], $options);
      $node = $this->entity_type_manager->getStorage('node')->load($art);
      $author_user = $this->entity_type_manager->getStorage('user')->load($node->getOwnerId());
      $author_name = $author_user->getUsername();
      $title = $node->get('title')->getString();
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
