<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use MohsenAbrishami\Stethoscope\Services\Cpu;
use MohsenAbrishami\Stethoscope\Services\HardDisk;
use MohsenAbrishami\Stethoscope\Services\Memory;
use MohsenAbrishami\Stethoscope\Services\Network;
use MohsenAbrishami\Stethoscope\Services\WebServer;
use MohsenAbrishami\Stethoscope\Traits\MessageCreatorTrait;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @covers \MohsenAbrishami\Stethosope\Commands\MonitorCommandTest
 */
class MonitorCommandTest extends TestCase
{
    use MessageCreatorTrait;

    public $log;
    public $filePath;

    public function setUp(): void
    {
        parent::setUp();

        $this->log;
        $this->filePath = config('stethoscope.log_file_storage.path') . now()->format('Y-m-d');
    }

    public function test_should_be_record_log_when_resources_exceeded_threshold()
    {
        $this->mockService(Cpu::class, 99);
        $this->mockService(HardDisk::class, 100);
        $this->mockService(Memory::class, 98);
        $this->mockService(Network::class, false);
        $this->mockService(WebServer::class, ['nginx' => 'inactive', 'apache' => 'inactive']);

        $this->deleteOldLogFile();

        $this->artisan('stethoscope:monitor')->assertOk();

        $this->readLogFile();

        $this->assertTrue(
            $this->assertContent($this->cpuMessage(99))
        );
        $this->assertTrue(
            $this->assertContent($this->hardDiskMessage(100))
        );
        $this->assertTrue(
            $this->assertContent($this->memoryMessage(98))
        );
        $this->assertTrue(
            $this->assertContent($this->networkMessage(false))
        );
        $this->assertTrue(
            $this->assertContent(
                $this->webServerMessage(['nginx' => 'inactive', 'apache' => 'inactive'])
            )
        );
    }

    public function test_should_be_not_record_log_when_resources_not_exceeded_threshold()
    {
        $this->mockService(Cpu::class, 80);
        $this->mockService(HardDisk::class, 100000000);
        $this->mockService(Memory::class, 70);
        $this->mockService(Network::class, true);
        $this->mockService(WebServer::class, ['nginx' => 'active', 'apache' => 'active']);

        $this->deleteOldLogFile();

        $this->artisan('stethoscope:monitor')->assertOk();

        $this->readLogFile();

        $this->assertFalse(
            $this->assertContent(
                ['cpu usage', 'hard disk free space', 'memory usage', 'network connection status', 'nginx status']
            )
        );
    }

    public function test_should_be_not_record_log_when_monitoring_is_disabled()
    {
        $this->mockService(Cpu::class, 99);
        $this->mockService(HardDisk::class, 100);
        $this->mockService(Memory::class, 98);
        $this->mockService(Network::class, false);
        $this->mockService(WebServer::class, ['nginx' => 'inactive', 'apache' => 'inactive']);

        Config::set('stethoscope.monitorable_resources.cpu', false);
        Config::set('stethoscope.monitorable_resources.memory', false);
        Config::set('stethoscope.monitorable_resources.hard_disk', false);
        Config::set('stethoscope.monitorable_resources.network', false);
        Config::set('stethoscope.monitorable_resources.web_server', false);

        $this->deleteOldLogFile();

        $this->artisan('stethoscope:monitor')->assertOk();

        $this->readLogFile();

        $this->assertFalse(
            $this->assertContent(
                ['cpu usage', 'hard disk free space', 'memory usage', 'network connection status', 'nginx status']
            )
        );
    }

    private function deleteOldLogFile()
    {
        Storage::delete($this->filePath);
    }

    private function readLogFile()
    {
        $this->log = Storage::get($this->filePath);
    }

    private function assertContent($message)
    {
        return Str::contains($this->log, $message);
    }
}
