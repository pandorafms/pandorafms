<?php
/**
 * @package Include/help/en
 */
?>
<h1>Jerarquía</h1>

<p>
Los permisos de un grupo se pueden extender a los hijos mediante la opción de configuración <b>Propagate ACL</b>. Sin embargo, desde la configuración de usuarios, se puede limitar esta funcionalidad y evitar que el ACL se propague marcando No hierarchy.
</p>

<p>
Como referencia para los ejemplos, se plantea una configuración con dos grupos padre "Applications" y "Databases" con dos hijos cada uno, "Development_Apps" y "Management_Apps" para el primero y "Databases_America" y "Databases_Asia" para el segundo. Ambos grupos padre están marcados para que se propague el ACL.
</p>

<?php
html_print_image(
    'images/help/Acl_hierarchy_groups.png',
    false,
    ['style' => 'max-width:100%'    ]
);
?>

<p>
En la vista de edición de usuario, si se añaden los siguientes perfiles:
</p>

<?php
html_print_image(
    'images/help/Acl_hierarchy_1.png',
    false,
    ['style' => 'max-width:100%'    ]
);
?>

<p>
El usuario tendrá acceso a los grupos "Applications", "Development_Apps", "Management_Apps" y "Databases".
</p>

<p>
En cambio, si se añade un hijo de "Databases":
</p>

<?php
html_print_image(
    'images/help/Acl_hierarchy_2.png',
    false,
    ['style' => 'max-width:100%'    ]
);
?>

<p>
Ahora el usuario podrá acceder a los grupos "Applications", "Development_Apps", "Management_Apps", "Databases" y "Databases_Asia", pero no a "Databases_America".
</p>
