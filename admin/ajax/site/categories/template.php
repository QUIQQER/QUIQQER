<?php

/**
 * Tab Inhalt bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 *
 * @return String
 */
function ajax_site_categories_template($project, $lang, $id, $tab)
{
	$Project = QUI::getProject( $project, $lang );
	$Site    = new Projects_Site_Edit( $Project, (int)$id );

	return Utils_String::removeLineBreaks(
	    Utils_Dom::getTabHTML( $tab, $Site )
	);

	// template hohlen
	/*
	if ( isset( $attr['tpl'] ) )
	{
        $tpl    = Utils_Security_Orthos::clearPath( $attr['tpl'] );
        $Engine = QUI_Template::getEngine(true);

    	if ( !file_exists( $tpl ) ) {
    		throw new QException( 'Template nicht gefunden' );
    	}

        $Engine->assign(array(
    		'Site'    => $Site,
    		'Project' => $Project,
    		'Groups'  => QUI::getGroups()
    	));

    	if (strpos($tpl, 'information') !== false)
    	{
    		$Plugins   = $Project->getPlugins();
    		$extrahtml = '';

    		foreach ($Plugins as $Plugin)
    		{
    			$folder = OPT_DIR . $Plugin->getAttribute('name') .'/';

    			if (file_exists($folder .'admin/information.html')) {
    				$extrahtml .= $Engine->fetch($folder .'admin/information.html');
    			}
    		}

    		$Engine->assign('extrahtml', $extrahtml);
    	}

    	// Tempfile Attribute laden
    	$attr['tpl'] = Utils_String::removeLineBreaks(
    		$Engine->fetch($tpl)
    	);
	}

	return $attr;
	*/
}

QUI::$Ajax->register(
	'ajax_site_categories_template',
    array('project', 'lang', 'id', 'tab'),
    'Permission::checkAdminUser'
);

?>