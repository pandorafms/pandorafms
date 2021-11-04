<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage TAGS
 */


/**
 * Delete a category by id.
 *
 * @param array $id Int with category id info.
 *
 * @return boolean True or false if something goes wrong.
 */
function categories_delete_category($id_category)
{
    // Change the elements of this category to "without category"
    db_process_sql_update('tagente_modulo', ['id_category' => 0], ['id_category' => $id_category]);
    db_process_sql_update('tnetwork_component', ['id_category' => 0], ['id_category' => $id_category]);
    if (enterprise_installed()) {
        db_process_sql_update('tlocal_component', ['id_category' => 0], ['id_category' => $id_category]);
        db_process_sql_update('tpolicy_modules', ['id_category' => 0], ['id_category' => $id_category]);
    }

    return db_process_sql_delete('tcategory', ['id' => $id_category]);
}


/**
 * Get tag's total count.
 *
 * @return mixed Int with the tag's count.
 */
function categories_get_category_count()
{
    return (int) db_get_value('count(*)', 'tcategory');
}


/**
 * Select all categories.
 *
 * @return mixed Array with categories.
 */
function categories_get_all_categories($mode='all')
{
    $categories = db_get_all_fields_in_table('tcategory');

    if ($categories === false) {
        $categories = [];
    }

    switch ($mode) {
        case 'all':
        return $categories;

            break;
        case 'forselect':
            $categories_select = [];
            foreach ($categories as $cat) {
                $categories_select[$cat['id']] = $cat['name'];
            }
        return $categories_select;
    }
}
