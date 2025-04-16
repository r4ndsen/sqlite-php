<?php

namespace r4ndsen\SQLite\Pragma;

interface Constant
{
    public const ENCODING = 'encoding';
    public const JOURNAL_MODE = 'journal_mode';
    public const LOCKING_MODE = 'locking_mode';
    public const SYNCHRONOUS = 'synchronous';
    public const TEMP_STORE = 'temp_store';
}
