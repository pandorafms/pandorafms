<?php


namespace PandoraFMS;

/**
 * View class.
 */
class View
{


    /**
     * Render view.
     *
     * @param string     $page Page load view.
     * @param array|null $data Array data if necessary for view.
     *
     * @return void
     */
    public static function render(string $page, ?array $data=null)
    {
        global $config;

        if (is_array($data) === true) {
            extract($data);
        }

        if (file_exists($config['homedir'].'/views/'.$page.'.php') === true) {
            include $config['homedir'].'/views/'.$page.'.php';
        } else {
            // TODO: XXX SHOW MESSAGE;
            echo '???';
        }
    }


}
