<?php

/**
 * MF_Folder
 * Ordner im Media Center
 *
 * @author PCSG - Henning
 * @package com.pcsg.pms.media
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4722 $
 * @since      Class available since Release P.MS 0.9
 *
 * @errorcodes
 * <ul>
 * <li>506	- createCache() Error; Ordner konnte nicht angelegt werden</li>
 * <li>507	- createFolder() Error; Ordner konnte auf dem Filesystem nicht angelegt werden</li>
 * <li>508	- upload() Error; Temporäre Datei konnte nicht kopiert werden</li>
 *
 * <li>600	- activate() Error; Aktivierung in der Datenbank fehlgeschlagen</li>
 * <li>601	- deactivate() Error; Deaktivierung in der Datenbank fehlgeschlagen</li>
 * <li>602	- destroy() Error; Zerstörung in der Datenbank fehlgeschlagen</li>
 *
 * <li>701	- createFolder() Error; Ordner existiert bereits</li>
 * <li>702	- restore() Error; Wiederherstellungs Fehler; In dem Elternordner gibt es schon einen Ordner mit dem gleichen Namen</li>
 * <li>703	- restore() Error; Update bei Wierherstellung fehlgeschlagen</li>
 * <li>704	- destroy() Error; Ordner ist noch aktiv und kann nicht zerstört werden. Ordner müssen vorher deaktiviert sein</li>
 * <li>705	- upload() Error; Datei existiert bereits</li>
 * <li>706	- uploadArchive() Error; Im Archiv sind keine Dateien</li>
 * <li>707	- uploadArchive() Error; Das Archiv ist kein ZIP</li>
 * <li>708	- uploadArchive() Error; Nicht alle Ordner konnten erstellt werden</li>
 * </ul>
 */

class MF_Folder extends MediaFile implements iMF
{
	protected $_TYPE = 'FOLDER';
	protected $_tmp_children;

	/**
	 * Gibt den Typ des Objektes zurück
	 * @return String "FOLDER"
	 */
	public function getType()
	{
		return $this->_TYPE;
	}

	/**
	 * Aktiviert den Ordner
	 * @return true || throw \QUI\Exception
	 */
	public function activate()
	{
		// DB setzen
		$r_db = QUI::getDB()->updateData(
			$this->_TABLE,
			array('active' => 1),
			array('id'     => $this->getId())
		);

		if (!$r_db) {
			throw new \QUI\Exception('Aktivierung des Ordners mit ID '. $this->getId() .' Fehlgeschlagen', 600);
		}

		$this->setAttribute('active', 1);

		// Cacheordner erstellen
		$this->createCache();
		return true;
	}

	/**
	 * Deaktiviert den Ordner und die darunter liegenden Sachen
	 * @return true || throw \QUI\Exception
	 */
	public function deactivate()
	{
		// DB setzen
		$r_db = QUI::getDB()->updateData(
			$this->_TABLE,
			array('active' => 0),
			array('id' => $this->getId())
		);

		if (!$r_db)
		{
			throw new \QUI\Exception(
				'Deaktivierung des Ordners mit der ID '. $this->getId() .' fehlgeschlagen',
				601
			);
		}

		$this->setAttribute('active', 0);

		// Bilder / Ordner / Dateien rekursiv deaktivieren
		$this->_tmp_children = array();
		$this->_getChildrenRec( $this->getId() );

		foreach ($this->_tmp_children as $child)
		{
			$r = QUI::getDB()->updateData(
				$this->_TABLE,
				array('active' => 0),
				array('id'     => (int)$child)
			);
		}

		$this->deleteCache();

		return true;
	}

	/**
	 * Gibt die Attribute als Array zurück
	 *
	 * @return Array
	 */
	public function toArray()
	{
		$attributes = $this->_attributes;
		$attributes['url']          = $this->getUrl();
		$attributes['has_children'] = $this->hasChildren();

		return $attributes;
	}

	/**
	 * Löscht den Ordner und die darin liegenden Dateien / Ordner
	 */
	public function delete()
	{
		if ($this->getId() == 1) {
			return false;
		}

		$children = $this->_getChildrenRec( $this->getId() );
		$Media    = $this->_Media; /* @var $Media Media */

		for ($i = 0, $len = count($children); $i < $len; $i++)
		{
			$Child = $Media->get( (int)$children[$i] );

			// Nur Files ins TMP verschieben
			if ($Child->getType() != 'FOLDER')
			{
				$Child->delete();
			} else
			{
				// Folder in der DB deaktivieren
				$u_del = QUI::getDB()->updateData(
					$this->_TABLE,
					array(
						'deleted' => 1,
						'active'  => 0
					),
					array('id' => $Child->getId())
				);

				// Eltern Beziehung aufgeben
				QUI::getDB()->deleteData(
					$this->_RELTABLE,
					array('child' => $Child->getId())
				);
			}
		}

		// Update für die DB
		$u_del = QUI::getDB()->updateData(
			$this->_TABLE,
			array(
				'deleted' => 1,
				'active'  => 0
			),
			array('id' => $this->getId())
		);

		// Eltern Beziehung aufgeben
		QUI::getDB()->deleteData(
			$this->_RELTABLE,
			array('child' => $this->getId())
		);

		// Cache löschen
		$this->deleteCache();

		$folder = $this->_Media->getAttribute('media_dir').$this->getAttribute('file');

		if (is_dir($folder)) {
			\QUI\Utils\System\File::unlink($folder);
		}
	}

	/**
	 * Cachedatei löschen
	 * @return Bool || throw \QUI\Exception
	 */
	public function deleteCache()
	{
		// Cacheordner löschen
		$folder = $this->_Media->getAttribute('cache_dir').$this->getAttribute('file');

		if (is_dir($folder)) {
			\QUI\Utils\System\File::unlink($folder);
		}

		return true;
	}

	/**
	 * Ordner wiederherstellen
	 * @param MF_Folder $Parent - Unter welcher der Ordner wieder eingehängt werden soll
	 * @return Bool || throw \QUI\Exception
	 */
	public function restore(MF_Folder $Parent)
	{
		if ($this->getAttribute('deleted') == 0) {
			return true;
		}

		$Media      = $this->_Media;
		$dir        = $Media->getAttribute('media_dir');
		$new_folder = $Parent->getAttribute('file').$this->getAttribute('name').'/';

		if (file_exists($dir.$new_folder))
		{
			throw new \QUI\Exception(
				'Restore Error; Ein Ordner mit dem gleichen Namen existiert bereits in diesem Ordner',
				702
			);
		}

		// DB aktualisieren
		$u_db = QUI::getDB()->updateData(
			$this->_TABLE,
			array(
				'deleted' => 0,
				'active'  => 0,
				'file' 	  => $new_folder
			),
			array('id' => $this->getId())
		);

		// Relation wieder herstellen
		$u_parent_db = QUI::getDB()->addData(
			$this->_RELTABLE,
			array(
				'parent' => $Parent->getId(),
				'child'  => $this->getId()
			)
		);

		if ($u_db && $u_parent_db) {
			return true;
		}

		throw new \QUI\Exception(
			'Update DB Error MF_File->restore()',
			703
		);
	}

	/**
	 * Umbennenen eines Ordners
	 *
	 * @param String $name
	 * @return Bool || throw \QUI\Exception
	 */
	public function rename($name)
	{
		$Media  = $this->_Media; /* @var $Media Media */
		$Parent = $this->getParent();

		$name   = $Media->stripMediaName($name);

		// Namen prüfen
		$Media->checkMediaName($name);

		// Alte Datei
		$file     = $this->getAttribute('file');
		$newfile  = $Parent->getAttribute('file') . $name;

		$org_path = \QUI\Utils\String::removeLastSlash( $Media->getAttribute('media_dir') . $file );
		$new_path = \QUI\Utils\String::removeLastSlash( $Media->getAttribute('media_dir') . $newfile );

		if ($org_path == $new_path) {
			return true;
		}

		if (file_exists($new_path))
		{
			throw new \QUI\Exception(
				'Der Ordner kann nicht umbenannt werden da ein Ordner mit dem gleichen Namen existiert.'
				.' :: '. $org_path .' => '. $new_path
			);
		}

		$result = QUI::getDB()->select(array(
			'from'  => $this->_TABLE,
			'where' => 'file LIKE "'. $file .'/%"'
		));

		foreach ($result as $_file)
		{
			$id = $_file['id'];

			$_oldfile = $_file['file'];
			$_newfile = str_replace($file, $newfile, $_oldfile);

			QUI::getDB()->updateData(
				$this->_TABLE,
				array('file' => $_newfile),
				array('id'   => $id)
			);
		}

		// Standard Felder ändern
		QUI::getDB()->updateData(
			$this->_TABLE,
			array(
				'name' => $name,
				'file' => $newfile
			),
			array('id' => $this->getId())
		);

		\QUI\Utils\System\File::move($org_path, $new_path);
		$this->deleteCache();
	}

	/**
	 * Zerstört den Ordner
	 */
	public function destroy()
	{
		if ($this->getAttribute('active') == 1)
		{
			throw new \QUI\Exception(
				'Der Ordner ist aktiv, bitte deaktivieren Sie den Ordner um diesen zu löschen',
				704
			);
		}

		$id = $this->getId();

		// Update für die DB
		$u_des = QUI::getDB()->deleteData(
			$this->_TABLE,
			array('id' => $id)
		);

		// Eltern Beziehungen löschen
		$c_des = QUI::getDB()->deleteData(
			$this->_RELTABLE,
			array('child' => $id)
		);

		$p_des = QUI::getDB()->deleteData(
			$this->_RELTABLE,
			array('parent' => $id)
		);


		if ($u_des && $c_des && $p_des)
		{
			// Eigenschaft im Object selbst löschen
			$this->_attributes = array();
			$this->_id         = false;
			$this->_pid        = false;

			return true;
		}

		throw new \QUI\Exception('destroy error; ID '. $id, 602);
	}

	/**
	 * Children rekursiv durchgehen
	 * Setzt children in $this->_getChildrenRec
	 * @param int $id
	 */
	protected function _getChildrenRec($id)
	{
		// Wegen Performance eigener SQL Statement
		$children = QUI::getDB()->select(array(
			'from' 	=> $this->_RELTABLE,
			'where' => array(
				'parent' => (int)$id
			)
		));

		foreach ($children as $child)
		{
			$this->_getChildrenRec($child['child']);
			$this->_tmp_children[] = $child['child'];
		}

		return $this->_tmp_children;
	}

	/**
	 * Erstellt einen Unterordner
	 *
	 * @param String $new_name - Name für den neuen Ordner
	 * @return MF_Folder || throw \QUI\Exception
	 */
	public function createFolder($new_name=false)
	{
		$Users = QUI::getUsers();
		$User  = $Users->getUserBySession();
		$Media = $this->_Media; /* @var $Media Media */

		// Namensprüfung wegen unerlaubten Zeichen
		$Media->checkMediaName($new_name);

		if (strpos($new_name, '.') !== false)
		{
			throw new \QUI\Exception(
				'Nicht erlaubte Zeichen wurden im Namen gefunden. Punkte in Ordnernamen dürfen nicht verwendet werden.',
				702
			);
		}

		// Whitespaces am Anfang und am Ende rausnehmen
		$new_name = $Media->stripMediaName($new_name);
		$new_name = trim($new_name);

		$newid  = $this->_Media->getNewId();
		$dir    = $this->_Media->getAttribute('media_dir').$this->getAttribute('file');
		$Parent = $this->getParent();

		if (is_dir($dir.$new_name)) {
			throw new \QUI\Exception('Der Ordner existiert schon ' . $dir . $new_name, 701);
		}

		\QUI\Utils\System\File::mkdir($dir.$new_name);

		// In die DB legen
		QUI::getDB()->addData($this->_TABLE, array(
			'id' 	=> $newid,
			'name' 	=> $new_name,
			'title' => $new_name,
			'short' => $new_name,
			'type' 	=> 'folder',
			'file' 	=> $this->getAttribute('file').$new_name.'/',
			'alt' 	=> $new_name,
			'c_date' => date('Y-m-d h:i:s'),
			'e_date' => date('Y-m-d h:i:s'),
			'c_user' => $User->getId(),
			'e_user' => $User->getId(),
			'mime_type' => 'folder',

		    'watermark'    => $this->getAttribute('watermark'),
		    'roundcorners' => $this->getAttribute('roundcorners')
		));

		QUI::getDB()->addData($this->_RELTABLE, array(
			'parent' => $this->getId(),
			'child'  => $newid
		));

		if (is_dir($dir.$new_name)) {
			return $this->_Media->get( $newid );
		}

		throw new \QUI\Exception(
			'Der Ordner konnte nicht erstellt werden',
			507
		);
	}

	/**
	 * Ein File in den Mediabereich laden
	 *
	 * @param Array $FILE
	 * <ul>
	 * 		<li>$FILE['name'] = Name des Files</li>
	 * 		<li>$FILE['tmp_name'] = Pfad zur Datei damit diese kopiert werden kann</li>
	 * </ul>
	 * @todo return MF_File || throw \QUI\Exception
	 */
	public function upload($FILE)
	{
		$Users = QUI::getUsers();
		$User  = $Users->getUserBySession();
		$Media = $this->_Media;	/* @var $Media Media  */
		$dir   = $Media->getAttribute('media_dir').$this->getAttribute('file').'/';
		$dir   = str_replace('//', '/', $dir);

		$filename = $Media->stripMediaName($FILE["name"]);

		if (file_exists($dir.$filename)) {
			throw new \QUI\Exception($dir.$filename ." existiert bereits", 705);
		}

		if (!move_uploaded_file($FILE["tmp_name"], $dir.$filename))
		{
			if (!copy($FILE["tmp_name"], $dir.$filename)) {
				throw new \QUI\Exception('Kann Datei nicht in den Zielordner verschieben', 508);
			}
		}

		$newid = $Media->getNewId();
		$info  = \QUI\Utils\System\File::getInfo($dir.$filename);

		// In die DB legen
		QUI::getDB()->addData($this->_TABLE, array(
			'id' 	=> $newid,
			'name' 	=> $filename,
			'title' => $info['filename'],
			'short' => $info['filename'],
			'file' 	=> str_replace($Media->getAttribute('media_dir'), '', $dir.$filename),
			'alt' 	=> $info['filename'],
			'c_date' 	=> date('Y-m-d H:i:s'),
			'c_user'    => $User->getId(),
			'e_date' 	=> date('Y-m-d H:i:s'),
			'e_user'    => $User->getId(),
			'mime_type' => $info['mime_type'],
			'type' 		=> $Media->getMediaTypeByMimeType( $info['mime_type'] ),
			'image_height'	=> $info['height'],
			'image_width' 	=> $info['width']
		));

		// Relation festlegen
		QUI::getDB()->addData($this->_RELTABLE, array(
			'parent' => $this->getId(),
			'child'  => $newid
		));

		$Obj = $Media->get( $newid ); /* @var $Obj MF_File */

		if ($Obj->getType() == 'IMAGE')
		{
    		$Obj->setAttribute('watermark', $this->getAttribute('watermark'));
    		$Obj->setAttribute('roundcorners', $this->getAttribute('roundcorners'));
		}

		$Obj->activate();
		$Obj->save();

		return $Obj;
	}

	/**
	 * Ein Archiv hochladen und gleich entpacken
	 *
	 * @param unknown_type $FILE
	 * @return unknown
	 */
	public function uploadArchive($FILE)
	{
		$Media = $this->_Media;	/* @var $Media Media  */

		// alles vorbereiten
		$tmp_file = VAR_DIR .'tmp/'. str_replace( array(' ', '.'), '', microtime()) .'.zip';

		if (!move_uploaded_file($FILE["tmp_name"], $tmp_file)) {
			copy($FILE["tmp_name"], $tmp_file);
		}

		// Archiv entpacken
		$files = array();

		if (strpos($FILE["type"], 'zip') === false &&
			strpos($FILE["type"], 'octet-stream') === false)
		{
			throw new \QUI\Exception(
				'uploadArchive() Error; NO ZIP',
				707
			);
		}

		// @todo check unzip -> Utils_Packer_Zip -> war vorher PCSG_ZIP
		$zip = new Utils_Packer_Zip();

		$tmp_folder = str_replace('.zip', '', $tmp_file);
		$tmp_folder = $tmp_folder.'/';

		\QUI\Utils\System\File::mkdir($tmp_folder);
		$zip->unzip($tmp_file, $tmp_folder);

		$PT_File = new \QUI\Utils\System\File();
		$files   =  $PT_File->readDirRecursiv($tmp_folder);

		if (count($files) <= 0) {
			throw new \QUI\Exception('No Files in Archiv', 706);
		}

		$dir = $Media->getAttribute('media_dir').$this->getAttribute('file');

		// Ordnerstruktur erstellen und Bilder einfügen
		$tmp_path = '';

		foreach ($files as $path => $file)
		{
			if ($tmp_path != $path)
			{
				$Folder = $this; /* @var $Folder MF_Folder */

				if ($path != '/' && $path != '')
				{
					try
					{
						$Folder = $this->_createPath( $path );

					} catch (\QUI\Exception $e)
					{
						// 701 = Folder exists
						if ($e->getCode() == 701)
						{
							// Ordner herausfinden
							$explode = explode('/', $path);

							if (!end($explode) || end($explode) == '') {
								unset( $explode[ count($explode)-1 ] );
							}

							foreach ($explode as $foldername)
							{
								$Children = $Folder->getChildren('FOLDER', 'name', $foldername);
								$Folder   = $Children->get(0);
							}
						} elseif ($e->getCode() == 708)
						{
							// Wenn der Pfad nicht erstellt werden konnte
							// @todo Muss noch als Fehler ausgegeben werden
							continue;
						}
					}
				}
			}

			if (is_array($file))
			{
				foreach ($file as $f)
				{
					if ($path == '/') {
						$path = '';
					}

					$FILE = array(
						"name"     => $f,
						"tmp_name" => $tmp_folder.$path.$f
					);

					try
					{
						$Folder->upload($FILE);
					} catch (\QUI\Exception $e)
					{
						if ($e->getCode() == 705)
						{
							// File existiert schon
							// @todo dies dann aufnehmen und ausgeben
						}

						//@todo für ajax noch error raussenden
					}
				}
			}

			$tmp_path = $path;
		}
	}

	/**
	 * Erstellt ein übergebenen Pfad in dem Ordner
	 *
	 * @param unknown_type $path
	 * @return Folder (letzter Folder) || throw \QUI\Exception
	 */
	private function _createPath($path)
	{
		if ($path == '/') {
			return true;
		}

		$path   = explode('/', trim($path, '/'));
		$Parent = $this; /* @var $Folder MF_Folder */

		foreach ($path as $folder)
		{
			try
			{
				$Folder = $Parent->createFolder($folder);
			} catch (\QUI\Exception $e)
			{
				$Children = $Parent->getChildren('FOLDER', 'name', $folder);
				$Folder   = $Children->get(0);
			}

			if (!is_object($Folder)) {
				throw new \QUI\Exception('Could not create the Path', 708);
			}

			$Parent = $Folder;
		}

		return $Folder;
	}

	/**
	 * Erstellt den Cacheordner
	 * @return Bool || throw \QUI\Exception
	 */
	public function createCache()
	{
		if (!$this->getAttribute('active')) {
			return true;
		}

		$cache_dir = $this->_Media->getAttribute('cache_dir').$this->getAttribute('file');

		if (\QUI\Utils\System\File::mkdir($cache_dir)) {
			return true;
		}

		throw new \QUI\Exception(
			'createCache() Error; Could not create Folder '. $cache_dir,
			506
		);
	}

	/**
	 * Gibt die Kinder als Objekte zurück
	 *
	 * @param String $type  - IMAGE (nur Bilder); FOLDER (nur Ordner); FILE (nur Dateien); default false, gibt alle Typen zurück;
	 * @param String $order - Sortierungsfeld
	 * @param String $name  - bestimmten Namen suchen
	 * @return MC_Children
	 */
	public function getChildren($type=false, $order='name', $name=false, $only_active=false)
	{
		$fields   = $this->getChildrenData($type, $order, $name, $only_active);
		$children = new MC_Children();

		foreach ($fields as $field)
		{
			$c_id	= (int)$field['id'];
			$result = QUI::getDB()->select(array(
				'from' 	 => $this->_RELTABLE,
				'where'  => array(
					'parent' => $c_id
				)
			));

			$field['has_children'] = count($result); // nicht optimal gelöst

			$children->add( $this->_Media->get( $c_id ), $field );
		}

		return $children;
	}

	/**
	 * Gibt die Daten der Kinder zurück - Keine Objekte
	 *
	 * @param String $type - IMAGE (nur Bilder); FOLDER (nur Ordner); FILE (nur Dateien); default false, gibt alle Typen zurück;
	 * @param String $order - Sortierungsfeld
	 * @param String $name - bestimmten Namen suchen
	 * @return Array
	 */
	public function getChildrenData($type=false, $order='name', $name=false, $only_active=false)
	{
		$sql =   $this->_RELTABLE.'.parent='.$this->getId().' AND '
				.$this->_RELTABLE.'.child='.$this->_TABLE.'.id AND '
				.$this->_TABLE.'.deleted = 0';

		// bestimmte Typen zurück geben
		switch ($type)
		{
			case "IMAGE":
				$sql .= ' AND '.$this->_TABLE.'.type = "image"';
			break;

			case "FOLDER":
				$sql .= ' AND '.$this->_TABLE.'.type = "folder"';
			break;

			case "FILE":
				$sql .= ' AND '.$this->_TABLE.'.type = "file"';
			break;
		}

		// Sortierung
		$order_by = false;

		switch ($order)
		{
			case 'c_date':
				$order_by = 'find_in_set('.$this->_TABLE.'.type, \'folder\') DESC, '.$this->_TABLE.'.c_date';
			break;

			case 'c_date DESC':
				$order_by = 'find_in_set('.$this->_TABLE.'.type, \'folder\') DESC, '.$this->_TABLE.'.c_date DESC';
			break;

			default:
			case 'name':
				$order_by = 'find_in_set('.$this->_TABLE.'.type, \'folder\') DESC, '.$this->_TABLE.'.name';
			break;
		}

		// Nach bestimmten Namen suchen
		if ($name) {
			$sql .= ' AND '.$this->_TABLE.'.name = "'.$name.'"';
		}

		if ($only_active) {
			$sql .= ' AND '.$this->_TABLE.'.active = 1';
		}

		$params['from'] = array(
			$this->_RELTABLE,
			$this->_TABLE
		);

		$params['where'] = $sql;
		$params['order'] = $order_by;

		$result = QUI::getDB()->select($params);

		return $result;
	}

	/**
	 * Gibt die Anzahl der Kinder zurück
	 * @return Integer
	 */
	public function hasChildren()
	{
		if ($this->getAttribute('has_children')) {
			return (int)$this->getAttribute('has_children');
		}

		$children = $this->getChildrenData();
		$this->setAttribute('has_children', count($children));

		return count($children);
	}

	/**
	 * Gibt das erste Kind zurück
	 *
	 * @param String $type - IMAGE (nur Bilder); FOLDER (nur Ordner); FILE (nur Dateien); default false, gibt alle Typen zurück;
	 * @return unknown
	 */
	public function firstChild($type=false)
	{
		$result = $this->getChildrenData($type, 'name', false, array(
			'select' => 'id',
			'limit'  => '0,1'
		));

		if (!isset($result[0])) {
			throw new \QUI\Exception('No Child in Folder', 404);
		}

		return $this->_Media->get( (int)$result[0]['id'] );
	}

    /**
     * Setzt das Wasserzeichen rekursiv auf den Ordner
     *
     * @var params
     */
	public function setWatermark($params=array())
	{
	    $this->_tmp_children = array();
		$this->_getChildrenRec($this->getId());

		$Media    = $this->_Media;
		$children = $this->_tmp_children;

		$watermark = json_decode($this->getAttribute('watermark'), true);

		if (isset($watermark['active'])) {
		    $params['active'] = $watermark['active'];
		}

		if (isset($watermark['image'])) {
		    $params['image'] = $watermark['image'];
		}

		if (isset($watermark['position'])) {
		    $params['position'] = $watermark['position'];
		}

		$this->setAttribute('watermark', $watermark);

		foreach ($children as $id)
		{
		    set_time_limit(10);

			try
			{
				$File = $Media->get( (int)$id );

				if ($File->getType() == 'FOLDER')
				{
                    $File->setAttribute('watermark', $this->getAttribute('watermark'));
                    $File->save();
				    continue;
				}

				if ($File->getType() != 'IMAGE') {
					continue;
				}

				/* @var $File MF_Image */
				$File->setWatermark(array(
				    'active' => (bool)$params['active']
				));

				$File->save();

			} catch (\QUI\Exception $Exception)
			{
                QUI::getMessagesHandler()->addException( $Exception );
			}
		}
	}


	/**
	 * Setzt Runde Ecken auf alle Bilder
	 * Wird rekursiv angewendet
	 *
	 * @param String $background - #FF0000
	 * @param Integer $radius    - 10
	 */
	public function setRoundCorners($background, $radius)
	{
		if (empty($background)) {
			throw new \QUI\Exception('Bitte geben Sie eine Hintergrundfarbe an');
		}

		if (empty($radius)) {
			throw new \QUI\Exception('Bitte geben Sie einen Radius für die Ecken an');
		}

		$this->_tmp_children = array();
		$this->_getChildrenRec($this->getId());

		$Media    = $this->_Media;
		$children = $this->_tmp_children;

		foreach ($children as $id)
		{
			try
			{
				$File = $Media->get( (int)$id );

				if ($File->getType() != 'IMAGE') {
					continue;
				}

				$File->setRoundCorners($background, $radius);
				$File->save();

			} catch( \QUI\Exception $e)
			{
				// nothing
			}
		}
	}

}

?>