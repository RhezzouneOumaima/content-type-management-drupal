<?php
namespace Drupal\layout\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Fax' block.
 *
 * @Block(
 *   id = "header_block",
 *   admin_label = @Translation("Header block"),
 * )
 */
class HeaderBlock extends BlockBase {
  // Override BlockPluginInterface methods here.

    /**
   * {@inheritdoc}
   */
  public function build() {
   

    $url = \Drupal::theme()->getActiveTheme()->getLogo();
    
    //dump($url);
    return [
      '#theme' => 'header_block',
      '#data' => ['url' => $url],
        ];
  }

}


?>