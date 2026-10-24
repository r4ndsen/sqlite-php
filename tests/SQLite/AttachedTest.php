<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Exception\AttachedDatabaseException;

final class AttachedTest extends TestCase
{
    private string $attachedFile;
    private string $encryptedFile;

    protected function setUp(): void
    {
        parent::setUp();

        // unique per test, infection runs the tests in parallel processes
        $prefix = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('attached-', true);
        $this->attachedFile = $prefix . '-unittest.sqlite';
        $this->encryptedFile = $prefix . '-encrypted.sqlite';
    }

    protected function tearDown(): void
    {
        file_exists($this->attachedFile) && unlink($this->attachedFile);
        file_exists($this->encryptedFile) && unlink($this->encryptedFile);
    }

    #[Test]
    public function it_should_attach(): void
    {
        $this->expectException(AttachedDatabaseException::class);

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->attach($this->attachedFile, 'one');
    }

    #[Test]
    public function it_should_attach_already_in_use(): void
    {
        $this->expectException(AttachedDatabaseException::class);
        $this->expectExceptionMessage('database one is already in use');

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->attach($this->attachedFile, 'one');
    }

    #[Test]
    public function it_should_attach_already_in_use_case_sensitivity(): void
    {
        $this->expectException(AttachedDatabaseException::class);
        $this->expectExceptionMessage('database One is already in use');

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->attach($this->attachedFile, 'One');
    }

    #[Test]
    public function it_should_detach(): void
    {
        $this->expectException(AttachedDatabaseException::class);

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        self::assertTrue($this->SQLite->attach($this->encryptedFile, 'two'));
        self::assertTrue($this->SQLite->detach('one'));
        self::assertTrue($this->SQLite->detach('two'));
        $this->SQLite->detach('one');
    }

    #[Test]
    public function it_should_forget_attached_database_by_its_mixed_case_identifier(): void
    {
        self::assertTrue($this->SQLite->attach($this->attachedFile, 'One'));
        $this->SQLite->getAttached('One');
        self::assertTrue($this->SQLite->detach('One'));

        self::assertTrue($this->SQLite->attach($this->encryptedFile, 'One'));
        self::assertSame($this->encryptedFile, $this->SQLite->getAttached('One')->path);
        self::assertTrue($this->SQLite->detach('One'));

        $this->expectException(AttachedDatabaseException::class);
        $this->expectExceptionMessage('No attached sqlite with identifier: One');

        $this->SQLite->getAttached('One');
    }

    #[Test]
    public function it_should_get_attached(): void
    {
        $this->expectException(AttachedDatabaseException::class);

        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->getAttached('one');
        self::assertTrue($this->SQLite->detach('one'));
        $this->SQLite->getAttached('one');
    }

    #[Test]
    public function it_should_get_attached_database_by_its_mixed_case_identifier(): void
    {
        self::assertTrue($this->SQLite->attach($this->attachedFile, 'One'));

        $attached = $this->SQLite->getAttached('One');

        self::assertSame($this->attachedFile, $attached->path);
        self::assertSame($attached, $this->SQLite->getAttached('one'));
    }

    #[Test]
    public function it_should_get_attached_database_with_its_data(): void
    {
        self::assertTrue($this->SQLite->attach($this->attachedFile, 'one'));
        $this->SQLite->exec('create table one.t (a)');

        self::assertTrue($this->SQLite->getAttached('ONE')->getTable('t')->exists());
        self::assertTrue($this->SQLite->detach('one'));
    }

    #[Test]
    public function it_should_not_get_attached_in_memory_database(): void
    {
        self::assertTrue($this->SQLite->attach(':memory:', 'mem'));

        $this->expectException(AttachedDatabaseException::class);
        $this->expectExceptionMessage('can only be used through the connection it is attached to');

        $this->SQLite->getAttached('mem');
    }

    #[Test]
    public function it_should_tell_whether_a_database_is_attached(): void
    {
        $handler = new AttachmentHandler($this->SQLite->getConnection());

        self::assertFalse($handler->has('one'));
        self::assertTrue($handler->attach($this->attachedFile, 'one'));
        self::assertTrue($handler->has('one'));
    }
}
