<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

$searchHelps = true;

$maps = false;
if ($searchHelps) {
    $keywords = io_safe_output($config['search_keywords']);

    $help_directory = $config['homedir'].'/include/help';

    $user_language = get_user_language($_SESSION['id_usuario']);
    if ($user_language === 'en_GB') {
        $user_language = 'en';
    }

    // Check the language directory help exists.
    if (is_dir($help_directory.'/'.$user_language)) {
        $helps = [];

        $help_directory = $help_directory.'/'.$user_language;

        $helps_files = scandir($help_directory);
        foreach ($helps_files as $help_file) {
            if (strstr($help_file, '.php') !== false) {
                $help_id = str_replace(['help_', '.php'], '', $help_file);

                $content = file_get_contents($help_directory.'/'.$help_file);

                preg_match('/<h1>(.*)<\/h1>/im', $content, $matchs);
                $title = null;
                if (!empty($matchs)) {
                    $title = $matchs[1];
                }



                // The name is the equal to the file
                $content = strip_tags($content);

                $count = preg_match_all('/'.$keywords.'/im', $content, $m);

                if ($count != 0) {
                    // Search in the file
                    if (!empty($title)) {
                        $helps[$title] = [
                            'id'    => $help_id,
                            'count' => $count,
                        ];
                    } else {
                        $helps[] = [
                            'id'    => $help_id,
                            'count' => $count,
                        ];
                    }
                }
            }
        }


        if (empty($helps)) {
            $helps = false;
            $totalHelps = 0;
        } else {
            $totalHelps = count($helps);
        }
    } else {
        $totalHelps = 0;
    }
}
