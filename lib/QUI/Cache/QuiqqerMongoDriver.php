<?php

/**
 * This file contains QUI\Cache\FileDriver
 */

namespace QUI\Cache;

use MongoDB\BSON\Regex;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;
use QUI;
use QUI\Exception;
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
     *
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        if (!self::isAvailable()) {
            throw new QUI\Exception(
                'Mongo DB Driver not found. ' .
                'Please install MongoDB\Client (php MongoDB extension) and the mongodb/mongodb package.' .
                'Otherwise don\'t use MongoDB as caching method',
                QUI\System\Log::LEVEL_ALERT
            );
        }

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
    public static function isAvailable(): bool
    {
        return class_exists('\MongoDB\Client', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getData($key): bool|array
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
    private static function mapKey(array $key): string
    {
        return implode('/', $key);
    }

    /**
     * {@inheritdoc}
     */
    public function storeData($key, $data, $expiration): bool
    {
        /* @phpstan-ignore-next-line */
        if ($this->collection instanceof Collection) {
            $id = self::mapKey($key);

            try {
                $this->collection->replaceOne(['_id' => $id], [
                    '_id' => $id,
                    'data' => serialize($data),
                    'expiration' => $expiration
                ], ['upsert' => true]);
            } catch (BulkWriteException) {
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
    public function clear($key = null): bool
    {
        if (!$key) {
            $this->collection->drop();

            return true;
        }

        $preg = "^" . preg_quote(self::mapKey($key));

        /* @phpstan-ignore-next-line */
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
    public function purge(): bool
    {
        /* @phpstan-ignore-next-line */
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
    public function setOptions(array $options = []): void
    {
        $options += $this->getDefaultOptions();

        /* @var $client Client */
        $Client = $options['mongo'];

        /* @phpstan-ignore-next-line */
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
    public function getDefaultOptions(): array
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
    public function isPersistent(): bool
    {
        return true;
    }
}
