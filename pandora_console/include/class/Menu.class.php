<?php

require_once 'include/class/MenuItem.class.php';

/**
 * Global Menu class.
 */
class Menu
{
    // Check ACL
    /*
        if ((bool) check_acl($config['id_user'], 0, '$item->ACL_el_que_sea') === true) {
        haz cosas aqui
        }

    */

    public function __construct(
        private string $name,
        private array $items=[],
        private array $menu=[]
    ) {

    }


    /**
     * Create Item function.
     *
     * @param MenuItem $menuItem Item for print.
     *
     * @return array.
     */
    public function generateItem(MenuItem $menuItem)
    {
        // Start with empty element.
        $item = [];
        if ($menuItem->canBeDisplayed() === true) {
            // Handle of information.
            if (empty($menuItem->getUrl()) === false) {
                $urlPath = $menuItem->getUrl();
            } else {
                $urlPath = ui_get_full_url(
                    $menuItem->getSec().'/'.$menuItem->getSec2().'/'.$menuItem->getParameters()
                );
            }

            // Creation of the line.
            $item[] = '<li>';

            // Create the link if is neccesary.
            if (empty($urlPath) === false) {
                $item[] = sprintf(
                    '<a href="%s">%s</a>',
                    $urlPath,
                    $menuItem->getText()
                );
            }

            // Check if this item has submenu. If is the case, create it.
            if ($menuItem->hasSubmenu() === true) {
                $item[] = '<ul>';
                $itemSubMenu = $menuItem->getSubmenu();
                foreach ($itemSubMenu as $subMenu) {
                    $item[] = $this->generateItem($subMenu);
                }

                $item[] = '</ul>';
            }

            $item[] = '</li>';
        }

        return $item;
    }


    /**
     * Generate the menu.
     *
     * @return void.
     */
    public function generateMenu()
    {
        $output = [];
            /*
                Estructura
                <ul>
                    <li>level0.item1</li>
                    <li>level0.item2</li>
                        <ul>
                            <li>level1.item1</li>
                            <li>level1.item2</li>
                        </ul>
                    <li>level0.item3</li>
                </ul>
            */
        $output[] = '<ul>';
        foreach ($this->items as $menuItem) {
            // If the item must be displayed.
            $this->generateItem($menuItem);
        }

        $output[] = '</ul>';

        $this->menu[] = $output;

    }


    /**
     * Prints the menu.
     *
     * @return void.
     */
    public function printMenu()
    {
        if (empty($this->menu) === false) {
            foreach ($this->menu as $element) {
                echo $element."\n";
            }
        }
    }


}
