<?php

declare(strict_types=1);

namespace Drupal\simplify_menu\TwigExtension;

use Drupal\simplify_menu\MenuItems;

/**
 * Class MenuItemsTwigExtension.
 *
 * @package Drupal\simplify_menu
 */
class MenuItemsTwigExtension extends \Twig_Extension {

  /**
   * MenuItems definition.
   *
   * @var \Drupal\simplify_menu\MenuItems
   */
  protected $menuItems;

  /**
   * MenuItemsTwigExtension constructor.
   *
   * @param \Drupal\simplify_menu\MenuItems $menuItems
   *   The MenuItems service.
   */
  public function __construct(MenuItems $menuItems) {
    $this->menuItems = $menuItems;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new \Twig_SimpleFunction('simplify_menu',
      function ($menuId = NULL) {
        return $this->menuItems->getMenuTree($menuId);
      },
        ['is_safe' => ['html']]
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'simplify_menu';
  }

}
