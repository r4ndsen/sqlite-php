<?php

namespace r4ndsen\SQLite\Pragma;

interface Constant
{
    public const CACHE_SIZE = 'cache_size';
    public const ENCODING = 'encoding';
    public const INTEGRITY_CHECK = 'integrity_check';
    public const INTEGRITY_CHECK_OKAY = 'ok';
    public const JOURNAL_MODE = 'journal_mode';
    public const LOCKING_MODE = 'locking_mode';
    public const PAGE_SIZE = 'page_size';
    public const QUICK_CHECK = 'quick_check';
    public const SYNCHRONOUS = 'synchronous';
    public const TEMP_STORE = 'temp_store';
}
