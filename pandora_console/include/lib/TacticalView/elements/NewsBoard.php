<?php
/**
 * NewsBoard element for tactical view.
 *
 * @category   General
 * @package    Pandora FMS
 * @subpackage TacticalView
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

use PandoraFMS\TacticalView\Element;

/**
 * NewsBoard, this class contain all logic for this section.
 */
class NewsBoard extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        ui_require_css_file('news');
        include_once 'general/news_dialog.php';
        $this->title = __('News Board');
    }


    /**
     * Returns the html of the latest news.
     *
     * @return string
     */
    public function getNews():string
    {
        global $config;
        $options = [];
        $options['id_user'] = $config['id_user'];
        $options['modal'] = false;
        $options['limit'] = 7;
        $news = get_news($options);

        if (!empty($news)) {
            $output = '<div id="news-board" class="new">';
            foreach ($news as $article) {
                $default = false;
                if ($article['text'] == '&amp;lt;p&#x20;style=&quot;text-align:&#x20;center;&#x20;font-size:&#x20;13px;&quot;&amp;gt;Hello,&#x20;congratulations,&#x20;if&#x20;you&apos;ve&#x20;arrived&#x20;here&#x20;you&#x20;already&#x20;have&#x20;an&#x20;operational&#x20;monitoring&#x20;console.&#x20;Remember&#x20;that&#x20;our&#x20;forums&#x20;and&#x20;online&#x20;documentation&#x20;are&#x20;available&#x20;24x7&#x20;to&#x20;get&#x20;you&#x20;out&#x20;of&#x20;any&#x20;trouble.&#x20;You&#x20;can&#x20;replace&#x20;this&#x20;message&#x20;with&#x20;a&#x20;personalized&#x20;one&#x20;at&#x20;Admin&#x20;tools&#x20;-&amp;amp;gt;&#x20;Site&#x20;news.&amp;lt;/p&amp;gt;&#x20;') {
                    $article['subject'] = __('Welcome to Pandora FMS Console');
                    $default = true;
                }

                $text_bbdd = io_safe_output($article['text']);
                $text = html_entity_decode($text_bbdd);

                $output .= '<div class="new-board">';
                $output .= '<div class="new-board-header">';
                $output .= '<span class="new-board-title">'.$article['subject'].'</span>';
                $output .= '<span class="new-board-author">'.__('By').' '.$article['author'].' '.ui_print_timestamp($article['timestamp'], true).'</span>';
                $output .= '</div>';
                $output .= '<div class="new content">';

                if ($default) {
                    $output .= '<div class="default-new">';
                    $output .= '<div class="default-image-new">';
                    $output .= '<img src="./images/welcome_image.svg" alt="img colabora con nosotros - Support">';
                    $output .= '</div><div class="default-text-new">';

                    $output .= '
                        <p>'.__('Welcome to our monitoring tool so grand,').'
                        <br>'.__('Where data insights are at your command.').'
                        <br>'.__('Sales, marketing, operations too,').'
                        <br>'.__("Customer support, we've got you.").'
                        </p>
                        
                        <p>'.__('Our interface is user-friendly,').'
                        <br>'.__("Customize your dashboard, it's easy.").'
                        <br>'.__('Set up alerts and gain insights so keen,').'
                        <br>'.__("Optimize your data, like you've never seen.").'
                        </p>
                        
                        <p>'.__('Unleash its power now, and join the pro league,').'
                        <br>'.__('Unlock the potential of your data to intrigue.').'
                        <br>'.__('Monitoring made simple, efficient and fun,').'
                        <br>'.__('Discover a whole new way to get things done.').'
                        </p>
                        
                        <p>'.__('And take control of your IT once and for all.').'</p>
                        
                        <span>'.__('You can replace this message with a personalized one at Admin tools -> Site news.').'</span>
                    ';

                    $output .= '</div></div>';
                } else {
                    $text = str_replace('<script', '&lt;script', $text);
                    $text = str_replace('</script', '&lt;/script', $text);
                    $output .= nl2br($text);
                }

                $output .= '</div></div>';
            }

            $output .= '</div>';

            return $output;
        } else {
            return '';
        }
    }


}
