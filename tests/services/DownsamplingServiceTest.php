<?php

declare(strict_types=1);

namespace tests\services;

use modules\services\DownsamplingService;

// Simple custom test runner since PHPUnit might not be installed
class DownsamplingServiceTest
{
    private DownsamplingService $service;

    public function __construct()
    {
        $this->service = new DownsamplingService();
    }

    public function run(): void
    {
        echo "Running DownsamplingService Tests...\n";
        $this->testArrayDownsampling();
        $this->testStreamDownsampling();
        $this->testEdgeCases();
        echo "All LTTB tests passed successfully.\n";
    }

    private function generateDummyData(int $count): array
    {
        $data = [];
        $startTime = time();
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'time_iso' => date('c', $startTime + $i * 60),
                'value'    => (string)(sin($i / 10) * 50 + 50),
                'flag'     => 0
            ];
        }
        return $data;
    }

    private function generateStream(array $data): \Generator
    {
        foreach ($data as $row) {
            yield $row;
        }
    }

    private function testArrayDownsampling(): void
    {
        $data = $this->generateDummyData(1000);
        $threshold = 100;

        $sampled = $this->service->downsampleLTTB($data, $threshold);

        if (count($sampled) !== $threshold) {
            throw new \Exception("Array test failed: Expected $threshold points, got " . count($sampled));
        }

        if ($sampled[0] !== $data[0] || end($sampled) !== end($data)) {
            throw new \Exception("Array test failed: First or last point not preserved.");
        }
    }

    private function testStreamDownsampling(): void
    {
        $dataCount = 1000;
        $data = $this->generateDummyData($dataCount);
        $stream = $this->generateStream($data);
        $threshold = 100;

        $sampled = $this->service->downsampleLTTBStream($stream, $dataCount, $threshold);

        if (count($sampled) !== $threshold) {
            throw new \Exception("Stream test failed: Expected $threshold points, got " . count($sampled));
        }

        if ($sampled[0] !== $data[0] || end($sampled) !== end($data)) {
            $expectedFirst = json_encode($data[0]);
            $actualFirst = json_encode($sampled[0]);
            $expectedLast = json_encode(end($data));
            $actualLast = json_encode(end($sampled));
            throw new \Exception("Stream test failed: First or last point not preserved.\nExpected First: $expectedFirst\nActual First: $actualFirst\nExpected Last: $expectedLast\nActual Last: $actualLast");
        }
    }

    private function testEdgeCases(): void
    {
        $data = $this->generateDummyData(50);
        
        // Threshold > Data Length
        $sampled = $this->service->downsampleLTTB($data, 100);
        if (count($sampled) !== 50) {
            throw new \Exception("Edge case failed: Threshold > Data length should return original data.");
        }

        // Empty Data
        $sampled = $this->service->downsampleLTTB([], 10);
        if (count($sampled) !== 0) {
            throw new \Exception("Edge case failed: Empty data should return empty array.");
        }
    }
}

// Auto-run if executed directly
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../../vendor/autoload.php';
    (new DownsamplingServiceTest())->run();
}
