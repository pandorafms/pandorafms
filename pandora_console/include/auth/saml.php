<?php
// ______                 __                     _______ _______ _______
// |   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
// |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
// |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================
global $config;

if (!file_exists($config['saml_path'].'simplesamlphp/lib/_autoload.php')) {
    return false;
}


/**
 * Authenticate in saml system (red.es)
 *
 * @param string Login
 * @param string Password
 *
 * @return boolean True if the login succeeds, false otherwise
 */
function saml_process_user_login()
{
    global $config;

    // SAML authentication attributes
    $as = new SimpleSAML_Auth_Simple('PandoraFMS');
    $as->requireAuth();
    $session = SimpleSAML_Session::getSessionFromRequest();
    $session->cleanup();
    $attributes = $as->getAttributes();

    if (empty($attributes)) {
        return false;
    }

    $id_user = $attributes[SAML_MAIL_IN_PANDORA][0];
    $email = $attributes[SAML_MAIL_IN_PANDORA][0];
    $group_name = $attributes[SAML_GROUP_IN_PANDORA][0];
    $profiles_and_tags = $attributes[SAML_ROLE_AND_TAG];

    $profile_names = [];
    $tag_names = [];
    // Manages array with tags and roles to separate them
    foreach ($profiles_and_tags as $profile_or_tag) {
        $is_profile_or_tag = explode(SAML_DEFAULT_PROFILES_AND_TAGS_FORM, $profile_or_tag);
        $is_profile_or_tag2 = explode(':', $is_profile_or_tag[1]);
        if ($is_profile_or_tag2[0] == 'role') {
            $profile_names[] = $is_profile_or_tag2[1];
        } else if ($is_profile_or_tag2[0] == 'tag') {
            $tag_names[] = $is_profile_or_tag2[1];
        }
    }

    // Connect to Pandora db
    $connection = mysql_connect_db(
        $config['pandora_server'],
        $config['pandora_dbname'],
        $config['pandora_user'],
        $config['pandora_pass']
    );

    if ($connection === false) {
        return false;
    }

    // Get the red.es user id
    $rows = db_get_all_rows_sql(
        "SELECT * FROM tusuario
		WHERE id_user = '".$id_user."'",
        false,
        false,
        $connection
    );

    // Checks group id, profiles id and tags id
    $group_id = '';
    $profile_id = [];
    $tag_id = '';
    $tags_to_profile = '';
    if ($group_name != '') {
        $group_id = db_get_all_rows_sql("SELECT id_grupo FROM tgrupo WHERE nombre = '".$group_name."'");
        $group_id = $group_id[0]['id_grupo'];
        if (empty($group_id)) {
            $config['auth_error'] = 'Group not found in database';
            db_pandora_audit('Logon Failed', 'Group '.$group_name.' not found in database', $_SERVER['REMOTE_ADDR']);
            return false;
        }
    }

    if (!empty($profile_names)) {
        foreach ($profile_names as $profile_name) {
            $profile_id[] = db_get_row_sql("SELECT id_perfil FROM tperfil WHERE name = '".io_safe_input($profile_name)."'");
        }
    }

    if (!empty($tag_names)) {
        $i = 0;
        foreach ($tag_names as $tag_name) {
            $tag_id = db_get_row_sql("SELECT id_tag FROM ttag WHERE name = '".io_safe_input($tag_name)."'");
            if ($i == 0) {
                $tags_to_profile = (String) $tag_id['id_tag'];
            } else {
                $tags_to_profile .= ','.(String) $tag_id['id_tag'];
            }

            $i++;
        }
    }

    // If user does not exist in Pandora
    if (empty($rows)) {
        if ($id_user != '') {
            $values_user = [];
            $values_user['id_user'] = $id_user;
            $values_user['email'] = $email;
            $result_insert_user = db_process_sql_insert('tusuario', $values_user);

            // Separates user insert of profile insert
            $values_user_profile = [];
            $values_user_profile['id_usuario'] = $id_user;
            $values_user_profile['id_grupo'] = $group_id;
            $values_user_profile['tags'] = $tags_to_profile;
            foreach ($profile_id as $id) {
                $values_user_profile['id_perfil'] = $id['id_perfil'];
                $result_insert_user_profile = db_process_sql_insert('tusuario_perfil', $values_user_profile);
            }

            if (!$result_insert_user_profile) {
                $config['auth_error'] = 'Login error';
                return false;
            }

            return $id_user;
        } else {
            return false;
        }
    } else {
        $user = $rows[0];
        // To update the profiles, delete the old and insert the new
        $have_profiles = db_get_all_rows_sql("SELECT id_up FROM tusuario_perfil WHERE id_usuario = '".$user['id_user']."'");
        if ($have_profiles) {
            $delete_old_profiles = db_process_sql("DELETE FROM tusuario_perfil WHERE id_usuario = '".$user['id_user']."'");
        }

        $values_user_profile = [];
        $values_user_profile['id_usuario'] = $user['id_user'];
        $values_user_profile['id_grupo'] = $group_id;
        $values_user_profile['tags'] = $tags_to_profile;
        foreach ($profile_id as $id) {
            $values_user_profile['id_perfil'] = $id['id_perfil'];
            $result_insert_user_profile = db_process_sql_insert('tusuario_perfil', $values_user_profile);
        }

        return $user['id_user'];
    }

    $config['auth_error'] = 'User not found in database or incorrect password';

    return false;
}
