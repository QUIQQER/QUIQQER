<?php

/**
 * This file contains QUI\Cache\FileDriver
 */

namespace QUI\Cache;

use MongoDB\Collection;
use QUI;

use Stash\Driver\AbstractDriver;
use Stash\Exception\InvalidArgumentException;

/**
 * Class QuiqqerMongoDriver
 * based on https://github.com/fisuku/mongostash
 */
class QuiqqerMongoDriver extends AbstractDriver
{
    /**
     * @var \MongoDB\Collection
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
        if (!\function_exists('\MongoDB\is_in_transaction')) {
            $file = OPT_DIR.'mongodb/mongodb/src/functions.php';

            if (\file_exists($file)) {
                require $file;
            }
        }
    }

    /**
     * @param array $key
     * @return string
     */
    private static function mapKey($key)
    {
        return \implode('/', $key);
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
                'data'       => \unserialize($doc['data']),
                'expiration' => $doc['expiration']
            ];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration)
    {
        if ($this->collection instanceof \MongoDB\Collection) {
            $id = self::mapKey($key);

            try {
                $this->collection->replaceOne(['_id' => $id], [
                    '_id'        => $id,
                    'data'       => \serialize($data),
                    'expiration' => $expiration
                ], ['upsert' => true]);
            } catch (\MongoDB\Driver\Exception\BulkWriteException $ignored) {
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

        $preg = "^".preg_quote(self::mapKey($key));

        if ($this->collection instanceof \MongoDB\Collection) {
            $this->collection->deleteMany([
                '_id' => new \MongoDB\BSON\Regex($preg, '')
            ]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purge()
    {
        if ($this->collection instanceof \MongoDB\Collection) {
            $this->collection->deleteMany([
                'expiration' => ['$lte' => time()]
            ]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOptions()
    {
        return [
            'mongo'      => 'quiqqer',
            'database'   => 'local',
            'collection' => 'quiqqer.store'
        ];
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

        /* @var $client \MongoDB\Client */
        $Client = $options['mongo'];

        if (!($Client instanceof \MongoDB\Client)) {
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
    public static function isAvailable()
    {
        return \class_exists('\MongoDB\Client', false);
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent()
    {
        return true;
    }
}
