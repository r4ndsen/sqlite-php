<?php

namespace r4ndsen\SQLite\Pragma;

interface Constant
{
    public const ENCODING = 'encoding';
    public const JOURNAL_MODE = 'journal_mode';
    public const LOCKING_MODE = 'locking_mode';
    public const SYNCHRONOUS = 'synchronous';
    public const TEMP_STORE = 'temp_store';
    public const CACHE_SIZE = 'cache_size';
    public const INTEGRITY_CHECK = 'integrity_check';
    public const INTEGRITY_CHECK_OKAY = 'ok';
    public const PAGE_SIZE = 'page_size';
    public const QUICK_CHECK = 'quick_check';
}
