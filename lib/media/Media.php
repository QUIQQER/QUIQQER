<?php

/**
* Media-Center
*
* Beinhaltet Grund Funktionen für das Media Center
*
* @category  Media-Center
* @package   com.pcsg.pms.media
*
* @author    PCSG - Henning <leutz@pcsg.de>
* @copyright 2008 PCSG
* @license   http://www.pcsg.net/ PCSG
* @version   SVN: <svn_id>
* @link      http://dev.pcsg.net/package/pms/media
* @since     Class available since Release P.MS 0.1
*/

class Media
{
    private $_project;

    private $_relationtable;
    private $_table;
    private $_TABLE;
    private $_RELTABLE;

    private $_path;
    private $_cache;
    private $_url_cache;

    private $_cmsdir;
    private $_optdir;
    private $_mediaFolder;

    private $_parent_id = false;
    private $_tmp_children;

    protected $_obj_cache = array();

    /**
     * Konstruktor
     *
     * @param \QUI\Projects\Project $project
     */
    public function __construct(\QUI\Projects\Project $Project)
    {
        $name = $Project->getAttribute('name');

        $this->_project     = $Project;
        $this->_cmsdir      = CMS_DIR;
        $this->_optdir      = OPT_DIR;
        $this->_mediaFolder = CMS_DIR .'media/sites/'. $name .'/';

        if (!is_dir($this->_mediaFolder)) {
            \QUI\Utils\System\File::mkdir($this->_mediaFolder);
        }

        $this->_TABLE     = $Project->getAttribute('media_table');
        $this->_RELTABLE  = $this->_TABLE .'_relations';

        $this->_table         = $this->_TABLE; 					// @depricated
        $this->_relationtable = $this->_TABLE .'_relations'; 	// @depricated

        $this->_path      = CMS_DIR .'media/sites/'. $name .'/';
        $this->_cache     = CMS_DIR .'media/cache/'. $name .'/';
        $this->_url_cache = URL_DIR .'media/cache/'. $name .'/';

        // Sites
        \QUI\Utils\System\File::mkdir(CMS_DIR .'media/sites/');
        \QUI\Utils\System\File::mkdir($this->_path);

        // Cache
        \QUI\Utils\System\File::mkdir(CMS_DIR .'media/cache/');
        \QUI\Utils\System\File::mkdir($this->_cache);
    }


    /**
     * Gibt eine Eigenschaft zurück
     *
     * @param String $name
     */
    public function getAttribute($name)
    {
        switch ($name)
        {
            case "cache_dir":
                return $this->_cache;
            break;

            case "url_cache_dir":
                return $this->_url_cache;
            break;

            case "media_dir":
                return $this->_mediaFolder;
            break;

            default:
                return false;
            break;
        }
    }

    /**
     * Gibt das Projekt im Media Objekt zurück
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->_project;
    }

    /**
     * Gibt das erste Kind zurück
     *
     * @return MF_Folder
     */
    public function firstChild()
    {
        return $this->get(1);
    }

    /**
     * Gibt ein passendes Icon zu einer bestimmten Endung zurück
     *
     * @param String $ext - Dateiendung
     * @param String $size - 16x16, 80x80
     * @return String - URL
     */
    static function getIconByExtension($ext, $size)
    {
        $extensions['16x16'] = array(
            'folder' => URL_BIN_DIR .'16x16/extensions/folder.png',
            'none'   => URL_BIN_DIR .'16x16/extensions/none.png',
            'pdf'    => URL_BIN_DIR .'16x16/extensions/pdf.png',

            // Images
            'jpg'  => URL_BIN_DIR .'16x16/extensions/image.png',
            'jpeg' => URL_BIN_DIR .'16x16/extensions/image.png',
            'gif'  => URL_BIN_DIR .'16x16/extensions/image.png',
            'png'  => URL_BIN_DIR .'16x16/extensions/image.png',

            // Movie
            'avi'  => URL_BIN_DIR .'16x16/extensions/film.png',
            'mpeg' => URL_BIN_DIR .'16x16/extensions/film.png',
            'mpg'  => URL_BIN_DIR .'16x16/extensions/film.png',

            // Archiv
            'tar' => URL_BIN_DIR .'16x16/extensions/archive.png',
            'rar' => URL_BIN_DIR .'16x16/extensions/archive.png',
            'zip' => URL_BIN_DIR .'16x16/extensions/archive.png',
            'gz'  => URL_BIN_DIR .'16x16/extensions/archive.png',
            '7z'  => URL_BIN_DIR .'16x16/extensions/archive.png',

            //Office

            // Music
            'mp3' => URL_BIN_DIR .'16x16/extensions/sound.png',
            'ogg' => URL_BIN_DIR .'16x16/extensions/sound.png',
        );

        $extensions['80x80'] = array(
            'folder' => URL_BIN_DIR .'80x80/extensions/folder.png',
            'none'   => URL_BIN_DIR .'80x80/extensions/none.png',
            'pdf'    => URL_BIN_DIR .'80x80/extensions/pdf.png',

            // Images
            'jpg'  => URL_BIN_DIR .'80x80/extensions/image.png',
            'jpeg' => URL_BIN_DIR .'80x80/extensions/image.png',
            'gif'  => URL_BIN_DIR .'80x80/extensions/image.png',
            'png'  => URL_BIN_DIR .'80x80/extensions/image.png',

            // Movie
            'avi'  => URL_BIN_DIR .'80x80/extensions/film.png',
            'mpeg' => URL_BIN_DIR .'80x80/extensions/film.png',
            'mpg'  => URL_BIN_DIR .'80x80/extensions/film.png',

            // Archiv
            'tar' => URL_BIN_DIR .'80x80/extensions/archive.png',
            'rar' => URL_BIN_DIR .'80x80/extensions/archive.png',
            'zip' => URL_BIN_DIR .'80x80/extensions/archive.png',
            'gz'  => URL_BIN_DIR .'80x80/extensions/archive.png',
            '7z'  => URL_BIN_DIR .'80x80/extensions/archive.png',

            //Office

            // Music
            'mp3' => URL_BIN_DIR .'80x80/extensions/sound.png',
        );

        if (isset($extensions[$size][$ext])) {
            return $extensions[$size][$ext];
        }

        if (isset($extensions[$size])) {
            return $extensions[$size]['none'];
        }

        return URL_BIN_DIR .'16x16/extensions/none.png';
    }

    /**
     * Gibt ein passendes Icon zu einer bestimmten Endung zurück
     *
     * @param String $ext - Dateiendung
     * @param String $size - 16x16, 80x80
     * @deprecated Use Media::getIconByExtension
     */
    static function getIconByType($ext, $size)
    {
        return Media::getIconByExtension($ext, $size);
    }

    /**
     * Endung einer Datei bekommen
     *
     * @param String $filename - Dateinamen, kann auch kompletten Pfad enthalten
     * @return String - Dateiendung
     */
    static function getExtension($filename)
    {
        $filename = explode('.', $filename);
        return $filename[ count($filename)-1 ];
    }

    /**
     * Gibt eine neue ID welche noch nicht verwendet wurde
     *
     * @return Integer
     */
    public function getNewId()
    {
        $maxid = \QUI::getDB()->select(array(
            'select' => 'id',
            'from' 	 => $this->_TABLE,
            'limit'  => '0,1',
            'order'  => 'id DESC'
        ));

        $id = (int)$maxid[0]['id'] + 1;

        return $id;
    }

    /**
     * Gibt den MediaType anhand des MimeTypes zurück
     *
     * @param String $mime_type
     * @return String file || image
     */
    static function getMediaTypeByMimeType($mime_type)
    {
        if (strpos($mime_type, 'image/') !== false &&
            strpos($mime_type, 'vnd.adobe') === false)
        {
            return 'image';
        }

        return 'file';
    }

    /**
     * Löscht Zeichen welche im Media-Center nicht erlaubt sind
     *
     * @param String $str
     * @return String
     */
    static function stripMediaName($str)
    {
        $str = preg_replace('/[^0-9a-zA-Z\.\-]/', '_', $str);

        // Umlaute
        $str = str_replace(
            array('ä',  'ö',  'ü'),
            array('ar', 'oe', 'ue'),
            $str
        );

        // Punkte entfernen
        $str_explode = explode('.', $str);
        $str_tmp     = '';

        if (count($str_explode) > 2)
        {
            for ($i = 0, $len = count($str_explode)-1; $i < $len; $i++) {
                $str_tmp .= $str_explode[$i] .'_';
            }

            $str_tmp .= '.'. strtolower($str_explode[count($str_explode)-1]);
            $str      = $str_tmp;

        } elseif (count($str_explode) == 2)
        {
            $str = $str_explode[0] .'.'. strtolower($str_explode[1]);
        }

        // FIX
        $str = preg_replace('/[_]{2,}/', "_", $str);

        return $str;
    }

    /**
     * Prüft ob die URL eine image.php PMS Url ist
     *
     * @param unknown_type $url
     * @return Bool
     */
    static function checkMediaUrl($url)
    {
        if (strpos($url, 'image.php') !== false &&
            strpos($url, 'pms=1') !== false &&
            strpos($url, 'project=') !== false &&
            strpos($url, 'id=') !== false)
        {
            return true;
        }

        return false;
    }

    /**
     * Wandelt eine File Adresse in eine URL um
     *
     * @param unknown_type $url
     */
    static function stripUrl($url)
    {
        return str_replace(CMS_DIR, URL_DIR, $url);
    }

    /**
     * Gibt eine Bild URL zurück die schon die Resize Parameter enthält
     *
     * @param String $url		- php Url des Bildes (index.php / image.php)
     * @param Integer $width	- Breite des Bildes
     * @param Integer $height	- Höhe des Bildes
     *
     * @return String
     */
    public function resizeImageUrl($url, $width=false, $height=false)
    {
        $params = \QUI\Utils\String::getUrlAttributes($url);

        if (!isset($params['pms'])) {
            return $url;
        }

        if (!isset($params['id'])) {
            return $url;
        }

        $Image = $this->get((int)$params['id']);

        if ($Image->getType() != 'IMAGE') {
            return $url;
        }

        return $this->stripUrl($Image->createResizeCache($width, $height));
    }

    /**
     * Wirft ein \QUI\Exception wenn nicht erlaubte Zeichen im String vorkommen
     *
     * @param unknown_type $str
     * @return unknown
     */
    static function checkMediaName($str)
    {
        // Prüfung des Namens - Sonderzeichen
        if (preg_match('/[^0-9_a-zA-Z . -]/', $str))
        {
            throw new \QUI\Exception(
                'Nicht erlaubte Zeichen wurden im Namen "'. $str .'" gefunden. Folgende Zeichen sind erlaubt: 0-9 a-z A-Z . _ -',
                702
            );
        }

        if (strpos($str, '__') !== false)
        {
            throw new \QUI\Exception(
                'Nicht erlaubte Zeichen wurden im Namen gefunden. Doppelte __ dürfen nicht verwendet werden.',
                702
            );
        }

        return true;
    }

    /**
     * Gibt ein Media Objekt zurück
     *
     * @param int $id - ID des DB Eintrages
     * @return MF Object || \QUI\Exception
     */
    public function get($id)
    {
        if ( !is_int( $id ) ) {
            throw new \QUI\Exception('ID '. $id .' ist kein Integer');
        }

        if (isset($this->_obj_cache[$id])) {
            return $this->_obj_cache[$id];
        }

        // Wenn der RAM zuviel wurde Objekte mal leere
        if ( \QUI\Utils\System::memUsageToHigh() ) {
            $this->_obj_cache = array();
        }

        $result = \QUI::getDB()->select(array(
            'from' 	=> $this->_TABLE,
            'where' => array(
                $this->_TABLE .'.id' => $id
            ),
            'limit' => '1'
        ));

        if (!isset($result[0])) {
            throw new \QUI\Exception('ID '. $id .' wurde nicht gefunden', 404);
        }

        $FileObj = $this->_createChildObjects($result[0]);

        if (!$FileObj) {
            throw new \QUI\Exception('ID '. $id .' wurde nicht gefunden', 404);
        }

        $this->_obj_cache[$id] = $FileObj;

        return $this->_obj_cache[$id];
    }

    /**
     * Erstellt ein MF Child Objekt
     *
     * @param Array $params
     * @return MF_Image | MF_Folder | MF_File
     */
    protected function _createChildObjects($params)
    {
        switch ($params['type'])
        {
            case "image":
                $FileObj = new MF_Image($this, $params);
            break;

            case "folder":
                $FileObj = new MF_Folder($this, $params);
            break;

            default:
                $FileObj = new MF_File($this, $params);
            break;
        }

        return $FileObj;
    }

    /**
     * Kinder bekommen
     *
     * @param unknown_type $params
     * @return MC_Children
     */
    public function getChildren($params=array())
    {
        $params['from'] = $this->_TABLE;

        return $this->_parseChildren(
            \QUI::getDB()->select($params)
        );
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $result
     * @return MC_Children
     */
    protected function _parseChildren($result)
    {
        $Children = new MC_Children();

        if (!isset($result[0])) {
            return $Children;
        }

        for ($i = 0, $len = count($result); $i < $len; $i++)
        {
            try
            {
                $Children->add(
                    $this->get((int)$result[$i]['id']),
                    $result[$i]
                );

            } catch (\QUI\Exception $e)
            {
                // nothing
            }
        }

        return $Children;
    }

    /**
     * Gibt den Papierkorb für vom MediaCenter zurück
     *
     * @return MediaTrash
     */
    public function getTrash()
    {
        if (!isset($this->_Trash))
        {
            $Trash = new MediaTrash($this->_project);
            $this->_Trash = $Trash;
        }

        return $this->_Trash;
    }

    /**
     * Media Suche
     *
     * @param String $search - Suchwort
     * @param Array $params  - Suchparameter
     * @return MC_Children
     */
    public function search($search, $params)
    {
        $search = \QUI\Utils\Security\Orthos::clearMySQL($search);
        $query  = 'SELECT * FROM '. $this->_TABLE;
        $where  = ' WHERE ';

        $len = count($params);
        $i   = 0;

        if ($len == 0) {
            $params['name'] = true;
        }

        foreach ($params as $key => $value)
        {
            switch ($key)
            {
                default:
                    continue;
                break;

                case 'name':
                case 'title':
                case 'short':
                case 'mime_type':
                    $where .= ' '. $key .' LIKE "%'. $search .'%"';
                break;

                case 'id':
                    $where .= ' '. $key .' LIKE "%'. (int)$search .'%"';
                break;
            }

            if ($len > ($i+1)) {
                $where .= ' OR ';
            }

            $i++;
        }

        $query .= $where;
        $query .= ' LIMIT 0, 30';

        $result   = \QUI::getDB()->getData($query, 'ARRAY', 'ASSOC');
        $Children = new MC_Children();

        if (!isset($result[0])) {
            return $Children;
        }

        for ($i = 0, $len = count($result); $i < $len; $i++)
        {
            try
            {
                $Children->add(
                    $this->_createChildObjects($result[$i]),
                    $result[$i]
                );

            } catch (\QUI\Exception $e)
            {
                // nothing
            }
        }

        return $Children;
    }
}

?>