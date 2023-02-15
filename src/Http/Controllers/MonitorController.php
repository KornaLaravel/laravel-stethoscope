<?php

namespace MohsenAbrishami\Stethoscope\Http\Controllers;

use MohsenAbrishami\Stethoscope\Models\ResourceLog;
use MohsenAbrishami\Stethoscope\Services\Cpu;
use MohsenAbrishami\Stethoscope\Services\HardDisk;
use MohsenAbrishami\Stethoscope\Services\Memory;
use MohsenAbrishami\Stethoscope\Services\Network;
use MohsenAbrishami\Stethoscope\Services\WebServer;

class MonitorController extends Controller
{
    public function current(Cpu $cpu, Memory $memory, Network $network, WebServer $webServer, HardDisk $hardDisk)
    {
        return response()->json([
            'cpu' => $cpu->check(),
            'memory' => $memory->check(),
            'network' => $network->check(),
            'web_server' => $webServer->check(),
            'hard_disk' => $hardDisk->check(),
        ]);
    }

    public function history($from, $to)
    {
        $resourceLogs = ResourceLog::where('created_at', '>=', $from . ' 00:00:00')
            ->where('created_at', '<=', $to . ' 23:59:59')->get();

        return response()->json([$resourceLogs]);
    }
}
