<?php

/**
 * This file contains QUI\Cache\FileDriver
 */

namespace QUI\Cache;

use MongoDB\BSON\Regex;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;
use Stash\Driver\AbstractDriver;
use Stash\Exception\InvalidArgumentException;

use function class_exists;
use function file_exists;
use function function_exists;
use function implode;
use function serialize;
use function unserialize;

/**
 * Class QuiqqerMongoDriver
 * based on https://github.com/fisuku/mongostash
 */
class QuiqqerMongoDriver extends AbstractDriver
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * QuiqqerMongoDriver constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        // workaround for mongo auto loading, // load mongo functions
        if (!function_exists('\MongoDB\is_in_transaction')) {
            $file = OPT_DIR . 'mongodb/mongodb/src/functions.php';

            if (file_exists($file)) {
                require $file;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function isAvailable()
    {
        return class_exists('\MongoDB\Client', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key)
    {
        $doc = $this->collection->findOne([
            '_id' => self::mapKey($key)
        ]);

        if ($doc) {
            return [
                'data' => unserialize($doc['data']),
                'expiration' => $doc['expiration']
            ];
        }

        return false;
    }

    /**
     * @param array $key
     * @return string
     */
    private static function mapKey($key)
    {
        return implode('/', $key);
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        if ($this->collection instanceof Collection) {
            $id = self::mapKey($key);

            try {
                $this->collection->replaceOne(['_id' => $id], [
                    '_id' => $id,
                    'data' => serialize($data),
                    'expiration' => $expiration
                ], ['upsert' => true]);
            } catch (BulkWriteException $ignored) {
                // As of right now, BulkWriteException can be thrown by
                // replaceOne in high-throughput environments where race
                // conditions can occur
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (!$key) {
            $this->collection->drop();

            return true;
        }

        $preg = "^" . preg_quote(self::mapKey($key));

        if ($this->collection instanceof Collection) {
            $this->collection->deleteMany([
                '_id' => new Regex($preg, '')
            ]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        if ($this->collection instanceof Collection) {
            $this->collection->deleteMany([
                'expiration' => ['$lte' => time()]
            ]);
        }

        return true;
    }

    /**
     * mongo - A MongoClient/Mongo/MongoDB instance. Required.
     *
     * @param array $options
     *
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function setOptions(array $options = [])
    {
        $options += $this->getDefaultOptions();

        /* @var $client Client */
        $Client = $options['mongo'];

        if (!($Client instanceof Client)) {
            throw new \InvalidArgumentException(
                'MongoDB\Driver\Manager instance required'
            );
        }

        if (empty($options['database'])) {
            throw new \InvalidArgumentException("A database is required.");
        }

        $this->collection = $Client->selectCollection(
            $options['database'],
            $options['collection']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return [
            'mongo' => 'quiqqer',
            'database' => 'local',
            'collection' => 'quiqqer.store'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return true;
    }
}
