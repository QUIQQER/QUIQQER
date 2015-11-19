<?php

/**
 * This file contains \QUI\Session
 */

namespace QUI;

use QUI;
use QUI\System\Log;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;

/**
 * Session handling for QUIQQER
 *
 * based at symfony session handler
 * http://symfony.com/doc/current/components/http_foundation/sessions.html
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class Session
{
    /**
     * Lifetime of the cookie
     *
     * @var int
     */
    protected $_lifetime = 1400;

    /**
     * Session handler
     *
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    private $_Session = false;

    /**
     * Storage handler
     *
     * @var \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
     */
    private $_Storage = false;

    /**
     * Database table
     *
     * @var string
     */
    private $_table;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->_table = QUI_DB_PRFX.'sessions';

        // symfony files
        $classNativeSessionStorage
            = '\Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage';

        $fileNativeSessionStorage = OPT_DIR
            .'symfony/http-foundation/Symfony/Component/HttpFoundation/Session/Storage/NativeSessionStorage.php';

        $classSession = '\Symfony\Component\HttpFoundation\Session\Session';

        $fileSession = OPT_DIR
            .'symfony/http-foundation/Symfony/Component/HttpFoundation/Session/Session.php';


        // options
        if ($sess = QUI::conf('session', 'max_life_time')) {
            $this->_lifetime = $sess;
        }

        $storageOptions = array(
            'cookie_lifetime'  => $this->_lifetime
        );

        if (!class_exists('NativeSessionStorage')) {

            if (file_exists($fileNativeSessionStorage)) {
                require_once $fileNativeSessionStorage;

            } else {
                throw new \Exception(
                    'Session File not found '.$fileNativeSessionStorage
                );
            }

            if (class_exists($classNativeSessionStorage)) {
                $this->_Storage = new $classNativeSessionStorage(
                    $storageOptions,
                    $this->_getStorage()
                );
            }

        } else {
            $this->_Storage = new NativeSessionStorage(
                $storageOptions,
                $this->_getStorage()
            );
        }

        if (!class_exists('NativeSessionStorage')) {

            if (file_exists($fileSession)) {
                require_once $fileSession;

            } else {
                throw new \Exception('Session File not found '
                    .$fileSession);
            }

            if (class_exists($classSession)) {
                $this->_Session = new $classSession($this->_Storage);
            }

        } else {
            $this->_Session
                = new \Symfony\Component\HttpFoundation\Session\Session($this->_Storage);
        }
    }

    /**
     * Return the storage type
     *
     * @return \SessionHandlerInterface
     */
    protected function _getStorage()
    {
        $sessionType = QUI::conf('session', 'type');

        switch ($sessionType) {
            case 'database':
            case 'memcached':
            case 'memcache':
                break;

            default:
                return new NativeFileSessionHandler(VAR_DIR.'sessions');
        }


        // memcached
        if ($sessionType == 'memcached' && class_exists('Memcached')) {

            $memcached_data = QUI::conf('session', 'memcached_data');
            $memcached_data = explode(';', $memcached_data);

            $Memcached = new \Memcached('quiqqer');

            foreach ($memcached_data as $serverData) {
                $serverData = explode(':', $serverData);

                $server = $serverData[0];
                $port = 11211;

                if (isset($serverData[1])) {
                    $port = $serverData[1];
                }

                $Memcached->addServer($server, $port, 1000);
            }

            return new MemcachedSessionHandler($Memcached);

        } elseif ($sessionType == 'memcached' && !class_exists('Memcached')) {
            Log::addWarning('Memcached not installed');
        }


        // memcache
        if ($sessionType == 'memcache' && class_exists('Memcache')) {

            $memcache_data = QUI::conf('session', 'memcache_data');
            $memcache_data = explode(';', $memcache_data);

            $Memcache = new \Memcache();

            foreach ($memcache_data as $serverData) {
                $serverData = explode(':', $serverData);

                $server = $serverData[0];
                $port = 11211;

                if (isset($serverData[1])) {
                    $port = $serverData[1];
                }

                $Memcache->addServer($server, $port);
            }

            return new MemcacheSessionHandler($Memcache);

        } elseif ($sessionType == 'memcache' && !class_exists('Memcache')) {
            Log::addWarning('Memcache not installed');
        }


        // session via database
        if ($sessionType == 'database') {

            $PDO = QUI::getDataBase()->getNewPDO();
            $PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return new PdoSessionHandler($PDO, array(
                'db_table'        => $this->_table,
                'db_id_col'       => 'session_id',
                'db_data_col'     => 'session_value',
                'db_time_col'     => 'session_time',
                'db_lifetime_col' => 'session_lifetime'
            ));
        }

        return new NativeFileSessionHandler(VAR_DIR.'sessions');
    }

    /**
     * Start the session
     */
    public function start()
    {
        if ($this->_Session) {
            $this->_Session->start();
        }
    }

    /**
     * Session setup
     */
    public function setup()
    {
        $DBTable = QUI::getDataBase()->Table();

        // pdo mysql options db
        // more at http://symfony.com/doc/current/cookbook/configuration/pdo_session_storage.html
        $DBTable->appendFields($this->_table, array(
            'session_id'       => 'varchar(255) NOT NULL',
            'session_value'    => 'text NOT NULL',
            'session_time'     => 'int(11) NOT NULL',
            'session_lifetime' => 'int(12) NOT NULL',
            'uid'              => 'int(11) NOT NULL'
        ));

        $DBTable->setPrimaryKey($this->_table, 'session_id');
    }

    /**
     * Set a variable to the session
     *
     * @param string $name  - Name og the variable
     * @param string $value - value of the variable
     */
    public function set($name, $value)
    {
        if ($this->_Session) {
            $this->_Session->set($name, $value);
        }
    }

    /**
     * refresh the session and extend the session time
     */
    public function refresh()
    {
        if ($this->_Session) {
            $this->_Session->migrate();
        }
    }

    /**
     * returns a variable from the session
     *
     * @param string $name - name of the variable
     *
     * @return mixed
     */
    public function get($name)
    {
        if ($this->_Session) {
            return $this->_Session->get($name, false);
        }

        return false;
    }

    /**
     * returns the session-id
     *
     * @return string
     */
    public function getId()
    {
        if ($this->_Session) {
            return $this->_Session->getId();
        }

        return md5(microtime());
    }

    /**
     * Checks the validity of the session
     *
     * @return bool
     */
    public function check()
    {
        if (!$this->_Session) {
            return false;
        }

        $idle = time() - $this->_Session->getMetadataBag()->getLastUsed();

        if ($idle > $this->_lifetime) {
            $this->_Session->invalidate();

            return false;
        }

        return true;
    }

    /**
     * Delete a session variable
     *
     * @param string $var - name of the variable
     */
    public function del($var)
    {
        if ($this->_Session) {
            $this->_Session->remove($var);
        }
    }

    /**
     * Destroy the whole session
     */
    public function destroy()
    {
        if (!$this->_Session) {
            return;
        }

        $this->_Session->clear();
        $this->_Session->invalidate();
    }

    /**
     * Return the last login from the session-id
     *
     * @param string $sid - Session-ID
     *
     * @return integer
     */
    public function getLastRefreshFrom($sid)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->_table,
            'where' => array(
                'session_id' => $sid
            ),
            'limit' => 1
        ));

        if (!isset($result[0])) {
            return 0;
        }

        return $result[0]['session_time'];
    }

    /**
     * Is the user online?
     *
     * @param integer $uid
     *
     * @return boolean
     */
    public function isUserOnline($uid)
    {
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->_table,
            'where' => array(
                'uid' => (int)$uid
            ),
            'limit' => 1
        ));

        return isset($result[0]) ? true : false;
    }

    /**
     * Return the symfony session
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSymfonySession()
    {
        return $this->_Session;
    }
}
