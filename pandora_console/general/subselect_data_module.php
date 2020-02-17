<?php

switch ($_GET['module']) {
    case 1:
        $sql = sprintf(
            'SELECT id_tipo, descripcion
			FROM ttipo_modulo
			WHERE categoria IN (6,7,8,0,1,2,-1) order by descripcion '
        );
    break;

    case 2:
        $sql = sprintf(
            'SELECT id_tipo, descripcion
			FROM ttipo_modulo
			WHERE categoria between 3 and 5
            OR categoria = 10 '
        );
    break;

    case 4:
        $sql = sprintf(
            'SELECT id_tipo, descripcion
			FROM ttipo_modulo
			WHERE categoria between 0 and 2 '
        );
    break;

    case 6:
        $sql = sprintf(
            'SELECT id_tipo, descripcion
			FROM ttipo_modulo
			WHERE categoria between 0 and 2 '
        );
    break;

    case 7:
        $sql = sprintf(
            'SELECT id_tipo, descripcion
			FROM ttipo_modulo
			WHERE categoria = 9'
        );
    break;

    case 5:
        $sql = sprintf(
            'SELECT id_tipo, descripcion
			FROM ttipo_modulo
			WHERE categoria = 0'
        );
    break;

    case '':
        $sql = sprintf(
            'SELECT id_tipo, descripcion
				FROM ttipo_modulo'
        );
    break;
}

        echo '<select id="datatype" name="datatype">';
    echo '<option name="datatype" value="">'.__('All').'</option>';
        $a = db_get_all_rows_sql($sql);

foreach ($a as $valor) {
    echo '<option name="datatype" value="'.$valor['id_tipo'].'">'.$valor['descripcion'].'</option>';
}

        echo '</select>';
