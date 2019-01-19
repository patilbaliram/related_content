<?php
namespace Drupal\related_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a 'Header Section' Block.
 *
 * @Block(
 *   id = "related_article",
 *   admin_label = @Translation("Related Article"),
 *   category = @Translation("Related Article")
 * )
 */

class RelatedArticle extends BlockBase {
 /**
  * Drupal\Core\Entity\Query\QueryFactory definition.
  *
  * @var Drupal\Core\Entity\Query\QueryFactory
  */
//    protected $entityQuery;
//
//    public function __construct(QueryFactory $entityQuery) {
//        $this->entityQuery = $entityQuery;
//    }
//
//    public static function create(ContainerInterface $container) {
//        return new static(
//                $container->get('entity.query')
//        );
//    }

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
       
    $query = \Drupal::entityQuery('node');
//        $group = $query->orConditionGroup() 
//        ->condition('uid', $uid) 
//        ->condition('uid', 14) ;
    $query->condition('status', 1);
    $query->condition('nid', $nid, '!=');
    $query->condition('type', 'article');
    $query->condition('field_tags', $categories_array, 'IN');
    $query->range(0, 5);
    $entity_ids = $query->execute();
      

    foreach ($entity_ids as $art) {
      $options = ['absolute' => TRUE];
      $url = \Drupal\Core\Url::fromRoute('entity.node.canonical', ['node' => $art], $options);
      $url = $url->toString();
      $article = entity_load('node', $art);
      $title = $article->get('title')->getString();
       
      $items[] = [
        '#markup' => '<a href="' . $url . '">' . $title . ' ()</a>',
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
