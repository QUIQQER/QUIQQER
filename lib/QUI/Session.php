<?php

/**
 * This file contains \QUI\Session
 */

namespace QUI;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcacheSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\MemcachedSessionHandler;

/**
 * Session handling for QUIQQER
 *
 * based at symfony session handler
 * http://symfony.com/doc/current/components/http_foundation/sessions.html
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class Session
{
    /**
     * max time life in seconds
     *
     * @var Integer
     */
    private $_max_life_time = 0;

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
     * @var String
     */
    private $_table;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->_table = QUI_DB_PRFX.'sessions';

        $this->_max_life_time = 1400; // default

        if ($sess = \QUI::conf('session', 'max_life_time')) {
            $this->_max_life_time = $sess;
        }

        ini_set('session.gc_maxlifetime', $this->_max_life_time);
        ini_set('session.gc_probability', 100);


        if (!class_exists('NativeSessionStorage')) {
            $nativeSessionFile = OPT_DIR
                .'symfony/http-foundation/Symfony/Component/HttpFoundation/Session/Storage/NativeSessionStorage.php';

            if (file_exists($nativeSessionFile)) {
                require_once $nativeSessionFile;
            } else {
                throw new \Exception('Session File not found '
                    .$nativeSessionFile);
            }

            if (class_exists('\Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage')) {
                $this->_Storage
                    = new \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage(array(),
                    $this->_getStorage());
            }

        } else {
            $this->_Storage = new NativeSessionStorage(array(),
                $this->_getStorage());
        }


        if (!class_exists('NativeSessionStorage')) {
            $sessionFile = OPT_DIR
                .'symfony/http-foundation/Symfony/Component/HttpFoundation/Session/Session.php';

            if (file_exists($sessionFile)) {
                require_once $sessionFile;
            } else {
                throw new \Exception('Session File not found '.$sessionFile);
            }

            if (class_exists('\Symfony\Component\HttpFoundation\Session\Session')) {
                $this->_Session
                    = new \Symfony\Component\HttpFoundation\Session\Session($this->_Storage);
            }

        } else {
            $this->_Session = new SymfonySession($this->_Storage);
        }
    }

    /**
     * Return the storage type
     *
     * @return \SessionHandlerInterface
     */
    protected function _getStorage()
    {
        $memcached_host = \QUI::conf('session', 'memcached_host');
        $memcached_port = \QUI::conf('session', 'memcached_host');

        $memcache_host = \QUI::conf('session', 'memcache_host');
        $memcache_port = \QUI::conf('session', 'memcache_host');

        if (!empty($memcached_host) && !empty($memcached_port)
            && class_exists('Memcached')
        ) {
            $Memcached = new \Memcached();
            $Memcached->addServer($memcached_host, $memcached_port);

            return new MemcachedSessionHandler($Memcached);
        }

        if (!empty($memcache_host) && !empty($memcache_port)
            && class_exists('Memcache')
        ) {
            $Memcache = new \Memcache($memcache_host, $memcache_port);

            return new MemcacheSessionHandler($Memcache);
        }

        return new PdoSessionHandler(\QUI::getDataBase()->getPDO(), array(
            'db_table'        => $this->_table,
            'db_id_col'       => 'session_id',
            'db_data_col'     => 'session_value',
            'db_time_col'     => 'session_time',
            'db_lifetime_col' => 'session_lifetime'
        ));
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
        $DBTable = \QUI::getDataBase()->Table();

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
     * @param String $name  - Name og the variable
     * @param String $value - value of the variable
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
     * @param String $name - name of the variable
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
     * @return String
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

        if ($idle > $this->_max_life_time) {
            $this->_Session->invalidate();

            return false;
        }

        return true;
    }

    /**
     * Delete a session variable
     *
     * @param String $var - name of the variable
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
     * @param String $sid - Session-ID
     *
     * @return Integer
     */
    public function getLastRefreshFrom($sid)
    {
        $result = \QUI::getDataBase()->fetch(array(
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
     * @param Integer $uid
     *
     * @return Bool
     */
    public function isUserOnline($uid)
    {
        $result = \QUI::getDataBase()->fetch(array(
            'from'  => $this->_table,
            'where' => array(
                'uid' => (int)$uid
            ),
            'limit' => 1
        ));

        return isset($result[0]) ? true : false;
    }
}
