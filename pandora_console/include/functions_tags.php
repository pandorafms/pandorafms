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
  * Find a tag searching by tag's or description name.
  *
  * @param string  $tag_name_description Name or description of the tag that it's currently searched.
  * @param array   $filter               Array with pagination parameters.
  * @param boolean $only_names           Whether to return only names or all fields.
  * @param boolean $count                To return the number of items.
  *
  * @return mixed Returns an array with the tag selected by name or false.
  * When the count parameter is enabled, returns an integer.
  */
function tags_search_tag($tag_name_description=false, $filter=false, $only_names=false, $count=false)
{
    global $config;

    if ($filter === false) {
        $filter = [];
    }

    if (isset($filter['name'])) {
        if (empty($tag_name_description)) {
            $tag_name_description = $filter['name'];
        }

        unset($filter['name']);
    }

    if ($tag_name_description) {
        switch ($config['dbtype']) {
            case 'mysql':
                $filter[] = '((name LIKE "%'.$tag_name_description.'%") OR 
						(description LIKE "%'.$tag_name_description.'%"))';
            break;

            case 'postgresql':
                $filter[] = '((name LIKE \'%'.$tag_name_description.'%\') OR
						(description LIKE \'%'.$tag_name_description.'%\'))';
            break;

            case 'oracle':
                $filter[] = '(UPPER(name) LIKE UPPER (\'%'.$tag_name_description.'%\') OR
						UPPER(dbms_lob.substr(description, 4000, 1)) LIKE UPPER (\'%'.$tag_name_description.'%\'))';
            break;

            default:
                // Default.
            break;
        }
    }

    // Default order.
    set_unless_defined($filter['order'], 'name');

    $fields = '*';
    if ($only_names) {
        $fields = [
            'id_tag',
            'name',
        ];
    }

    // It will return the count.
    if ($count) {
        unset($filter['order']);
        unset($filter['limit']);
        unset($filter['offset']);

        if (!empty($filter)) {
            return (int) db_get_value_filter('COUNT(id_tag)', 'ttag', $filter);
        } else {
            return (int) db_get_value('COUNT(id_tag)', 'ttag');
        }
    }

    $result = db_get_all_rows_filter('ttag', $filter, $fields);

    if ($result === false) {
        $result = [];
    }

    if ($only_names) {
        $result_tags = [];
        foreach ($result as $tag) {
            $result_tags[$tag['id_tag']] = $tag['name'];
        }

        $result = $result_tags;
    }

    return $result;
}


/**
 * Create a new tag.
 *
 * @param array $values Array with all values to insert.
 *
 * @return mixed Tag id or false.
 */
function tags_create_tag($values)
{
    if (empty($values)) {
        return false;
    }

    // No create tag if the tag exists.
    if (tags_get_id($values['name'])) {
        return false;
    }

    return db_process_sql_insert('ttag', $values);
}


/**
 * Search tag by id.
 *
 * @param array $id Int with tag id info.
 *
 * @return mixed Array with the seleted tag or false.
 */
function tags_search_tag_id($id)
{
    return db_get_row('ttag', 'id_tag', $id);
}


/**
 * Get tag name.
 *
 * @param array $id Int with tag id info.
 *
 * @return mixed String with tag name or false.
 */
function tags_get_name($id)
{
    return db_get_value_filter('name', 'ttag', ['id_tag' => $id]);
}


/**
 * Get tag id given the tag name.
 *
 * @param string Tag name.
 *
 * @return integer Tag id.
 */
function tags_get_id($name)
{
    return db_get_value_filter('id_tag', 'ttag', ['name' => $name]);
}


/**
 * Get tag description.
 *
 * @param array $id Int with tag id info.
 *
 * @return mixed String with tag description or false.
 */
function tags_get_description($id)
{
    return db_get_value_filter('description', 'ttag', ['id_tag' => $id]);
}


/**
 * Get tag url.
 *
 * @param array $id Int with tag id info.
 *
 * @return mixed String with tag url or false.
 */
function tags_get_url($id)
{
    return db_get_value_filter('description', 'ttag', ['id_tag' => $id]);
}


/**
 * Get tag's module count.
 *
 * @param array $id Int with tag id info.
 *
 * @return integer Tag's count.
 */
function tags_get_modules_count($id)
{
    $num_modules = tags_get_local_modules_count($id);
    $num_policy_modules = tags_get_policy_modules_count($id);

    return ($num_modules + $num_policy_modules);
}


/**
 * Get tag's local module count.
 *
 * @param array $id Int with tag id info.
 *
 * @return integer Local module tag's count.
 */
function tags_get_local_modules_count($id)
{
    $field = 'COUNT(DISTINCT(tagente_modulo.id_agente_modulo))';
    $filter = ['id_tag' => $id];
    $num_modules = (int) db_get_value_filter(
        $field,
        'ttag_module INNER JOIN tagente_modulo ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo',
        $filter
    );
    return $num_modules;
}


/**
 * Get module tag's count.
 *
 * @param array $id Int with agent module id info.
 *
 * @return integer Module tag's count.
 */
function tags_get_modules_tag_count($id)
{
    $field = 'COUNT(id_agente_modulo)';
    $filter = ['id_agente_modulo' => $id];

    $num_modules = (int) db_get_value_filter($field, 'ttag_module', $filter);

    return $num_modules;
}


/**
 * Get tag's policy module count.
 *
 * @param array $id Int with tag id info.
 *
 * @return integer Policy module tag's count.
 */
function tags_get_policy_modules_count($id)
{
    $field = 'COUNT(id_tag)';
    $filter = ['id_tag' => $id];

    $num_policy_modules = (int) db_get_value_filter($field, 'ttag_policy_module', $filter);

    return $num_policy_modules;
}


/**
 * Updates a tag by id.
 *
 * @param array  $id    Int with tag id info.
 * @param string $where Where clause to update record.
 *
 * @return boolean True or false if something goes wrong.
 */
function tags_update_tag($values, $where)
{
    return db_process_sql_update('ttag', $values, $where);
}


/**
 * Delete a tag by id.
 *
 * @param array $id Int with tag id info.
 *
 * @return boolean True or false if something goes wrong.
 */
function tags_delete_tag($id_tag)
{
    $errn = 0;

    $result_tag = db_process_delete_temp('ttag', 'id_tag', $id_tag);
    if ($result_tag === false) {
        $errn++;
    }

    $result_module = db_process_delete_temp('ttag_module', 'id_tag', $id_tag);
    if ($result_module === false) {
        $errn++;
    }

    $result_policy = db_process_delete_temp('ttag_policy_module', 'id_tag', $id_tag);
    if ($result_policy === false) {
        $errn++;
    }

    if ($errn == 0) {
        return true;
    } else {
        return false;
    }

}


function tags_remove_tag($id_tag, $id_module)
{
    $result = (bool) db_process_sql_delete(
        'ttag_module',
        [
            'id_tag'           => $id_tag,
            'id_agente_modulo' => $id_module,
        ]
    );

    return $result;
}


/**
 * Get tag's total count.
 *
 * @return mixed Int with the tag's count.
 */
function tags_get_tag_count($filter=false)
{
    $tag_name = false;
    if (isset($filter['name'])) {
        $tag_name = $filter['name'];
        unset($filter['name']);
    }

    return tags_search_tag($tag_name, $filter, false, true);
}


/**
 * Inserts tag's array of a module.
 *
 * @param integer $id_agent_module Module's id.
 * @param array   $tags            Array with tags to associate to the module.
 *
 * @return boolean True or false if something goes wrong.
 */
function tags_insert_module_tag($id_agent_module, $tags)
{
    $errn = 0;

    $values = [];

    if ($tags == false) {
        $tags = [];
    }

    foreach ($tags as $tag) {
        // Protect against default insert.
        if (empty($tag)) {
            continue;
        }

        $values['id_tag'] = $tag;
        $values['id_agente_modulo'] = $id_agent_module;
        $result_tag = db_process_sql_insert('ttag_module', $values);
        if ($result_tag === false) {
            $errn++;
        }
    }

    return $errn;
}


/**
 * Inserts tag's array of a policy module.
 *
 * @param integer $id_agent_module Policy module's id.
 * @param array   $tags            Array with tags to associate to the module.
 *
 * @return boolean True or false if something goes wrong.
 */
function tags_insert_policy_module_tag($id_agent_module, $tags)
{
    $errn = 0;

    $values = [];
    foreach ($tags as $tag) {
        // Protect against default insert.
        if (empty($tag)) {
            continue;
        }

        $values['id_tag'] = $tag;
        $values['id_policy_module'] = $id_agent_module;
        $result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
        if ($result_tag === false) {
            $errn++;
        }
    }

    if ($errn > 0) {
        return false;
    } else {
        return true;
    }
}


/**
 * Updates tag's array of a module.
 *
 * @param integer $id_agent_module Module's id.
 * @param array   $tags            Array with tags to associate to the module.
 * @param boolean $autocommit      Whether to do automatical commit or not.
 *
 * @return boolean True or false if something goes wrong.
 */
function tags_update_module_tag(
    $id_agent_module,
    $tags,
    $autocommit=false,
    $update_policy_tags=true
) {
    $errn = 0;

    if (empty($tags)) {
        $tags = [];
    }

    if ($update_policy_tags) {
        // First delete module tag entries.
        $result_tag = db_process_sql_delete(
            'ttag_module',
            ['id_agente_modulo' => $id_agent_module]
        );
    } else {
        $result_tag = db_process_sql_delete(
            'ttag_module',
            [
                'id_agente_modulo' => $id_agent_module,
                'id_policy_module' => '0',
            ]
        );
    }

    $values = [];
    foreach ($tags as $tag) {
        // Protect against default insert.
        if (empty($tag)) {
            continue;
        }

        $values['id_tag'] = $tag;
        $values['id_agente_modulo'] = $id_agent_module;
        $result_tag = db_process_sql_insert('ttag_module', $values, false);
        if ($result_tag === false) {
            $errn++;
        }
    }

}


/**
 * Updates tag's array of a policy module.
 *
 * @param integer $id_policy_module Policy module's id.
 * @param array   $tags             Array with tags to associate to the module.
 * @param boolean $autocommit       Whether to do automatical commit or not.
 *
 * @return boolean True or false if something goes wrong.
 */
function tags_update_policy_module_tag($id_policy_module, $tags, $autocommit=false)
{
    $errn = 0;

    if (empty($tags)) {
        $tags = [];
    }

    // First delete module tag entries
    $result_tag = db_process_sql_delete('ttag_policy_module', ['id_policy_module' => $id_policy_module]);

    $values = [];
    foreach ($tags as $tag) {
        // Protect against default insert.
        if (empty($tag)) {
            continue;
        }

        $values['id_tag'] = $tag;
        $values['id_policy_module'] = $id_policy_module;
        $result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
        if ($result_tag === false) {
            $errn++;
        }
    }

    if ($errn > 0) {
        return false;
    } else {
        return true;
    }
}


/**
 * Select all tags of a module.
 *
 * @param integer $id_agent_module Module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_module_tags($id, $policy=false)
{
    if (empty($id)) {
        return false;
    }

    if ($policy) {
        $tags = db_get_all_rows_filter(
            'ttag_policy_module',
            ['id_policy_module' => $id],
            false
        );
    } else {
        $tags = db_get_all_rows_filter(
            'ttag_module',
            ['id_agente_modulo' => $id],
            false
        );
    }

    if ($tags === false) {
        return [];
    }

    $return = [];
    foreach ($tags as $tag) {
        $return[] = $tag['id_tag'];
    }

    return $return;
}


/**
 * Select all tags of a policy module.
 *
 * @param integer $id_policy_module Policy module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_policy_module_tags($id_policy_module)
{
    if (empty($id_policy_module)) {
        return false;
    }

    $tags = db_get_all_rows_filter(
        'ttag_policy_module',
        ['id_policy_module' => $id_policy_module],
        false
    );

    if ($tags === false) {
        return false;
    }

    $return = [];
    foreach ($tags as $tag) {
        $return[] = $tag['id_tag'];
    }

    return $return;
}


/**
 * Select all tags.
 *
 * @return mixed Array with tags.
 */
function tags_get_all_tags($return_url=false)
{
    $tags = db_get_all_fields_in_table('ttag', 'name', '', 'name');

    if ($tags === false) {
        return false;
    }

    $return = [];
    foreach ($tags as $tag) {
        $return[$tag['id_tag']] = $tag['name'];
        if ($return_url) {
            $return[$tag['id_tag']] .= ' '.$tag['url'];
        }
    }

    return $return;
}


/**
 * Get the tags required
 *
 * @return mixed Array with tags.
 */
function tags_get_tags($ids)
{
    $all_tags = tags_get_all_tags(true);

    $tags = [];
    foreach ($ids as $id) {
        if (isset($all_tags[$id])) {
            $tags[$id] = $all_tags[$id];
        }
    }

    return $tags;
}


function tags_agent_has_tag($id_agent, $id_tag)
{
    $exists = db_get_value_sql(
        '
		SELECT COUNT(*)
		FROM ttag_module
		WHERE id_agente_modulo IN (
			SELECT id_agente_modulo
			FROM tagente_modulo
			WHERE id_agente = '.$id_agent.')
			AND id_tag = '.$id_tag
    );

    if (empty($exists)) {
        return false;
    }

    return (bool) $exists;
}


function tags_get_agents($id_tag, $id_policy_module=0)
{
    $agents = db_get_all_rows_sql(
        '
		SELECT id_agente
		FROM tagente
		WHERE id_agente IN (
			SELECT t1.id_agente
			FROM tagente_modulo t1
			WHERE t1.id_agente_modulo IN (
				SELECT t2.id_agente_modulo
				FROM ttag_module t2
				WHERE id_tag = '.$id_tag.'
					AND id_policy_module = '.$id_policy_module.'))'
    );

    if (empty($agents)) {
        return [];
    }

    $temp = [];
    foreach ($agents as $agent) {
        $temp[] = $agent['id_agente'];
    }

    $agents = $temp;

    return $agents;
}


/**
 * Give format to tags when go concatened with url.
 *
 * @param string name of tags serialized
 * @param bool flag to return the url or not
 *
 * @return string Tags with url format
 */
function tags_get_tags_formatted($tags_array, $get_url=true)
{
    if (!is_array($tags_array)) {
        $tags_array = explode(',', $tags_array);
    }

    $tags = [];
    foreach ($tags_array as $t) {
        $tag_url = explode(' ', trim($t));
        $tag = io_safe_output($tag_url[0]);
        if (isset($tag_url[1]) && $tag_url[1] != '' && $get_url) {
            $title = $tag_url[1];
            // $link = '<a href="'.$tag_url[1].'" target="_blank">'.html_print_image('images/zoom.png',true, array('alt' => $title, 'title' => $title)).'</a>';
            $link = '<a href="javascript: openURLTagWindow(\''.$tag_url[1].'\');">'.html_print_image('images/zoom.png', true, ['title' => __('Click here to open a popup window with URL tag')]).'</a>';
        } else {
            $link = '';
        }

        $tags[] = $tag.$link;
    }

    $tags = implode(',', $tags);

    $tags = str_replace(',', ' , ', $tags);

    return $tags;
}


/**
 * Get the tags (more restrictive) of an access flag in a group
 *
 * @param string id of the user
 * @param string id of the group
 * @param string access flag (AR,AW...)
 * @param string return_mode
 *             - 'data' for return array with groups and tags
 *             - 'module_condition' for return string with sql condition for tagente_module
 *             - 'event_condition' for return string with sql condition for tevento
 *
 * @return mixed/string Tag ids
 */


function tags_get_acl_tags(
    $id_user,
    $id_group,
    $access='AR',
    $return_mode='module_condition',
    $query_prefix='',
    $query_table='',
    $meta=false,
    $childrens_ids=[],
    $force_group_and_tag=false,
    $id_grupo_table_pretag='',
    $alt_id_grupo_table_pretag='',
    $search_secondary_group=true
) {
    global $config;

    if ($id_user == false) {
        $id_user = $config['id_user'];
    }

    if (is_user_admin($id_user) && empty($childrens_ids)) {
        switch ($return_mode) {
            case 'data':
            return [];

                break;
            case 'event_condition':
            case 'module_condition':
            return '';

                break;
            default:
                // Default.
            break;
        }
    }

    if ($id_group == 0) {
        // Don't filter.
        $id_group = [];
    } else if (empty($id_group)) {
        return ERR_WRONG_PARAMETERS;
    } else if (!is_array($id_group)) {
        $id_group = [$id_group];
    }

    $acl_column = get_acl_column($access);
    if (empty($acl_column)) {
        return ERR_WRONG_PARAMETERS;
    }

    $raw_acltags = tags_get_user_groups_and_tags($id_user, $access);

    $acltags = [];
    foreach ($raw_acltags as $group => $taglist) {
        if (empty($id_group) === false && array_key_exists($group, $id_group) === false) {
            continue;
        }

        if (!empty($taglist)) {
            $acltags[$group] = explode(',', $taglist);
        } else {
            $acltags[$group] = '';
        }
    }

    switch ($return_mode) {
        case 'data':
            // Stop here and return the array.
        return $acltags;

            break;
        case 'module_condition':
            // Return the condition of the tags for tagente_modulo table.
            $condition = tags_get_acl_tags_module_condition(
                $acltags,
                $query_table,
                empty($childrens_ids) ? [] : $childrens_ids
            );
            if (!empty($condition)) {
                return " $query_prefix ".$condition;
            }
        break;

        case 'event_condition':
            // Return the condition of the tags for tevento table.
            $condition = tags_get_acl_tags_event_condition(
                $acltags,
                $meta,
                $force_group_and_tag,
                false,
                $id_grupo_table_pretag,
                $alt_id_grupo_table_pretag,
                $search_secondary_group
            );

            if (!empty($condition)) {
                return " $query_prefix ".'('.$condition.')';
            }
        break;

        default:
            // Default.
        break;
    }

    return '';
}


/**
 * Transform the acl_groups data into a SQL condition
 *
 * @param mixed acl_groups data calculated in tags_get_acl_tags function
 *
 * @return string SQL condition for tagente_module
 */
function tags_get_acl_tags_module_condition($acltags, $modules_table='', $force_tags=[])
{
    if (!empty($modules_table)) {
        $modules_table .= '.';
    }

    $condition = '';
    $group_conditions = [];

    $without_tags = [];
    $has_secondary = enterprise_hook('agents_is_using_secondary_groups');
    // The acltags array contains the groups with the acl propagation applied
    // after the changes done into the 'tags_get_user_groups_and_tags' function.
    foreach ($acltags as $group_id => $group_tags) {
        if (empty($group_tags)) {
            // $group_tags = [];
            if (!empty($force_tags)) {
                $group_tags = $force_tags;
            }
        }

        if (!empty($group_tags) && !empty($force_tags)) {
            $group_tags = array_intersect($force_tags, $group_tags);
        }

        $tag_join = '';
        if (!empty($group_tags)) {
            $tag_join = sprintf('AND ttag_module.id_tag IN (%s)', is_array($group_tags) ? implode(',', $group_tags) : $group_tags);
            if ($has_secondary) {
                $agent_condition = sprintf('((tagente.id_grupo = %d OR tasg.id_group = %d) %s)', $group_id, $group_id, $tag_join);
            } else {
                $agent_condition = sprintf('((tagente.id_grupo = %d %s))', $group_id, $tag_join);
            }

            $group_conditions[] = $agent_condition;
        } else if (!empty($force_tags) || !empty($group_id)) {
            $without_tags[] = $group_id;
        }
    }

    if (!empty($group_conditions)) {
        $condition = implode(' OR ', $group_conditions);
    }

    if (!empty($without_tags)) {
        if (!empty($condition)) {
            $condition .= ' OR ';
        }

        $in_group = implode(',', $without_tags);
        if ($has_secondary) {
            $condition .= sprintf('(tagente.id_grupo IN (%s) OR tasg.id_group IN (%s))', $in_group, $in_group);
        } else {
            $condition .= sprintf('(tagente.id_grupo IN (%s))', $in_group);
        }
    }

    $condition = !empty($condition) ? "($condition)" : '';

    return $condition;
}


/**
 * Transform the acl_groups data into a SQL condition
 *
 * @param mixed acl_groups data calculated in tags_get_acl_tags function
 *
 * @return string SQL condition for tagente_module
 */


function tags_get_acl_tags_event_condition(
    $acltags,
    $meta=false,
    $force_group_and_tag=false,
    $force_equal=false,
    $id_grupo_table_pretag='',
    $alt_id_grupo_table_pretag='',
    $search_secondary_group=true
) {
    global $config;
    $condition = [];

    // Get all tags of the system.
    $all_tags = tags_get_all_tags(false);

    $without_tags = [];
    foreach ($acltags as $group_id => $group_tags) {
        // NO check if there is not tag associated with groups.
        if (empty($group_tags)) {
            $without_tags[] = $group_id;
            continue;
        }

        // Group condition (The module belongs to an agent of the group X)
        // $group_condition = sprintf('id_grupo IN (%s)', implode(',', array_values(groups_get_children_ids($group_id, true))));.
        $group_condition = '('.$id_grupo_table_pretag.'id_grupo = '.$group_id;

        if ($search_secondary_group === true) {
                $group_condition .= ' OR '.$alt_id_grupo_table_pretag.'id_group = '.$group_id;
        }

        $group_condition .= ')';

        // Tags condition (The module has at least one of the restricted tags).
        $tags_condition = '';
        $tags_condition_array = [];

        foreach ($group_tags as $tag) {
            // If the tag ID doesnt exist, ignore.
            if (!isset($all_tags[$tag])) {
                continue;
            }

            $tags_condition_array[] = $force_equal ? sprintf('tags = "%s"', io_safe_input($all_tags[$tag])) : "tags LIKE '%".io_safe_input($all_tags[$tag])."%'";
        }

        // If there is not tag currently in Pandora, block the group info.
        if (empty($tags_condition_array)) {
            $tags_condition_array[] = '1=0';
        }

        $tags_condition = $group_condition.' AND ('.implode(' OR ', $tags_condition_array).')';
        $condition[] = "($tags_condition)\n";
    }

    if (!empty($condition)) {
        $condition = implode(' OR ', $condition);
    } else {
        $condition = '';
    }

    if (!empty($without_tags)) {
        if (!empty($condition)) {
            $condition .= ' OR  ';
        }

        $in_group = implode(',', $without_tags);
        $condition .= sprintf('('.$id_grupo_table_pretag.'id_grupo IN (%s)', $in_group);

        if ($search_secondary_group === true) {
                $condition .= sprintf(' OR '.$alt_id_grupo_table_pretag.'id_group IN (%s)', $in_group);
        }

        $condition .= ')';
    }

    $condition = !empty($condition) ? "($condition)" : '';

    return $condition;
}


/**
 * Check if a user has assigned acl tags or not (if is admin, is like not acl tags)
 *
 * @param string ID of the user (with false the user will be taked from config)
 *
 * @return boolean true if the user has tags and false if not
 */
function tags_has_user_acl_tags($id_user=false)
{
    global $config;

    if ($id_user === false) {
        $id_user = $config['id_user'];
    }

    if (is_user_admin($id_user)) {
        return false;
    }

    $query = "SELECT count(*)
		FROM tusuario_perfil
		WHERE tusuario_perfil.id_usuario = '$id_user'
			AND tags != '' AND tags !='0'";

    $user_tags = db_get_value_sql($query);

    return (bool) $user_tags;
}


/**
 * Get the tags of a user in an ACL flag
 *
 * @param string ID of the user (with false the user will be taked from config)
 * @param string Access flag where check what tags have the user
 * @param bool returns 0 if the user has all the tags
 *
 * @return array Returns the user's Tags
 */
function tags_get_user_tags($id_user=false, $access='AR', $return_tag_any=false)
{
    global $config;

    // users_is_strict_acl.
    if ($id_user === false) {
        $id_user = $config['id_user'];
    }

    // Get all tags to have the name of all of them.
    $all_tags = tags_get_all_tags();

    // If at least one of the profiles of this access flag hasent
    // tags restrictions, the user can see all tags.
    $acl_column = get_acl_column($access);

    if (empty($acl_column)) {
        return [];
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $query = sprintf(
                "
				SELECT count(*) 
				FROM tusuario_perfil, tperfil
				WHERE tperfil.id_perfil = tusuario_perfil.id_perfil
					AND tusuario_perfil.id_usuario = '%s'
					AND tperfil.%s = 1
					AND tags <> ''",
                $id_user,
                $acl_column
            );
        break;

        case 'oracle':
            $query = sprintf(
                "
				SELECT count(*) 
				FROM tusuario_perfil, tperfil
				WHERE tperfil.id_perfil = tusuario_perfil.id_perfil
					AND tusuario_perfil.id_usuario = '%s'
					AND tperfil.%s = 1
					AND dbms_lob.getlength(tags) > 0",
                $id_user,
                $acl_column
            );
        break;

        default:
            // Default.
        break;
    }

    $profiles_without_tags = db_get_value_sql($query);

    if (users_is_admin() === true || $profiles_without_tags == 0) {
        // --------------------------------------------------------------
        // FIXED FOR TICKET #1921
        //
        // If the user is setted with strict ACL, the pandora does not
        // show any tags. Thanks Mr. C from T.
        //
        // --------------------------------------------------------------
        if (users_is_strict_acl($id_user)) {
            return [];
        } else {
            if ($return_tag_any) {
                return 0;
            } else {
                return $all_tags;
            }
        }
    }

    // Get the tags of the required access flag for each group.
    $tags = tags_get_acl_tags($id_user, 0, $access, 'data');
    // If there are wrong parameters or fail ACL check, return false.
    if ($tags_user === ERR_WRONG_PARAMETERS || $tags_user === ERR_ACL) {
        return [];
    }

    // Merge the tags to get an array with all of them.
    $user_tags_id = [];

    foreach ($tags as $t) {
        if (empty($user_tags_id)) {
            $user_tags_id = $t;
        } else {
            if (empty($t)) {
                // Empty is 'all of them'.
                // TODO: Review this...
                $t = [];
            }

            $user_tags_id = array_unique(array_merge($t, $user_tags_id));
        }
    }

    // Set the format id=>name to tags.
    $user_tags = [];
    foreach ($user_tags_id as $id) {
        if (!isset($all_tags[$id])) {
            continue;
        }

        $user_tags[$id] = $all_tags[$id];
    }

    return $user_tags;
}


function tags_get_tags_for_module_search($id_user=false, $access='AR')
{
    global $config;

    // users_is_strict_acl.
    if ($id_user === false) {
        $id_user = $config['id_user'];
    }

    // Get all tags to have the name of all of them.
    $all_tags = tags_get_all_tags();

    // If at least one of the profiles of this access flag hasent
    // tags restrictions, the user can see all tags.
    $acl_column = get_acl_column($access);

    if (empty($acl_column)) {
        return [];
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $query = sprintf(
                "
				SELECT count(*)
				FROM tusuario_perfil, tperfil
				WHERE tperfil.id_perfil = tusuario_perfil.id_perfil
					AND tusuario_perfil.id_usuario = '%s'
					AND tperfil.%s = 1
					AND tags <> ''",
                $id_user,
                $acl_column
            );
        break;

        case 'oracle':
            $query = sprintf(
                "
				SELECT count(*)
				FROM tusuario_perfil, tperfil
				WHERE tperfil.id_perfil = tusuario_perfil.id_perfil
					AND tusuario_perfil.id_usuario = '%s'
					AND tperfil.%s = 1
					AND dbms_lob.getlength(tags) > 0",
                $id_user,
                $acl_column
            );
        break;

        default:
            // Default.
        break;
    }

    $profiles_without_tags = db_get_value_sql($query);

    if ($profiles_without_tags == 0) {
        // --------------------------------------------------------------
        // FIXED FOR TICKET #1921
        //
        // If the user is setted with strict ACL, the pandora does not
        // show any tags. Thanks Mr. C from T.
        //
        // --------------------------------------------------------------
        return false;
    }

    // Get the tags of the required access flag for each group.
    $tags = tags_get_acl_tags($id_user, 0, $access, 'data');
    // If there are wrong parameters or fail ACL check, return false.
    if ($tags_user === ERR_WRONG_PARAMETERS || $tags_user === ERR_ACL) {
        return [];
    }

    // Merge the tags to get an array with all of them.
    $user_tags_id = [];

    foreach ($tags as $t) {
        if (empty($user_tags_id)) {
            $user_tags_id = $t;
        } else {
            $user_tags_id = array_unique(array_merge($t, $user_tags_id));
        }
    }

    // Set the format id=>name to tags.
    $user_tags = [];
    foreach ($user_tags_id as $id) {
        if (!isset($all_tags[$id])) {
            continue;
        }

        $user_tags[$id] = $all_tags[$id];
    }

    return $user_tags;
}


function tags_check_acl_by_module(
    $id_module=0,
    $id_user=false,
    $access='AW'
) {
    global $config;

    if (empty($id_module)) {
        return false;
    }

    if ($id_user === false) {
        $id_user = $config['id_user'];
    }

    $tags = tags_get_module_tags($id_module);
    $groups = modules_get_agent_groups($id_module);
    $user_groups = users_get_groups($id_user, $acces, false, true);

    $acl_column = get_acl_column($access);
    foreach ($groups as $group) {
        // If user has not permission for this group,go to next group.
        if (!isset($user_groups[$group])) {
            continue;
        }

        // No tags means user can see all tags for this group.
        if (empty($user_groups[$group]['tags'][$acl_column])) {
            return true;
        }

        // Check acl
        $intersection = array_intersect($tags, $user_groups[$group]['tags'][$acl_column]);
        if (!empty($intersection)) {
            return true;
        }
    }

    return false;
}


// This function checks event ACLs.
function tags_checks_event_acl($id_user, $id_group, $access, $tags=[], $childrens_ids=[])
{
    global $config;

    if ($id_user === false) {
        $id_user = $config['id_user'];
    }

    if (users_is_admin($id_user)) {
        return true;
    }

    $tags_user = tags_get_acl_tags($id_user, $id_group, $access, 'data', '', '', true, $childrens_ids, true);
    // If there are wrong parameters or fail ACL check, return false.
    if ($tags_user === ERR_WRONG_PARAMETERS || $tags_user === ERR_ACL) {
        return false;
    }

    // check user without tags.
    $sql = "SELECT id_usuario FROM tusuario_perfil
		WHERE id_usuario = '".$config['id_user']."' AND tags = ''
			AND id_perfil IN (
				SELECT id_perfil
				FROM tperfil
				WHERE ".get_acl_column($access).' = 1)';

    if (isset($id_group)) {
        $sql .= ' AND id_grupo = '.$id_group;
    }

    $user_has_perm_without_tags = db_get_all_rows_sql($sql);

    if ($user_has_perm_without_tags) {
        return true;
    }

    $tags_aux = [];
    $tags_str = '';
    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $tag_id = tags_get_id($tag);
            if (isset($tag_id) && ($tag_id !== false)) {
                $tags_aux[$tag_id] = $tag_id;
            }
        }

        $tags_str = implode(',', $tags_aux);
    }

    $query = sprintf(
        "SELECT tags, id_grupo 
				FROM tusuario_perfil, tperfil
				WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
					tusuario_perfil.id_usuario = '%s' AND 
					tperfil.%s = 1
				ORDER BY id_grupo",
        $id_user,
        get_acl_column($access)
    );
    $user_tags = db_get_all_rows_sql($query);

    if ($user_tags === false) {
        $user_tags = [];
    }

    foreach ($user_tags as $user_tag) {
        $tags_user = $user_tag['tags'];
        $id_group_user = $user_tag['id_grupo'];
        $childrens = groups_get_children($id_group_user, null, true);

        if (empty($childrens)) {
            $group_ids = $id_group_user;
        } else {
            $childrens_ids[] = $id_group_user;
            foreach ($childrens as $child) {
                $childrens_ids[] = (int) $child['id_grupo'];
            }

            $group_ids = implode(',', $childrens_ids);
        }

        $tag_conds = '';

        if (!empty($tags_str)) {
            $tag_conds = " AND (tags IN ($tags_str) OR tags = '') ";
        } else if (!empty($tags_user)) {
            $tag_conds = " AND (tags IN ($tags_user) OR tags = '') ";
        } else {
            $tag_conds = " AND tags = '' ";
        }

        $sql = "SELECT id_usuario FROM tusuario_perfil
			WHERE id_usuario = '".$config['id_user']."' $tag_conds 
			AND id_perfil IN (SELECT id_perfil FROM tperfil WHERE ".get_acl_column($access)."=1)
			AND id_grupo IN ($group_ids)";

        $has_perm = db_get_value_sql($sql);

        if ($has_perm) {
            return true;
        }
    }

    return false;
}


/**
 * Get the number of the agents that pass the filters.
 *
 * @param mixed   $id_tag          Id in integer or a set of ids into an array.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'status': (mixed) Agent status. Single or grouped into an array. e.g.: AGENT_STATUS_CRITICAL.
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function tags_get_agents_counter($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[], $realtime=true)
{
    // Avoid mysql error.
    if (empty($id_tag)) {
        return false;
    }

    $groups_clause = '';
    if (!empty($groups_and_tags)) {
        $groups_id = [];
        foreach ($groups_and_tags as $group_id => $tags) {
            if (!empty($tags)) {
                $tags_arr = explode(',', $tags);
                foreach ($tags_arr as $tag) {
                    if ($tag == $id_tag) {
                        $hierarchy_groups = groups_get_children_ids($group_id);
                        $groups_id = array_merge($groups_id, $hierarchy_groups);
                    }
                }
            }
        }

        if (array_search(0, $groups_id) === false) {
            $groups_id = array_unique($groups_id);
            $groups_id_str = implode(',', $groups_id);
            $groups_clause = " AND ta.id_grupo IN ($groups_id_str)";
        }
    }

    $agent_name_filter = '';
    $agent_status = AGENT_STATUS_ALL;
    if (!empty($agent_filter)) {
        // Name.
        if (isset($agent_filter['name']) && !empty($agent_filter['name'])) {
            $agent_name_filter = "AND ta.nombre LIKE '%".$agent_filter['name']."%'";
        }

        // Status.
        if (isset($agent_filter['status'])) {
            if (is_array($agent_filter['status'])) {
                $agent_status = array_unique($agent_filter['status']);
            } else {
                $agent_status = $agent_filter['status'];
            }
        }
    }

    $module_name_filter = '';
    $module_status_filter = '';
    $module_status_array = [];
    if (!empty($module_filter)) {
        // IMPORTANT: The module filters will force the realtime search.
        $realtime = true;

        // Name.
        if (isset($module_filter['name']) && !empty($module_filter['name'])) {
            $module_name_filter = "AND tam.nombre LIKE '%".$module_filter['name']."%'";
        }

        // Status.
        if (isset($module_filter['status'])) {
            $module_status = $module_filter['status'];
            if (is_array($module_status)) {
                $module_status = array_unique($module_status);
            } else {
                $module_status = [$module_status];
            }

            foreach ($module_status as $status) {
                switch ($status) {
                    case AGENT_MODULE_STATUS_ALL:
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING;
                        $module_status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                        $module_status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $module_status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;

                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                    break;

                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case AGENT_MODULE_STATUS_WARNING:
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING;
                    break;

                    case AGENT_MODULE_STATUS_UNKNOWN:
                        $module_status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                    break;

                    case AGENT_MODULE_STATUS_NO_DATA:
                    case AGENT_MODULE_STATUS_NOT_INIT:
                        $module_status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $module_status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                    break;

                    case AGENT_MODULE_STATUS_NORMAL_ALERT:
                    case AGENT_MODULE_STATUS_NORMAL:
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;

                    default:
                        // Default.
                    break;
                }
            }

            if (!empty($module_status_array)) {
                $module_status_array = array_unique($module_status_array);
                $status_str = implode(',', $module_status_array);

                $module_status_filter = "INNER JOIN tagente_estado tae
											ON tam.id_agente_modulo = tae.id_agente_modulo
												AND tae.estado IN ($status_str)";
            }
        }
    }

    $count = 0;
    if ($realtime) {
        $sql = "SELECT DISTINCT ta.id_agente
				FROM tagente ta
				INNER JOIN tagente_modulo tam
					ON ta.id_agente = tam.id_agente
						AND tam.disabled = 0
						$module_name_filter
				$module_status_filter
				INNER JOIN ttag_module ttm
					ON ttm.id_tag = $id_tag
						AND tam.id_agente_modulo = ttm.id_agente_modulo
				WHERE ta.disabled = 0
					$agent_name_filter
					$groups_clause";
        $agents = db_get_all_rows_sql($sql);

        if ($agents === false) {
            return $count;
        }

        if ($agent_status == AGENT_STATUS_ALL) {
            return count($agents);
        }

        foreach ($agents as $agent) {
            $agent_filter['id'] = $agent['id_agente'];

            $total = 0;
            $critical = 0;
            $warning = 0;
            $unknown = 0;
            $not_init = 0;
            $normal = 0;
            // Without module filter.
            if (empty($module_status_array)) {
                $total = (int) tags_get_total_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                $critical = (int) tags_get_critical_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                $warning = (int) tags_get_warning_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                $unknown = (int) tags_get_unknown_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                $not_init = (int) tags_get_not_init_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                $normal = (int) tags_get_normal_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
            }

            // With module filter.
            else {
                foreach ($module_status_array as $status) {
                    switch ($status) {
                        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                        case AGENT_MODULE_STATUS_CRITICAL_BAD:
                            $critical = (int) tags_get_critical_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                        break;

                        case AGENT_MODULE_STATUS_WARNING_ALERT:
                        case AGENT_MODULE_STATUS_WARNING:
                            $warning = (int) tags_get_warning_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                        break;

                        case AGENT_MODULE_STATUS_UNKNOWN:
                            $unknown = (int) tags_get_unknown_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                        break;

                        case AGENT_MODULE_STATUS_NO_DATA:
                        case AGENT_MODULE_STATUS_NOT_INIT:
                            $not_init = (int) tags_get_not_init_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                        break;

                        case AGENT_MODULE_STATUS_NORMAL_ALERT:
                        case AGENT_MODULE_STATUS_NORMAL:
                            $normal = (int) tags_get_normal_monitors($id_tag, $groups_and_tags, $agent_filter, $module_filter);
                        break;

                        default:
                            // Default.
                        break;
                    }
                }

                $total = ($critical + $warning + $unknown + $not_init + $normal);
            }

            if (!is_array($agent_status)) {
                switch ($agent_status) {
                    case AGENT_STATUS_CRITICAL:
                        if ($critical > 0) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_WARNING:
                        if ($total > 0 && $critical = 0 && $warning > 0) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_UNKNOWN:
                        if ($critical == 0 && $warning == 0 && $unknown > 0) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_NOT_INIT:
                        if ($total == 0 || $total == $not_init) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_NORMAL:
                        if ($critical == 0 && $warning == 0 && $unknown == 0 && $normal > 0) {
                            $count++;
                        }
                    break;

                    default:
                        // The status doesn't exist.
                    return 0;
                }
            } else {
                if (array_search(AGENT_STATUS_CRITICAL, $agent_status) !== false) {
                    if ($critical > 0) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_WARNING, $agent_status) !== false) {
                    if ($total > 0 && $critical = 0 && $warning > 0) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_UNKNOWN, $agent_status) !== false) {
                    if ($critical == 0 && $warning == 0 && $unknown > 0) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_NOT_INIT, $agent_status) !== false) {
                    if ($total == 0 || $total == $not_init) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_NORMAL, $agent_status) !== false) {
                    if ($critical == 0 && $warning == 0 && $unknown == 0 && $normal > 0) {
                        $count++;
                    }
                }
                // Invalid status.
                else {
                    return 0;
                }
            }
        }
    } else {
        $status_filter = '';
        // Transform the element into a one element array.
        if (!is_array($agent_status)) {
            $agent_status = [$agent_status];
        }

        // Support for multiple status. It counts the agents for each status and sum the result.
        foreach ($agent_status as $status) {
            switch ($agent_status) {
                case AGENT_STATUS_ALL:
                    $status_filter = '';
                break;

                case AGENT_STATUS_CRITICAL:
                    $status_filter = 'AND ta.critical_count > 0';
                break;

                case AGENT_STATUS_WARNING:
                    $status_filter = 'AND ta.total_count > 0
									AND ta.critical_count = 0
									AND ta.warning_count > 0';
                break;

                case AGENT_STATUS_UNKNOWN:
                    $status_filter = 'AND ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count > 0';
                break;

                case AGENT_STATUS_NOT_INIT:
                    $status_filter = 'AND (ta.total_count = 0
										OR ta.total_count = ta.notinit_count)';
                break;

                case AGENT_STATUS_NORMAL:
                    $status_filter = 'AND ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count = 0
									AND ta.normal_count > 0';
                break;

                default:
                    // The type doesn't exist.
                return 0;
            }

            $sql = "SELECT COUNT(DISTINCT ta.id_agente) 
					FROM tagente ta
					INNER JOIN tagente_modulo tam
						ON ta.id_agente = tam.id_agente
							AND tam.disabled = 0
							$module_name_filter
					$module_status_filter
					INNER JOIN ttag_module ttm
						ON ttm.id_tag = $id_tag
							AND tam.id_agente_modulo = ttm.id_agente_modulo
					WHERE ta.disabled = 0
						$status_filter
						$agent_name_filter
						$groups_clause";

            $count += (int) db_get_sql($sql);
        }
    }

    return $count;
}


/**
 * Get the number of the agents that pass the filters.
 *
 * @param mixed   $id_tag          Id in integer or a set of ids into an array.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function tags_get_total_agents($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[], $realtime=true)
{
    // Always modify the agent status filter.
    $agent_filter['status'] = AGENT_STATUS_ALL;
    return tags_get_agents_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter, $realtime);
}


/**
 * Get the number of the normal agents that pass the filters.
 *
 * @param mixed   $id_tag          Id in integer or a set of ids into an array.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function tags_get_normal_agents($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[], $realtime=true)
{
    // Always modify the agent status filter.
    $agent_filter['status'] = AGENT_STATUS_NORMAL;
    return tags_get_agents_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter, $realtime);
}


/**
 * Get the number of the warning agents that pass the filters.
 *
 * @param mixed   $id_tag          Id in integer or a set of ids into an array.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function tags_get_warning_agents($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[], $realtime=true)
{
    // Always modify the agent status filter.
    $agent_filter['status'] = AGENT_STATUS_WARNING;
    return tags_get_agents_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter, $realtime);
}


/**
 * Get the number of the critical agents that pass the filters.
 *
 * @param mixed   $id_tag          Id in integer or a set of ids into an array.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function tags_get_critical_agents($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[], $realtime=true)
{
    // Always modify the agent status filter.
    $agent_filter['status'] = AGENT_STATUS_CRITICAL;
    return tags_get_agents_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter, $realtime);
}


/**
 * Get the number of the unknown agents that pass the filters.
 *
 * @param mixed   $id_tag          Id in integer or a set of ids into an array.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function tags_get_unknown_agents($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[], $realtime=true)
{
    // Always modify the agent status filter.
    $agent_filter['status'] = AGENT_STATUS_UNKNOWN;
    return tags_get_agents_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter, $realtime);
}


/**
 * Get the number of the not init agents that pass the filters.
 *
 * @param mixed   $id_tag          Id in integer or a set of ids into an array.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function tags_get_not_init_agents($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[], $realtime=true)
{
    // Always modify the agent status filter.
    $agent_filter['status'] = AGENT_STATUS_NOT_INIT;
    return tags_get_agents_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter, $realtime);
}


/**
 * Get the number of the monitors that pass the filters.
 *
 * @param mixed $id_tag          Id in integer or a set of ids into an array.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param array $agent_filter    Filter of the agents.
 *    This filter support the following fields:
 *    -'name': (string) Agent name. e.g.: "agent_1".
 *    -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter   Filter of the modules.
 *   This filter support the following fields:
 *   -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *   -'name': (string) Module name. e.g.: "module_1".
 *
 * @return integer Number of monitors.
 */
function tags_get_monitors_counter($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[])
{
    // Avoid mysql error.
    if (empty($id_tag)) {
        return false;
    }

    $groups_clause = '';
    if (!empty($groups_and_tags)) {
        $groups_id = [];
        foreach ($groups_and_tags as $group_id => $tags) {
            if (!empty($tags)) {
                $tags_arr = explode(',', $tags);
                foreach ($tags_arr as $tag) {
                    if ($tag == $id_tag) {
                        $hierarchy_groups = groups_get_children_ids($group_id);
                        $groups_id = array_merge($groups_id, $hierarchy_groups);
                    }
                }
            }
        }

        if (array_search(0, $groups_id) === false) {
            $groups_id = array_unique($groups_id);
            $groups_id_str = implode(',', $groups_id);
            $groups_clause = " AND ta.id_grupo IN ($groups_id_str)";
        }
    }

    $agent_name_filter = '';
    $agents_clause = '';
    if (!empty($agent_filter)) {
        // Name.
        if (isset($agent_filter['name']) && !empty($agent_filter['name'])) {
            $agent_name_filter = "AND ta.nombre LIKE '%".$agent_filter['name']."%'";
        }

        // ID.
        if (isset($agent_filter['id'])) {
            if (is_array($agent_filter['id'])) {
                $agents = array_unique($agent_filter['id']);
            } else {
                $agents = [$agent_filter['id']];
            }

            $agents_str = implode(',', $agents);
            $agents_clause = "AND ta.id_agente IN ($agents_str)";
        }
    }

    $module_name_filter = '';
    $module_status_array = '';
    $modules_clause = '';
    if (!empty($module_filter)) {
        // Name.
        if (isset($module_filter['name']) && !empty($module_filter['name'])) {
            $module_name_filter = "AND tam.nombre LIKE '%".$module_filter['name']."%'";
        }

        // Status.
        if (isset($module_filter['status'])) {
            $module_status = $module_filter['status'];
            if (is_array($module_status)) {
                $module_status = array_unique($module_status);
            } else {
                $module_status = [$module_status];
            }

            $status_array = '';
            foreach ($module_status as $status) {
                switch ($status) {
                    case AGENT_MODULE_STATUS_ALL:
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                        $status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_WARNING;
                        $status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                        $status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;

                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                    break;

                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case AGENT_MODULE_STATUS_WARNING:
                        $status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_WARNING;
                    break;

                    case AGENT_MODULE_STATUS_UNKNOWN:
                        $status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                    break;

                    case AGENT_MODULE_STATUS_NO_DATA:
                    case AGENT_MODULE_STATUS_NOT_INIT:
                        $status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                    break;

                    case AGENT_MODULE_STATUS_NORMAL_ALERT:
                    case AGENT_MODULE_STATUS_NORMAL:
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;

                    default:
                        // The status doesn't exist.
                    return false;
                }
            }

            if (!empty($status_array)) {
                $status_array = array_unique($status_array);
                $status_str = implode(',', $status_array);

                $modules_clause = "AND tae.estado IN ($status_str)";
            }
        }
    }

    $sql = "SELECT COUNT(DISTINCT tam.id_agente_modulo)
			FROM tagente_modulo tam
			INNER JOIN tagente_estado tae
				ON tam.id_agente_modulo = tae.id_agente_modulo
					$modules_clause
			INNER JOIN ttag_module ttm
				ON ttm.id_tag = $id_tag
					AND tam.id_agente_modulo = ttm.id_agente_modulo
			INNER JOIN tagente ta
				ON tam.id_agente = ta.id_agente
					AND ta.disabled = 0
					$agent_name_filter
					$agents_clause
					$groups_clause
			WHERE tam.disabled = 0
				$module_name_filter";

    $count = db_get_sql($sql);

    return $count;
}


/**
 * Get the number of the total monitors that pass the filters.
 *
 * @param mixed $id_tag          Id in integer or a set of ids into an array.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param array $agent_filter    Filter of the agents.
 *    This filter support the following fields:
 *    -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter   Filter of the modules.
 *   This filter support the following fields:
 *   -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *   -'name': (string) Module name. e.g.: "module_1".
 *
 * @return integer Number of monitors.
 */
function tags_get_total_monitors($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[])
{
    // Always modify the module status filter.
    $module_filter['status'] = AGENT_MODULE_STATUS_ALL;
    return tags_get_monitors_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter);
}


/**
 * Get the number of the normal monitors that pass the filters.
 *
 * @param mixed $id_tag          Id in integer or a set of ids into an array.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param array $agent_filter    Filter of the agents.
 *    This filter support the following fields:
 *    -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter   Filter of the modules.
 *   This filter support the following fields:
 *   -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *   -'name': (string) Module name. e.g.: "module_1".
 *
 * @return integer Number of monitors.
 */
function tags_get_normal_monitors($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[])
{
    // Always modify the module status filter.
    $module_filter['status'] = AGENT_MODULE_STATUS_NORMAL;
    return tags_get_monitors_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter);
}


/**
 * Get the number of the critical monitors that pass the filters.
 *
 * @param mixed $id_tag          Id in integer or a set of ids into an array.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param array $agent_filter    Filter of the agents.
 *    This filter support the following fields:
 *    -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter   Filter of the modules.
 *   This filter support the following fields:
 *   -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *   -'name': (string) Module name. e.g.: "module_1".
 *
 * @return integer Number of monitors.
 */
function tags_get_critical_monitors($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[])
{
    // Always modify the module status filter.
    $module_filter['status'] = AGENT_MODULE_STATUS_CRITICAL_BAD;
    return tags_get_monitors_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter);
}


/**
 * Get the number of the warning monitors that pass the filters.
 *
 * @param mixed $id_tag          Id in integer or a set of ids into an array.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param array $agent_filter    Filter of the agents.
 *    This filter support the following fields:
 *    -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter   Filter of the modules.
 *   This filter support the following fields:
 *   -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *   -'name': (string) Module name. e.g.: "module_1".
 *
 * @return integer Number of monitors.
 */
function tags_get_warning_monitors($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[])
{
    // Always modify the module status filter.
    $module_filter['status'] = AGENT_MODULE_STATUS_WARNING;
    return tags_get_monitors_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter);
}


/**
 * Get the number of the not init monitors that pass the filters.
 *
 * @param mixed $id_tag          Id in integer or a set of ids into an array.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param array $agent_filter    Filter of the agents.
 *    This filter support the following fields:
 *    -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter   Filter of the modules.
 *   This filter support the following fields:
 *   -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *   -'name': (string) Module name. e.g.: "module_1".
 *
 * @return integer Number of monitors.
 */
function tags_get_not_init_monitors($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[])
{
    // Always modify the module status filter.
    $module_filter['status'] = AGENT_MODULE_STATUS_NOT_INIT;
    return tags_get_monitors_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter);
}


/**
 * Get the number of the unknown monitors that pass the filters.
 *
 * @param mixed $id_tag          Id in integer or a set of ids into an array.
 * @param array $groups_and_tags Array with strict ACL rules.
 * @param array $agent_filter    Filter of the agents.
 *    This filter support the following fields:
 *    -'id': (mixed) Agent id. e.g.: "1".
 * @param array $module_filter   Filter of the modules.
 *   This filter support the following fields:
 *   -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *   -'name': (string) Module name. e.g.: "module_1".
 *
 * @return integer Number of monitors.
 */
function tags_get_unknown_monitors($id_tag, $groups_and_tags=[], $agent_filter=[], $module_filter=[])
{
    // Always modify the module status filter.
    $module_filter['status'] = AGENT_MODULE_STATUS_UNKNOWN;
    return tags_get_monitors_counter($id_tag, $groups_and_tags, $agent_filter, $module_filter);
}


/**
 * Get the monitors fired alerts count.
 *
 * @param integer $id_tag          Id of the tag to filter the modules.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param mixed   $id_agente       Id or ids of the agent to filter the modules.
 *
 * @return mixed Returns the count of the modules fired alerts or false on error.
 */
function tags_monitors_fired_alerts($id_tag, $groups_and_tags=[], $id_agente=false)
{
    // Avoid mysql error.
    if (empty($id_tag)) {
        return;
    }

    $groups_clause = '';
    if (!empty($groups_and_tags)) {
        $groups_id = [];
        foreach ($groups_and_tags as $group_id => $tags) {
            if (!empty($tags)) {
                $tags_arr = explode(',', $tags);
                foreach ($tags_arr as $tag) {
                    if ($tag == $id_tag) {
                        $hierarchy_groups = groups_get_children_ids($group_id);
                        $groups_id = array_merge($groups_id, $hierarchy_groups);
                    }
                }
            }
        }

        if (array_search(0, $groups_id) === false) {
            $groups_id_str = implode(',', $groups_id);
            $groups_clause = " AND tagente.id_grupo IN ($groups_id_str)";
        }
    }

    $agents_clause = '';
    if ($id_agente !== false) {
        if (is_array($id_agente)) {
            $id_agente = implode(',', $id_agente);
        }

        $agents_clause = " AND tagente.id_agente IN ($id_agente)";
    }

    $sql = "SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente_modulo.id_agente = tagente.id_agente
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND tagente_modulo.disabled = 0 AND tagente.disabled = 0 
		AND talert_template_modules.disabled = 0 
		AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo 
		AND times_fired > 0 
		AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag = $id_tag)
		$agents_clause
		$groups_clause";

    $count = db_get_sql($sql);

    return $count;
}


/**
 * Get the monitors alerts count.
 *
 * @param integer $id_tag          Id of the tag to filter the modules alerts.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param mixed   $id_agente       Id or ids of the agent to filter the modules.
 *
 * @return mixed Returns the count of the modules alerts or false on error.
 */
function tags_get_monitors_alerts($id_tag, $groups_and_tags=[], $id_agente=false)
{
    // Avoid mysql error.
    if (empty($id_tag)) {
        return;
    }

    $groups_clause = '';
    if (!empty($groups_and_tags)) {
        $groups_id = [];
        foreach ($groups_and_tags as $group_id => $tags) {
            if (!empty($tags)) {
                $tags_arr = explode(',', $tags);
                foreach ($tags_arr as $tag) {
                    if ($tag == $id_tag) {
                        $hierarchy_groups = groups_get_children_ids($group_id);
                        $groups_id = array_merge($groups_id, $hierarchy_groups);
                    }
                }
            }
        }

        if (array_search(0, $groups_id) === false) {
            $groups_id_str = implode(',', $groups_id);
            $groups_clause = " AND tagente.id_grupo IN ($groups_id_str)";
        }
    }

    $agents_clause = '';
    if ($id_agente !== false) {
        if (is_array($id_agente)) {
            $id_agente = implode(',', $id_agente);
        }

        $agents_clause = " AND tagente.id_agente IN ($id_agente)";
    }

    $sql = "SELECT COUNT(talert_template_modules.id)
		FROM talert_template_modules, tagente_modulo, tagente_estado, tagente
		WHERE tagente_modulo.id_agente = tagente.id_agente
		AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
		AND tagente_modulo.disabled = 0 AND tagente.disabled = 0
		AND	talert_template_modules.disabled = 0 
		AND talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo
		AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag = $id_tag)
		$agents_clause
		$groups_clause";

    $count = db_get_sql($sql);

    return $count;
}


function __add_acltags(&$acltags, $group_id, $tags_str)
{
    if (!isset($acltags[$group_id])) {
        // Add the new element.
        $acltags[$group_id] = $tags_str;
    } else {
        // Add the tags. The empty tags have priority cause mean more permissions.
        $existing_tags = $acltags[$group_id];

        if (!empty($existing_tags)) {
            $existing_tags_array = explode(',', $existing_tags);

            // Store the empty tags.
            if (empty($tags_str)) {
                $acltags[$group_id] = '';
            }
            // Merge the old and new tabs.
            else {
                $new_tags_array = explode(',', $tags_str);

                $final_tags_array = array_merge($existing_tags_array, $new_tags_array);
                $final_tags_str = implode(',', $final_tags_array);

                if (! empty($final_tags_str)) {
                    $acltags[$group_id] = $final_tags_str;
                }
            }
        }
    }

    // Propagation.
    $propagate = (bool) db_get_value('propagate', 'tgrupo', 'id_grupo', $group_id);
    if ($propagate) {
        $sql = "SELECT id_grupo FROM tgrupo WHERE parent = $group_id";
        $children = db_get_all_rows_sql($sql);

        if ($children === false) {
            $children = [];
        }

        foreach ($children as $children_group) {
            // Add the tags to the children (recursive).
            __add_acltags($acltags, $children_group['id_grupo'], $tags_str);
        }
    }
}


// Return array with groups and their tags.
function tags_get_user_groups_and_tags($id_user=false, $access='AR', $strict_user=false)
{
    global $config;

    if ($id_user == false) {
        $id_user = $config['id_user'];
    }

    $acls = users_get_groups($id_user, $access, false, true);

    $return = [];
    foreach ($acls as $acl) {
        $return[$acl['id_grupo']] = isset($acl['tags'][get_acl_column($access)]) ? implode(',', $acl['tags'][get_acl_column($access)]) : '';
    }

    return $return;
}


/**
 * Get agents filtering by id_tag.
 *
 * @param integer $id_tag Id of the tag to search total agents
 *
 * @return mixed Returns count of agents with this tag or false if they aren't.
 */
function tags_get_all_user_agents(
    $id_tag=false,
    $id_user=false,
    $groups_and_tags=[],
    $filter=false,
    $fields=false,
    $meta=true,
    $strict_user=true,
    $return_all_fields=false
) {
    global $config;

    if (empty($id_tag)) {
        $tag_filter = '';
    } else {
        $tag_filter = " AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag = $id_tag) ";
    }

    if (empty($id_user)) {
        $id_user = $config['id_user'];
    }

    if (!is_array($fields)) {
        $fields = [];
        $fields[0] = 'id_agente';
        $fields[1] = 'nombre';
    }

    $select_fields = implode(',', $fields);

    $groups_clause = '';
    $groups_clause = ' AND tagente.id_grupo IN ('.implode(',', array_keys($groups_and_tags)).')';

    if (!empty($filter['id_group'])) {
        if (is_array($filter['id_group'])) {
            $groups_str = implode(',', $filter['id_group']);
        } else {
            $groups_str = $filter['id_group'];
        }

        $groups_clause .= " AND tagente.id_grupo IN ($groups_str)";
    }

    $status_sql = '';
    if (isset($filter['status'])) {
        switch ($filter['status']) {
            case AGENT_STATUS_NORMAL:
                $status_sql = ' AND (normal_count = total_count)';
            break;

            case AGENT_STATUS_WARNING:
                $status_sql = 'AND (critical_count = 0 AND warning_count > 0)';
            break;

            case AGENT_STATUS_CRITICAL:
                $status_sql = 'AND (critical_count > 0)';
            break;

            case AGENT_STATUS_UNKNOWN:
                $status_sql = 'AND (critical_count = 0 AND warning_count = 0
						AND unknown_count > 0)';
            break;

            case AGENT_STATUS_NOT_NORMAL:
                $status_sql = ' AND (normal_count <> total_count)';
            break;

            case AGENT_STATUS_NOT_INIT:
                $status_sql = 'AND (notinit_count = total_count)';
            break;
        }
    }

    $disabled_sql = '';
    if (!empty($filter['disabled'])) {
        $disabled_sql = ' AND disabled = '.$filter['disabled'];
    }

    $order_by_condition = '';
    if (!empty($filter['order'])) {
        $order_by_condition = ' ORDER BY '.$filter['order'];
    } else {
        $order_by_condition = ' ORDER BY tagente.nombre ASC';
    }

    $limit_sql = '';
    if (isset($filter['offset'])) {
        $offset = $filter['offset'];
    }

    if (isset($filter['limit'])) {
        $limit = $filter['limit'];
    }

    if (isset($offset) && isset($limit)) {
        $limit_sql = " LIMIT $offset, $limit ";
    }

    if (!empty($filter['group_by'])) {
        $group_by = ' GROUP BY '.$filter['group_by'];
    } else {
        $group_by = ' GROUP BY tagente.nombre';
    }

    $id_agent_search = '';
    if (!empty($filter['id_agent'])) {
        $id_agent_search = ' AND tagente.id_agente = '.$filter['id_agent'];
    }

    $search_sql = '';
    $void_agents = '';
    if ($filter) {
        if (($filter['search']) != '') {
            $string = io_safe_input($filter['search']);
            $search_sql = ' AND (tagente.nombre LIKE "%'.$string.'%")';
        }

        if (isset($filter['show_void_agents'])) {
            if (!$filter['show_void_agents']) {
                $void_agents = ' AND tagente_modulo.delete_pending = 0';
            }
        }
    }

    $user_agents_sql = 'SELECT '.$select_fields.'
		FROM tagente, tagente_modulo
		WHERE tagente.id_agente = tagente_modulo.id_agente
		'.$tag_filter.$groups_clause.$search_sql.$void_agents.$status_sql.$disabled_sql.$group_by.$order_by_condition.$limit_sql;

    $user_agents = db_get_all_rows_sql($user_agents_sql);

    if ($user_agents == false) {
        $user_agents = [];
    }

    if ($return_all_fields) {
        return $user_agents;
    }

    if (!$meta) {
        $user_agents_aux = [];

        foreach ($user_agents as $ua) {
            $user_agents_aux[$ua['id_agente']] = $ua['nombre'];
        }

        return $user_agents_aux;
    }

    return $user_agents;
}


function tags_get_agent_modules($id_agent, $id_tag=false, $groups_and_tags=[], $fields=false, $filter=false, $return_all_fields=false, $get_filter_status=-1)
{
    global $config;

    // Avoid mysql error.
    if (empty($id_agent)) {
        return false;
    }

    if (empty($id_tag)) {
        $tag_filter = '';
    } else {
        $tag_filter = " AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag = $id_tag) ";
    }

    if (!is_array($fields)) {
        $fields = [];
        $fields[0] = 'tagente_modulo.id_agente_modulo';
        $fields[1] = 'tagente_modulo.nombre';
    }

    $select_fields = implode(',', $fields);

    if ($filter) {
        $filter_sql = '';
        if (isset($filter['disabled'])) {
            $filter_sql .= ' AND tagente_modulo.disabled = '.$filter['disabled'];
        }

        if (isset($filter['nombre'])) {
            $filter_sql .= ' AND tagente_modulo.nombre LIKE "'.$filter['nombre'].'"';
        }
    }

    if (!empty($groups_and_tags)) {
        $agent_group = db_get_value('id_grupo', 'tagente', 'id_agente', $id_agent);
        if (isset($groups_and_tags[$agent_group]) && ($groups_and_tags[$agent_group] != '')) {
            // ~ $tag_filter = " AND ttag_module.id_tag IN (".$groups_and_tags[$agent_group].")";
            $tag_filter .= ' AND tagente_modulo.id_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag IN ('.$groups_and_tags[$agent_group].'))';
        }
    }

    if ($get_filter_status != -1) {
        $agent_modules_sql = 'SELECT '.$select_fields.'
			FROM tagente_modulo, tagente_estado
			WHERE tagente_modulo.id_agente='.$id_agent.' AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
			 AND tagente_estado.estado = '.$get_filter_status.$tag_filter.$filter_sql.'
			ORDER BY nombre';
    } else {
        $agent_modules_sql = 'SELECT '.$select_fields.'
			FROM tagente_modulo 
			WHERE id_agente='.$id_agent.$tag_filter.$filter_sql.'
			ORDER BY nombre';
    }

    $agent_modules = db_get_all_rows_sql($agent_modules_sql);

    if ($agent_modules == false) {
        $agent_modules = [];
    }

    if ($return_all_fields) {
        $result = [];
        foreach ($agent_modules as $am) {
            $am['status'] = modules_get_agentmodule_status($am['id_agente_modulo']);
            $am['isinit'] = modules_get_agentmodule_is_init($am['id_agente_modulo']);
            if ($am['isinit']) {
            }

            $result[$am['id_agente_modulo']] = $am;
        }

        return $result;
    }

    $result = [];
    foreach ($agent_modules as $am) {
        $result[$am['id_agente_modulo']] = $am['nombre'];
    }

    return $result;
}


function tags_get_module_policy_tags($id_tag, $id_module)
{
    if (empty($id_tag)) {
        return false;
    }

    $id_module_policy = db_get_value_filter(
        'id_policy_module',
        'ttag_module',
        [
            'id_tag'           => $id_tag,
            'id_agente_modulo' => $id_module,
        ]
    );

    return $id_module_policy;
}


/**
 * Get all tags configured to user associated to the agent.
 *
 * @param integer $id_agent Agent to extract tags
 * @param string  $access   Access to check
 *
 * @return mixed
 *         false if user has not permission on agent groups
 *         true if there is not any tag restriction
 *         array with all tags if there are tags configured
 */
function tags_get_user_applied_agent_tags($id_agent, $access='AR')
{
    global $config;

    $agent_groups = agents_get_all_groups_agent($id_agent);
    $user_groups = users_get_groups(false, 'AR', false, true);
    // Check global agent permissions
    if (!check_acl_one_of_groups($config['id_user'], $agent_groups, $access)) {
        return false;
    }

    $acl_column = get_acl_column($access);
    $tags = [];
    foreach ($agent_groups as $group) {
        // If user has not permission to a single group, continue
        if (!isset($user_groups[$group])) {
            continue;
        }

        $group_tags = null;
        if (isset($user_groups[$group]) === true
            && isset($user_groups[$group]['tags']) === true
        ) {
            $group_tags = $user_groups[$group]['tags'][$acl_column];
        }

        if (empty($group_tags) === false) {
            $tags = array_merge($tags, $group_tags);
        } else {
            // If an agent
            return true;
        }
    }

    return empty($tags) ? true : $tags;
}
