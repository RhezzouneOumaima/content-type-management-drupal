<?php
namespace Drupal\layout\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Fax' block.
 *
 * @Block(
 *   id = "footer_block",
 *   admin_label = @Translation("Footer block"),
 * )
 */
class FooterBlock extends BlockBase {
  // Override BlockPluginInterface methods here.

    /**
   * {@inheritdoc}
   */
  public function build() {
   
    
    $path = 'public://images/ed.png';
    $url = \Drupal\image\Entity\ImageStyle::load('medium')->buildUrl($path);
    return [
      '#theme' => 'footer_block',
      '#data' => ['url' => $url],
        ];
  }

}


?>