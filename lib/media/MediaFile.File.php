<?php

/**
 * MF_File
 * File Objekt im MediaCenter
 *
 * @author PCSG - Henning
 * @package com.pcsg.pms.media
 *
 * @copyright  2008 PCSG
 * @version    $Revision: 4414 $
 * @since      Class available since Release P.MS 0.9
 */

class MF_File extends MediaFile implements iMF
{
	protected $_TYPE = 'FILE';

	/**
	 * Gibt den Media Typ zurück
	 *
	 * @return String "FILE"
	 */
	public function getType()
	{
		return $this->_TYPE;
	}

	/**
	 * Aktiviert die Datei und die darüber liegenden Ordner
	 * @return true || throw \QUI\Exception
	 */
	public function activate()
	{
		// Parents aktvieren
		$Parent = $this->getParent();

		while ($Parent)
		{
			$Parent->activate();
			$Parent = $Parent->getParent();
		}

		// DB setzen
		$r_db = QUI::getDB()->updateData(
			$this->_TABLE,
			array('active' => 1),
			array('id' => $this->getId())
		);

		if (!$r_db) {
			throw new \QUI\Exception('Cannot activate ID:'.$this->getId().'; DB error');
		}

		$this->setAttribute('active', 1);

		// Cachefile erstellen
		$this->createCache();

		// Linkcache erstellen
		$this->_createLinkCache();
		return true;
	}

	/**
	 * Deaktiviert den Ordner
	 * @return Bool || throw \QUI\Exception
	 */
	public function deactivate()
	{
		// DB setzen
		$r_db = QUI::getDB()->updateData(
			$this->_TABLE,
			array('active' => 0),
			array('id' => $this->getId())
		);

		if ( !$r_db ) {
			throw new \QUI\Exception('Cannot activate ID:'.$this->getId().'; DB error');
		}

		$this->setAttribute('active', 0);

		// Cachefile löschen
		$this->deleteCache();

		// Linkcache löschen
		$this->_deleteLinkCache();

		return true;
	}

	/**
	 * Schiebt das File in den Temp Ordner und setzt das delete Flag
	 * @return Bool || throw \QUI\Exception
	 */
	public function delete()
	{
		$Media = $this->_Media;

		// Datei in das TMP verschieben
		$original   = $Media->getAttribute('media_dir') . $this->getAttribute('file');
		$var_folder = VAR_DIR .'media/'. $Media->getProject()->getAttribute('name') .'/';

		if (!is_dir($var_folder) || !file_exists($var_folder)) {
			\QUI\Utils\System\File::mkdir($var_folder);
		}

		\QUI\Utils\System\File::move($original, $var_folder.$this->getId());

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

		// Cache vom File löschen
		$this->deleteCache();


		if ($u_del) {
			return true;
		}

		throw new \QUI\Exception(
			'Update DB "MF_File->delete()" Error; ID '. $this->getId()
		);
	}

	/**
	 * Cachedatei erzeugen
	 * Es wurden nur manche Dateien aus Sicherheitsgründen gecachet
	 * @return String - Pfad zur Cachedatei || throw \QUI\Exception
	 */
	public function createCache()
	{
		$WHITE_LIST_EXTENSION = array(
			'pdf', 'txt', 'xml', 'doc', 'pdt', 'xls', 'csv', 'txt',
			'swf', 'flv',
			'mp3', 'ogg', 'wav',
			'mpeg', 'avi', 'mpg', 'divx', 'mov', 'wmv',
			'zip', 'rar', '7z', 'gzip', 'tar', 'tgz', 'ace',
			'psd'
		);

		if (!$this->getAttribute('active')) {
			return '';
		}

		$Media   = $this->_Media;
		$Project = $Media->getProject();

		// Link Cache erstellen
		$this->_createLinkCache();

		$original  = $Media->getAttribute('media_dir') . $this->getAttribute('file');
		$cachefile = $Media->getAttribute('cache_dir') . $this->getAttribute('file');

		$ext = \QUI\Utils\String::pathinfo($original, PATHINFO_EXTENSION);

		if (in_array($ext, $WHITE_LIST_EXTENSION))
		// Nur wenn Extension in Whitelist ist dann Cache machen
		{
			if (file_exists($cachefile)) {
				return $cachefile;
			}

			// Cachefolder erstellen
			$this->getParent()->createCache();

			try
			{
				\QUI\Utils\System\File::copy($original, $cachefile);
			} catch(\QUI\Exception $e)
			{
				// nothing
			}

		} else
		{
			if (file_exists($cachefile)) {
				unlink($cachefile);
			}

			return $original;
		}

		return $cachefile;
	}

	/**
	 * Cachedatei löschen
	 * @return Bool || throw \QUI\Exception
	 */
	public function deleteCache()
	{
		$cachefile = $this->_Media->getAttribute('cache_dir').$this->getAttribute('file');

		if (file_exists($cachefile)) {
			unlink($cachefile);
		}

		return true;
	}

	/**
	 * Umbennenen einer Datei
	 *
	 * @param String $name
	 * @return Bool || throw \QUI\Exception
	 */
	public function rename($name)
	{
		$Media  = $this->_Media; /* @var $Media Media */
		$Parent = $this->getParent();

		$name = $Media->stripMediaName($name);

		// Namen prüfen
		$Media->checkMediaName($name);

		// Alte Datei
		$org_file_path = $Media->getAttribute('media_dir').$this->getAttribute('file');

		// Neue Datei
		$ext = \QUI\Utils\String::pathinfo($org_file_path, PATHINFO_EXTENSION);

		if (strpos($name, '.') === false) {
		    $name = $name .'.'. $ext;
		}

		$new_file      = $Parent->getAttribute('file') . $name;
		$new_file_path = $Media->getAttribute('media_dir') . $new_file;

		if ($new_file_path == $org_file_path) {
			return true;
		}

		if (file_exists($new_file_path)) {
			throw new \QUI\Exception('Can\'t move the file; New File exists '. $new_file .';');
		}

		// Datei verschieben
		\QUI\Utils\System\File::move($org_file_path, $new_file_path);

		// Alter Cache löschen
		$this->deleteCache();

		// DB Update
		$u_db = QUI::getDB()->updateData(
			$this->_TABLE,
			array(
				'name' => $name,
				'file' => $new_file
			),
			array('id' => $this->getId())
		);

		if ($u_db) {
			return true;
		}

		return false;
	}

	/**
	 * Stellt eine Datei wieder her und schiebt diese in den übergebenen Ordner
	 *
	 * @param MF_Folder $toParent
	 * @return true || throw \QUI\Exception
	 */
	public function restore(MF_Folder $toParent)
	{
		if ($this->getAttribute('deleted') == 0) {
			return true;
		}

		$Media    = $this->_Media;
		$tmp_file = VAR_DIR .'media/'. $Media->getProject()->getAttribute('name') .'/'. $this->getId();

		if (file_exists($tmp_file))
		{
			$f_path = $toParent->getPath();

			$file = $this->getAttribute('file');
			$file = explode('/', $file);
			$file = end($file);

			$new_file = $Media->getAttribute('media_dir').$f_path.$file;

			// Datei in den richtigen Ordner schieben
			\QUI\Utils\System\File::move($tmp_file, $new_file);

			// DB aktualisieren
			$u_db = QUI::getDB()->updateData(
				$this->_TABLE,
				array(
					'deleted' => 0,
					'active'  => 0,
					'file' 	  => $f_path.$file
				),
				array('id' => $this->getId())
			);

			// Relation wieder herstellen
			$u_parent_db = QUI::getDB()->addData(
				$this->_RELTABLE,
				array(
					'parent' => $toParent->getId(),
					'child'  => $this->getId()
				)
			);

			if ($u_db && $u_parent_db) {
				return true;
			}

			throw new \QUI\Exception('Update DB Error on MF_File->restore()');
		}

		throw new \QUI\Exception('Deleted File not found');
	}

	/**
	 * Entfernt die Datei komplett aus der DB und dem Filesystem
	 * Muss vorher deativiert werden
	 * @return Bool || throw \QUI\Exception
	 */
	public function destroy()
	{
		if ($this->getAttribute('active') == 1) {
			throw new \QUI\Exception('File is active; Cant delete; deactivate File before delete');
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

		// TMP File löschen
		$tmp_file = VAR_DIR .'media/'. $this->_Media->getProject()->getAttribute('name') .'/'. $id;

		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}

		if ($u_des && $c_des && $p_des && !file_exists($tmp_file))
		{
			// Eigenschaft im Object selbst löschen
			$this->_attributes = array();
			$this->_id         = false;
			$this->_pid        = false;

			return true;
		}

		throw new \QUI\Exception('destroy error; ID '. $id);
	}

	/**
	 * Erstellt den Link Cache
	 * @return Bool || throw \QUI\Exception
	 */
	protected function _createLinkCache()
	{
		$Project  = $this->_Media->getProject();
		$lcd      = VAR_DIR .'cache/links/'. $Project->getAttribute('name') .'/';
		$lcd_file = $lcd.$this->getId() .'_'. $Project->getAttribute('name') .'_media';

		\QUI\Utils\System\File::mkdir($lcd);

		$url = $this->getUrl(true);

		$this->_deleteLinkCache();

		if (!file_put_contents($lcd_file, $url))
		{
			throw new \QUI\Exception(
				'Error on CreateLinkCache at File '.$lcd_file
			);
		}

		return true;
	}

	/**
	 * Löscht den Link Cache
	 * @return Bool || throw \QUI\Exception
	 */
	protected function _deleteLinkCache()
	{
		$Project  = $this->_Media->getProject();
		$lcd      = VAR_DIR .'cache/links/'. $Project->getAttribute('name') .'/';
		$lcd_file = $lcd.$this->getId() .'_'. $Project->getAttribute('name') .'_media';

		if (file_exists($lcd_file)) {
			unlink($lcd_file);
		}

		return true;
	}

	/**
	 * Ersetzt die Datei
	 *
	 * @param Array $FILE
	 * @return Bool || throw \QUI\Exception
	 */
	public function replace($FILE)
	{
		if (!is_array($FILE)) {
			throw new \QUI\Exception('$FILE is not an array');
		}

		if (!isset($FILE["name"])) {
			throw new \QUI\Exception('$FILE["name"] not exist');
		}

		$Media    = $this->_Media; /* @var $Media Media  */
		$mediadir = $Media->getAttribute('media_dir');

		$dir  = $mediadir . $this->getParent()->getAttribute('file');
		$file = $mediadir . $this->getAttribute('file');

		$filename = $Media->stripMediaName($FILE["name"]);
		$new_file = $dir . $filename;

		if (file_exists($new_file) && $file != $new_file) {
			throw new \QUI\Exception($filename." already exists");
		}

		unlink($file);

		if (!move_uploaded_file($FILE["tmp_name"], $new_file))
		{
			if (!copy($FILE["tmp_name"], $new_file)) {
				throw new \QUI\Exception('replace() Error; Konnte "'. $FILE["name"] .'" nicht kopieren');
			}
		}

		// Alter Cache löschen
		$this->deleteCache();

		// Update
		$info = \QUI\Utils\System\File::getInfo( $new_file );

		$this->setAttribute('file', str_replace($mediadir, '', $new_file));

		QUI::getDB()->updateData(
			$this->_TABLE,
			array(
				'name' 		=> $filename,
				'e_date' 	=> date('Y-m-d h:i:s'),
				'file' 		=> str_replace($mediadir, '', $new_file),
				'mime_type' => $info['mime_type'],
				'type' 		=> $Media->getMediaTypeByMimeType( $info['mime_type'] ),

				'image_height'	=> $info['height'],
				'image_width' 	=> $info['width']
			),
			array(
				'id' => $this->getId()
			)
		);

		// Link Cache erstellen
		$this->_createLinkCache();

		return true;
	}

	/**
	 * Datei verschieben
	 *
	 * @param MF_Folder $Folder
	 */
	public function moveTo(MF_Folder $toParent)
	{
		$Parent = $this->getParent();
		$Media  = $this->_Media;

		if ($toParent->getId() == $Parent->getId()) {
			return;
		}

		$f_path = $toParent->getPath();

		$file = $this->getAttribute('file');
		$file = explode('/', $file);
		$file = end($file);

		$new_file = $Media->getAttribute('media_dir') . $f_path . $file;
		$old_file = $Media->getAttribute('media_dir') . $this->getPath();

		\QUI\Utils\System\File::move($old_file, $new_file);

		$this->setAttribute('file', $f_path . $file);

		// daten
		QUI::getDB()->updateData(
			$this->_TABLE,
			array(
				'e_date' => date('Y-m-d h:i:s'),
				'file' 	 => $f_path . $file
			),
			array(
				'id' => $this->getId()
			)
		);

		// relation
		QUI::getDB()->updateData(
			$this->_RELTABLE,
			array('parent' => $toParent->getId()),
			array('child' => $this->getId())

		);

		// Link Cache erstellen
		$this->_createLinkCache();

		// Alter Cache löschen
		$this->deleteCache();

		return true;
	}
}

?>