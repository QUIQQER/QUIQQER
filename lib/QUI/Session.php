<?php

/**
 * This file contains \QUI\Session
 */

namespace QUI;

use QUI;
use QUI\System\Log;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
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
    protected $lifetime = 1400;

    /**
     * Session handler
     *
     * @var \Symfony\Component\HttpFoundation\Session\Session
     */
    private $Session = false;

    /**
     * Storage handler
     *
     * @var \Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler
     */
    private $Storage = false;

    /**
     * Database table
     *
     * @var string
     */
    private $table;

    /**
     * @var array
     */
    protected $vars = array();

    /**
     * constructor
     */
    public function __construct()
    {
        $this->table = QUI::getDBTableName('sessions');

        // symfony files
        $classNativeSessionStorage = '\Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage';

        $fileNativeSessionStorage = OPT_DIR .
                                    'symfony/http-foundation/Symfony/Component/' .
                                    'HttpFoundation/Session/Storage/NativeSessionStorage.php';

        $classSession = '\Symfony\Component\HttpFoundation\Session\Session';

        $fileSession = OPT_DIR .
                       'symfony/http-foundation/Symfony/Component/' .
                       'HttpFoundation/Session/Session.php';


        // options
        if (QUI::conf('session', 'max_life_time')) {
            $this->lifetime = QUI::conf('session', 'max_life_time');
        }

        $storageOptions = array(
            'cookie_httponly' => true,
            'name'            => 'qsess',
            'cookie_lifetime' => $this->lifetime,
            'gc_maxlifetime'  => $this->lifetime,
            'cookie_secure'   => QUI\Utils\System::isProtocolSecure()
        );

        if (!class_exists('NativeSessionStorage')) {
            if (file_exists($fileNativeSessionStorage)) {
                include_once $fileNativeSessionStorage;
            } else {
                throw new \Exception(
                    'Session File not found ' . $fileNativeSessionStorage
                );
            }

            if (class_exists($classNativeSessionStorage)) {
                $this->Storage = new $classNativeSessionStorage(
                    $storageOptions,
                    $this->getStorage()
                );
            }
        } else {
            $this->Storage = new NativeSessionStorage(
                $storageOptions,
                $this->getStorage()
            );
        }

        if (!class_exists('NativeSessionStorage')) {
            if (file_exists($fileSession)) {
                include_once $fileSession;
            } else {
                throw new \Exception('Session File not found ' . $fileSession);
            }

            if (class_exists($classSession)) {
                $this->Session = new $classSession($this->Storage);
            }
        } else {
            $this->Session = new \Symfony\Component\HttpFoundation\Session\Session(
                $this->Storage
            );
        }

        if (headers_sent()) {
            $this->Storage = new MockFileSessionStorage();
            $this->Session = new \Symfony\Component\HttpFoundation\Session\Session($this->Storage);
        }

        $this->start();
    }

    /**
     * Return the storage type
     *
     * @return \SessionHandlerInterface
     */
    protected function getStorage()
    {
        $sessionType = QUI::conf('session', 'type');

        switch ($sessionType) {
            case 'database':
            case 'memcached':
            case 'memcache':
                break;

            default:
                return new NativeFileSessionHandler(VAR_DIR . 'sessions');
        }


        // memcached
        if ($sessionType == 'memcached' && class_exists('Memcached')) {
            $memcached_data = QUI::conf('session', 'memcached_data');
            $memcached_data = explode(';', $memcached_data);

            $Memcached = new \Memcached('quiqqer');

            foreach ($memcached_data as $serverData) {
                $serverData = explode(':', $serverData);

                $server = $serverData[0];
                $port   = 11211;

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
                $port   = 11211;

                if (isset($serverData[1])) {
                    $port = $serverData[1];
                }

                $Memcache->addserver($server, $port);
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
                'db_table'        => $this->table,
                'db_id_col'       => 'session_id',
                'db_data_col'     => 'session_value',
                'db_time_col'     => 'session_time',
                'db_lifetime_col' => 'session_lifetime'
            ));
        }

        return new NativeFileSessionHandler(VAR_DIR . 'sessions');
    }

    /**
     * Start the session
     */
    public function start()
    {
        if (!$this->Session) {
            return;
        }

        if ($this->Session->isStarted()) {
            $this->Session->getMetadataBag()->stampNew($this->lifetime);

            return;
        }

        $this->Session->start();
    }

    /**
     * Session setup
     */
    public function setup()
    {
        $DBTable = QUI::getDataBase()->table();

        // pdo mysql options db
        // more at http://symfony.com/doc/current/cookbook/configuration/pdo_session_storage.html
        $DBTable->addColumn($this->table, array(
            'session_id'       => 'varchar(255) NOT NULL',
            'session_value'    => 'text NOT NULL',
            'session_time'     => 'int(11) NOT NULL',
            'session_lifetime' => 'int(12) NOT NULL',
            'uid'              => 'int(11) NOT NULL'
        ));

        $DBTable->setPrimaryKey($this->table, 'session_id');
    }

    /**
     * Set a variable to the session
     *
     * @param string $name - Name og the variable
     * @param string $value - value of the variable
     */
    public function set($name, $value)
    {
        if ($this->Session) {
            $this->Session->set($name, $value);
        }
    }

    /**
     * refresh the session and extend the session time
     */
    public function refresh()
    {
        if ($this->Session) {
            $this->Session->migrate();
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
        if ($this->Session) {
            return $this->Session->get($name, false);
        }

        return false;
    }

    /**
     * Returns the session-id
     *
     * @return string
     */
    public function getId()
    {
        if ($this->Session) {
            return $this->Session->getId();
        }

        return md5(microtime()) . QUI\Utils\Security\Orthos::getPassword();
    }

    /**
     * Checks the validity of the session
     *
     * @return boolean
     */
    public function check()
    {
        if (!$this->Session) {
            return false;
        }

        $idle = time() - $this->Session->getMetadataBag()->getLastUsed();

        if ($idle > $this->lifetime) {
            $this->Session->invalidate();

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
        if ($this->Session) {
            $this->Session->remove($var);
        }
    }

    /**
     * Alias for del()
     *
     * @param string $var
     */
    public function remove($var)
    {
        $this->del($var);
    }

    /**
     * Destroy the whole session
     */
    public function destroy()
    {
        if (!$this->Session) {
            return;
        }

        $this->Session->clear();
        $this->Session->invalidate();
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
        $result = QUI::getDataBase()->fetch(
            array(
                'from'  => $this->table,
                'where' => array(
                    'session_id' => $sid
                ),
                'limit' => 1
            )
        );

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
        $result = QUI::getDataBase()->fetch(
            array(
                'from'  => $this->table,
                'where' => array(
                    'uid' => (int)$uid
                ),
                'limit' => 1
            )
        );

        return isset($result[0]) ? true : false;
    }

    /**
     * Return the symfony session
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    public function getSymfonySession()
    {
        return $this->Session;
    }
}
