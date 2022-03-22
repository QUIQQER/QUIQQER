<?php

namespace QUI\Cache;

use Stash\Driver\Redis;

use function is_null;

/**
 * Class QuiqqerRedisDriver
 * - Workaround class, because tedivm don't include the PR :(
 */
class QuiqqerRedisDriver extends Redis
{
    /**
     * {@inheritdoc}
     */
    public function clear($key = null)
    {
        if (is_null($key)) {
            $this->redis->flushDB();

            return true;
        }

        $keyString = $this->makeKeyString($key, true);
        $keyReal   = $this->makeKeyString($key);

        $this->redis->incr($keyString); // increment index for children items
        $this->redis->del($keyReal); // remove direct item.
        $this->keyCache = [];

        return true;
    }
}
