<?php

exit;

/**
 * @deprecated
 */

/* @var $User User */
$Users = \QUI::getUsers();
$User  = $Users->getUserBySession();

if (!$User->getId()) {
    exit;
}

if ($User->isAdmin() == false) {
    exit;
}

/**
 * Sortierungsarten bekommen
 *
 * @param String $project
 * @param String $lang
 * @param String $id
 *
 * @return Array
 */
function ajax_site_get_sorts($project, $lang, $id)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$id);

    $sorts  = $Site->getSorts();
    $result = array();

    foreach ($sorts as $key => $value)
    {
        $result[] = array(
            'sort' => $key,
            'text' => $value
        );
    }

    return $result;
}
$ajax->register('ajax_site_get_sorts', array('project', 'lang', 'id'));

/**
 * Seite suchen
 *
 * @param unknown_type $lang
 * @param unknown_type $project
 * @param unknown_type $params
 * @param unknown_type $limit
 */
function ajax_site_search($lang, $project, $params, $select)
{
    $Project = \QUI::getProject($project, $lang);
    $params  = json_decode($params, true);
    $select  = explode(',', trim($select, ','));

    $result   = array();
    $children = array();

    if (isset($params['id']))
    {
        try
        {
            $children[] = $Project->get((int)$params['id']);
        } catch (\QUI\Exception $e)
        {
            return $result;
        }
    }

    $childs = array();

    foreach ($children as $Child)  /* @var $Child Projects_Site */
    {
        $attributes = array();

        if (empty($select))
        {
            $attributes = $Child->getAllAttributes();
        } else
        {
            foreach ($select as $att) {
                $attributes[$att] = $Child->getAttribute($att);
            }
        }

        $attributes['id'] = $Child->getId();

        if (empty($select) || in_array('has_children', $select)) {
            $attributes['has_children'] = $Child->hasChildren();
        }

        if (empty($select) || in_array('config', $select)) {
            $attributes['config'] = $Child->conf;
        }

        if ($Child->isLinked()) {
            $attributes['linked'] = 1;
        }

        $childs[] = $attributes;
    }

    return $childs;
}
$ajax->register('ajax_site_search', array('lang', 'project', 'params', 'select'));

/**
 * Rekursiv die Parent IDs bekommen
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 * @return Array
 */
function ajax_site_get_parentids($project, $lang, $id)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = $Project->get((int)$id);
    $result  = array();

    if ($Site->getId() == 1) {
        return array(1);
    }

    $parent  = $Site->getParents();

    foreach ($parent as $Parent) {
        $result[] = $Parent->getId();
    }

    return $result;
}
$ajax->register('ajax_site_get_parentids', array('project', 'lang', 'id'));


/**
 * Enter description here...
 *
 * @param unknown_type $id
 * @param unknown_type $lang
 * @param unknown_type $project
 * @param unknown_type $attribute
 */
function ajax_site_get_extra($id, $lang, $project, $field)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$id);

    return $Site->getExtra($field);
}
$ajax->register('ajax_site_get_extra', array('id', 'lang', 'project', 'field'));


/**
 * SuperUser Demarkierung
 *
 * @param String $project
 * @param String $lang
 * @param String $id
 */
function ajax_site_super_user_demarcate($project, $lang, $id)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, $id);

    $Site->demarcateWithRights();
}
$ajax->register('ajax_site_super_user_demarcate', array('project', 'lang', 'id'));

/**
 * Speichert die Einträge vom Temp in die DB
 *
 * @param unknown_type $id
 * @param unknown_type $lang
 * @param unknown_type $project_name
 * @return unknown
 */
function ajax_site_saveFromTemp($id, $lang, $project_name)
{
    try
    {
        $Project = \QUI::getProject($project_name, $lang);
        $Site    = $Project->get($id);

        return $Site->save();

    } catch (\QUI\Exception $e)
    {
        switch( $e->getCode() )
        {
            case 701:
                throw new \QUI\Exception(
                    'Der Name einer Seite muss mehr als 2 Zeichen betragen. <br />'.
                    'Bitte ändern Sie den Namen und speichern erneut. <br />',
                    701
                );
            break;

            case 702:
                throw new \QUI\Exception(
                    'Es wurden Sonderzeichen im Name gefunden die nicht erlaubt sind.'.
                    'Bitte nehmen Sie alle Sonderzeichen aus dem Namen und speichern erneut.<br />'.
                    '<span style="font-size: 10px">Folgende Zeichen sind nicht erlaubt:<br /> - . , ; ` # ! § $ % & / ? < > = \' [ ] +"</span>',
                    701
                );
            break;

            case 703:
                throw new \QUI\Exception(
                    'Eine Seite mit dem gleichen Namen existiert bereits in der selben Ebene.<br />'.
                    'Bitte ändern Sie den Namen und speichern erneut.',
                    703
                );
            break;

            case 704:
                throw new \QUI\Exception(
                    'Der Name einer Seite darf nicht mehr als 200 Zeichen betragen. <br />'.
                    'Bitte ändern Sie den Namen und speichern erneut. <br />',
                    704
                );
            break;

            default:
                throw new \QUI\Exception(
                    $e->getMessage(),
                    $e->getCode()
                );
            break;
        }
    }
}
$ajax->register('ajax_site_saveFromTemp', array('id', 'lang', 'project_name'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $pid
 * @param unknown_type $id
 * @return unknown
 */
function ajax_site_move($project, $lang, $pid, $id)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$id);

    return $Site->move($pid) ? 1 : 0;
}
$ajax->register('ajax_site_move', array('project', 'lang', 'pid', 'id'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $pid
 * @param unknown_type $id
 * @return unknown
 */
function ajax_site_copy($project, $lang, $pid, $id)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$id);

    return $Site->copy($pid);
}
$ajax->register('ajax_site_copy', array('project', 'lang', 'pid', 'id'));

/**
 * Erstellt eine Verknüpfung
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $pid
 * @param unknown_type $id
 * @return Bol
 */
function ajax_site_linked($project, $lang, $pid, $id)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$id);

    return $Site->linked($pid);
}
$ajax->register('ajax_site_linked', array('project', 'lang', 'pid', 'id'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $id
 * @param unknown_type $ids
 */
function ajax_site_linked_in($project, $lang, $id, $ids)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$id);
    $parents = json_decode($ids, true);

    // Schaun ob es den Namen im Kind schon gibt
    // Falls ja dann raus
    foreach ($parents as $pid)
    {
        $Parent = $Project->get((int)$pid);

        try
        {
            $Child = $Parent->getChildIdByName(
                $Site->getAttribute('name')
            );
        } catch (\QUI\Exception $e)
        {
            // es wurde kein Kind gefunden
            $Child  = false;
        }

        if ($Child)
        {
            $parents   = $Parent->getParents();
            $parents[] = $Parent;

            $path    = '';

            foreach ($parents as $Prt) {
                $path .= '/'. $Prt->getAttribute('name');
            }

            // Es wurde ein Kind gefunde
            throw new \QUI\Exception(
                'Eine Seite mit dem Namen '. $Site->getAttribute('name') .' befindet sich schon unter '. $path
            );
        }

        $Child = false;
    }

    foreach ($parents as $pid) {
        $Site->linked((int)$pid);
    }

    return true;
}
$ajax->register('ajax_site_linked_in', array('project', 'lang', 'id', 'ids'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $pid
 * @param unknown_type $id
 * @param unknown_type $delorig
 */
function ajax_site_delete_linked($project, $lang, $pid, $id, $delorig)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$id);

    return $Site->deleteLinked($pid, $delorig);
}
$ajax->register('ajax_site_delete_linked', array('project', 'lang', 'pid', 'id', 'delorig'));

/**
 * Enter description here...
 *
 * @param unknown_type $project
 * @param unknown_type $lang
 * @param unknown_type $ids
 */
function ajax_site_delete_onlylinked($project, $lang, $ids)
{
    $ids = explode(',', $ids);

    if (!isset($ids[2])) {
        throw new \QUI\Exception('Es wurden nicht alle Parameter übermittelt');
    }

    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, (int)$ids[1]);

    return $Site->deleteLinked($ids[0], false, $ids[2]);
}
$ajax->register('ajax_site_delete_onlylinked', array('project', 'lang', 'ids'));


/**
 * Rechte rekursiv setzen
 *
 * @param String $project
 * @param String $lang
 * @param String $id
 * @param String $rights
 */
function ajax_site_rights_recursive($project, $lang, $id, $rights)
{
    $Project = \QUI::getProject($project, $lang);
    $Site    = new Projects_Site_Edit($Project, $id);

    $Rights = \QUI::getRights();
    $rights = json_decode($rights, true);

    try
    {
        if (is_array($rights))
        {
            $children = $Site->getChildren(array(), true);

            foreach ($children as $Child) {
                $Rights->setRightsFromSite($Child, $rights);
            }
        }

        return true;
    } catch (\QUI\Exception $e)
    {
        return false;
    }
}
$ajax->register('ajax_site_rights_recursive', array('project', 'lang', 'id', 'rights'));

/**
 * Gibt das Blatt zurück auf welchem sich die Seite befindet
 *
 * @param String $project
 * @param String $lang
 * @param String $parentid
 * @param String $id
 * @return String
 */
function ajax_site_getsheet($project, $lang, $parentid, $id)
{
    $Project  = \QUI::getProject($project, $lang);
    $Parent   = $Project->get($parentid);

    $childids = $Parent->getChildrenIds($parentid);
    $sheet    = $Project->getConfig('sheets');

    if ($sheet == false) {
        $sheet = 10;
    }

    $c = 0;

    for ($i = 0, $len = count($childids); $i < $len; $i++)
    {
        if (($i % $sheet)) { // Seite suchen
            $c++;
        }

        if ($id == $childids[$i]) {
            break;
        }
    }

    return $c;
}
$ajax->register('ajax_site_getsheet', array('project', 'lang', 'parentid', 'id'));

/**
 * Such Template für Seiten
 *
 * @return String
 */
function ajax_site_search_template()
{
    $Engine = QUI_Template::getEngine(true);

    $Engine->assign(array(
        'projects' => Projects_Manager::getProjects(true)
    ));

    return $Engine->fetch(SYS_DIR .'template/site_search.html');
}
$ajax->register('ajax_site_search_template');

/**
 * Seiten Suche
 *
 * @param unknown_type $search
 * @param unknown_type $params
 */
function ajax_site_search_window($project, $search, $params)
{
    $params = json_decode($params, true);

    if (!isset($params['project'])) {
        return array();
    }

    if (!isset($params['lang'])) {
        $params['lang'] = false;
    }

    try
    {
        $Project = \QUI::getProject($params['project'], $params['lang']);
        $presult = $Project->search($search);

        $result = array();

        foreach ($presult as $Site)
        {
            $icon = URL_BIN_DIR . '16x16/page_white.png';

            if (isset($Site->conf['icon_16x16'])) {
                $icon = URL_OPT_DIR . $Site->conf['icon_16x16'];
            }

            $att = array(
                'id'      => $Site->getId(),
                'name'    => $Site->getAttribute('name'),
                'title'   => $Site->getAttribute('title'),
                'rurl'    => $Site->getUrlRewrited(),
                'url'     => $Site->getUrl(),
                'icon'    => '<img src="'. $icon .'">',
                'type'    => $Site->getAttribute('type'),
                'project' => $Project->getAttribute('name'),
                'lang'    => $Project->getAttribute('lang')
            );
            $result[] = $att;
        }

        return $result;

    } catch (\QUI\Exception $e)
    {
        \QUI\System\Log::writeException($e);
        return array();
    }
}
$ajax->register('ajax_site_search_window', array('project', 'search', 'params'));

?>