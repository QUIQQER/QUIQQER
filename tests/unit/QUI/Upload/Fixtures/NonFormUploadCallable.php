<?php

namespace QUI\Upload;

final class NonFormUploadCallable
{
    public static int $constructorCalls = 0;

    public function __construct()
    {
        self::$constructorCalls++;
    }
}
