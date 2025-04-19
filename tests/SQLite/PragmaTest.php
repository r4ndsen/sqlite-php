<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Exception\SQLiteException;
use stdClass;

final class PragmaTest extends TestCase
{
    #[Test]
    public function it_should_allow_setting_journal_mode_by_string(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.sqlite';
        $this->SQLite = new SQLite($path);

        try {
            $p = $this->SQLite->pragma;
            self::assertSame(Pragma\JournalMode::DELETE, $p->getJournalMode());

            $p->journal_mode = 'WaL';
            self::assertSame(Pragma\JournalMode::WAL, $p->getJournalMode());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function it_should_allow_setting_synchronous_by_integer(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.sqlite';
        $this->SQLite = new SQLite($path);

        try {
            $p = $this->SQLite->pragma;
            self::assertSame(Pragma\Synchronous::FULL, $p->getSynchronous());

            foreach (range(0, 3) as $value) {
                $p->synchronous = $value;

                self::assertSame(Pragma\Synchronous::from($value), $p->getSynchronous());
            }
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function it_should_allow_setting_synchronous_by_string(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.sqlite';
        $this->SQLite = new SQLite($path);

        try {
            $p = $this->SQLite->pragma;
            self::assertSame(Pragma\Synchronous::FULL, $p->getSynchronous());

            $p->synchronous = 'ExTrA';
            self::assertSame(Pragma\Synchronous::EXTRA, $p->getSynchronous());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function it_should_get_and_set_encoding(): void
    {
        $p = $this->SQLite->pragma;
        self::assertSame(Pragma\Encoding::UTF8, $p->getEncoding());

        $p->encoding = Pragma\Encoding::UTF16;
        self::assertSame(Pragma\Encoding::UTF16LE, $p->getEncoding(), 'on apple mac this chooses UTF-16LE as it is the machines native byte-ordering');

        $p->encoding = 'UTF-16BE';
        self::assertSame(Pragma\Encoding::UTF16BE, $p->getEncoding());

        $p->encoding = 'UTF-16le';
        self::assertSame(Pragma\Encoding::UTF16LE, $p->getEncoding());
    }

    #[Test]
    public function it_should_get_and_set_locking_mode(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.sqlite';
        $this->SQLite = new SQLite($path);

        try {
            $p = $this->SQLite->pragma;
            self::assertSame(Pragma\LockingMode::NORMAL, $p->getLockingMode());

            $p->locking_mode = 'exCLusIve';
            self::assertSame(Pragma\LockingMode::EXCLUSIVE, $p->getLockingMode());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function it_should_get_and_set_temp_store(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.sqlite';
        $this->SQLite = new SQLite($path);

        try {
            $p = $this->SQLite->pragma;
            self::assertSame(Pragma\TempStore::DEFAULT, $p->getTempStore());

            $p->temp_store = 'File';
            self::assertSame(Pragma\TempStore::FILE, $p->getTempStore());

            $p->temp_store = 2;
            self::assertSame(Pragma\TempStore::MEMORY, $p->getTempStore());
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function it_should_pragma_cache_size(): void
    {
        $pragma = $this->SQLite->pragma;

        $rand = random_int(1_000, 10_000);

        $pragma->setCacheSize($rand);
        self::assertSame($rand, $pragma->getCacheSize());
        self::assertSame($rand, $pragma->cache_size);
    }

    #[Test]
    public function it_should_pragma_construct(): void
    {
        $conn = (new SQLite())->getConnection();
        $pragma = new Pragma($conn);
        $pragma->setCacheSize(2048);

        self::assertSame(2_048, $pragma->getCacheSize());
    }

    #[Test]
    public function it_should_pragma_get_database_list(): void
    {
        $pragma = $this->SQLite->pragma;

        $databases = $pragma->getDatabaseList();
        self::assertCount(1, $databases);

        self::assertInstanceOf(stdClass::class, $databases[0]);
        self::assertSame('main', $databases[0]->name);
    }

    #[Test]
    public function it_should_pragma_initialization(): void
    {
        $pragma = $this->SQLite->pragma;
        self::assertInstanceOf(Pragma::class, $pragma);

        $pragma = $this->SQLite->PRaGmA;
        self::assertInstanceOf(Pragma::class, $pragma);

        self::assertSame('ok', $pragma->quickCheck());
        self::assertSame('ok', $pragma->integrityCheck());
    }

    #[Test]
    public function it_should_pragma_isset(): void
    {
        /** @var Pragma $pragma */
        $pragma = $this->SQLite->pragma;

        self::assertFalse(isset($pragma->foobar));
        self::assertNull($pragma->foobar);
        self::assertTrue(isset($pragma->cache_size));
    }

    #[Test]
    public function it_should_pragma_journal_mode(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test.sqlite';

        try {
            /** @var Pragma $pragma */
            $pragma = (new SQLite($path))->pragma;

            foreach (Pragma\JournalMode::cases() as $mode) {
                if ($mode === Pragma\JournalMode::OFF) {
                    continue;
                }

                $pragma->setJournalMode($mode);

                self::assertSame($mode, $pragma->getJournalMode());
            }
        } finally {
            unlink($path);
        }
    }

    #[Test]
    public function it_should_pragma_journal_mode_is_always_memory_on_in_memory_database(): void
    {
        /** @var Pragma $pragma */
        $pragma = (new SQLite(':memory:'))->pragma;

        foreach (Pragma\JournalMode::cases() as $mode) {
            $pragma->setJournalMode($mode);
            self::assertSame(Pragma\JournalMode::MEMORY, $pragma->getJournalMode());
        }
    }

    #[Test]
    public function it_should_pragma_page_size(): void
    {
        $this->expectException(SQLiteException::class);
        $this->expectExceptionMessage('page_size must be in (1024, 2048, 4096, 8192, 16384, 32768, 65536)');

        $pragma = $this->SQLite->pragma;

        $pragma->setPageSize(8_192);
        self::assertSame(8_192, $pragma->getPageSize());
        self::assertSame(8_192, $pragma->page_size);

        $pragma->setPageSize(10);
    }

    #[Test]
    public function it_should_synchronous(): void
    {
        $p = $this->SQLite->pragma;

        self::assertSame(Pragma\Synchronous::FULL, $p->getSynchronous());

        foreach (Pragma\Synchronous::cases() as $mode) {
            $p->setSynchronous($mode);
            self::assertSame($mode, $p->getSynchronous());
        }
    }

    #[Test]
    public function it_should_throw_on_invalid_encoding_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for pragma.encoding: foo');
        $this->SQLite->pragma->encoding = 'foo';
    }

    #[Test]
    public function it_should_throw_on_invalid_journal_mode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for pragma.journal_mode: foo');

        $this->SQLite->pragma->journal_mode = 'foo';
    }

    #[Test]
    public function it_should_throw_on_invalid_locking_mode_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for pragma.locking_mode: foo');
        $this->SQLite->pragma->locking_mode = 'foo';
    }

    #[Test]
    public function it_should_throw_on_invalid_synchronous_numeric_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->SQLite->pragma->synchronous = 5;
    }

    #[Test]
    public function it_should_throw_on_invalid_synchronous_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for pragma.synchronous: foo');

        $this->SQLite->pragma->synchronous = 'foo';
    }

    #[Test]
    public function it_should_throw_on_invalid_temp_store_number(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for pragma.temp_store: 10');
        $this->SQLite->pragma->temp_store = 10;
    }

    #[Test]
    public function it_should_throw_on_invalid_temp_store_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid value for pragma.temp_store: foo');
        $this->SQLite->pragma->temp_store = 'foo';
    }
}
