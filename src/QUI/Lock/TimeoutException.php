<?php

namespace QUI\Lock;

/** The process lock could not be acquired within the caller's waiting budget. */
class TimeoutException extends Exception
{
}
