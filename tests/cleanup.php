<?php

if (PHP_SAPI !== 'cli') {
    exit(1);
}

echo 'PHPUnit fixtures are cleaned up automatically by their owning test process.' . PHP_EOL;
echo 'Standalone cleanup does not remove projects or data from other test runs.' . PHP_EOL;
