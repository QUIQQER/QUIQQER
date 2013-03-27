<?php

function ajax_site_permissions_tpl()
{
    $Engine   = QUI_Template::getEngine( true );
    $template = SYS_DIR .'template/site/permissions.html';

    if ( !file_exists( $template ) )
    {
        throw new QException(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.template.not.found'
            )
        );
    }

    $permissions = array();

    $Manager = \QUI::getPermissionManager();
    $list    = $Manager->getPermissionList( 'site' );

    foreach ( $list as $entry )
    {
        $Permission = new QDOM();
        $Permission->setAttributes(array(
            'name'  => $entry['name'],
            'title' => \QUI::getLocale()->get(
                'locale/permissions',
                $entry['name'] .'._title'
            ),
            'type'  => $entry['type']
        ));

        if (
            \QUI::getLocale()->exists(
                'locale/permissions',
                $entry['name'] .'._description'
            )
        )
        {
            $Permission->setAttribute('description', \QUI::getLocale()->get(
                'locale/permissions',
                $entry['name'] .'._description'
            ));
        }

        $permissions[] = $Permission;
    }


    $Engine->assign(array(
        'permissions' => $permissions
    ));

    return $Engine->fetch( $template );
}

QUI::$Ajax->register(
    'ajax_site_permissions_tpl',
    false,
    'Permission::checkAdminUser'
);

?>