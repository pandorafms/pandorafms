<?php
/**
 * @package Include/help/en
 */
?>
<h1>Hierarchy</h1>

<p>
We explained that the permissions of a group can be extended to the children by means of the configuration option <b>Propagate ACL</b>. However, from the user configuration, you can limit this functionality and prevent the ACL from propagating by checking No hierarchy.
</p>

<p>
As a reference for the examples, we propose a configuration with two parent groups "Applications" and "Databases" with two children each, "Development_Apps" and "Management_Apps" for the former and "Databases_America" and "Databases_Asia" for the latter. Both parent groups are marked for ACL to spread.
</p>

<?php
html_print_image(
    'images/help/Acl_hierarchy_groups.png',
    false,
    ['style' => 'max-width:100%'    ]
);
?>

<p>
In the user edit view, if the following profiles are added:
</p>

<?php
html_print_image(
    'images/help/Acl_hierarchy_1.png',
    false,
    ['style' => 'max-width:100%'    ]
);
?>

<p>
The user will have access to the groups named "Applications", "Development_Apps", "Management_Apps" and "Databases".
</p>

<p>
However, if a child of "Databases" is added:
</p>

<?php
html_print_image(
    'images/help/Acl_hierarchy_2.png',
    false,
    ['style' => 'max-width:100%'    ]
);
?>

<p>
Now the user will have access to the groups named "Applications", "Development_Apps", "Management_Apps", "Databases" and "Databases_Asia", but not to "Databases_America".
</p>
