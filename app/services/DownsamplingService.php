<?php

declare(strict_types=1);

namespace modules\services;

/**
 * Service responsible for reducing the resolution of large datasets.
 *
 * Implements the Largest Triangle Three Buckets (LTTB) downsampling algorithm
 * to shrink massive data arrays or unbuffered database streams into a manageable
 * number of data points while rigorously preserving the visual shape, peaks,
 * and valleys of the original dataset.
 *
 * @package modules\services
 */
class DownsamplingService
{
    /**
     * Applies the Largest Triangle Three Buckets (LTTB) algorithm on an in-memory array.
     *
     * @param array<int, array{time_iso: string, value: string, flag: string}> $data The raw historical dataset.
     * @param int $threshold The desired maximum number of data points to return.
     * 
     * @return array<int, array{time_iso: string, value: string, flag: string}>
     */
    public function downsampleLTTB(array $data, int $threshold): array
    {
        $dataLength = count($data);
        if ($threshold >= $dataLength || $threshold <= 2 || $dataLength === 0) {
            return $data;
        }

        $sampled = [];
        $sampled[] = $data[0];

        $every = ($dataLength - 2) / ($threshold - 2);
        $a = 0; // Index of the last selected point

        for ($i = 0; $i < $threshold - 2; $i++) {
            // Calculate point average for next bucket
            $avgX = 0;
            $avgY = 0;
            $avgRangeStart  = (int)( floor( ($i + 1) * $every ) + 1 );
            $avgRangeEnd    = (int)( floor( ($i + 2) * $every ) + 1 );
            $avgRangeEnd = $avgRangeEnd < $dataLength ? $avgRangeEnd : $dataLength;

            $avgRangeLength = $avgRangeEnd - $avgRangeStart;

            for ($j = $avgRangeStart; $j < $avgRangeEnd; $j++) {
                $avgX += $j;
                $avgY += (float)$data[$j]['value'];
            }
            $avgX /= $avgRangeLength;
            $avgY /= $avgRangeLength;

            // Range for current bucket
            $rangeOffs = (int)(floor( ($i + 0) * $every ) + 1);
            $rangeTo   = (int)(floor( ($i + 1) * $every ) + 1);

            $pointAX = (float)$a;
            $pointAY = (float)$data[$a]['value'];

            $maxArea = -1;
            $maxAreaPoint = -1;

            for ($j = $rangeOffs; $j < $rangeTo; $j++) {
                $pointBX = (float)$j;
                $pointBY = (float)$data[$j]['value'];

                $area = abs( ($pointAX - $avgX) * ($pointBY - $pointAY) - ($pointAX - $pointBX) * ($avgY - $pointAY) ) * 0.5;
                if ($area > $maxArea) {
                    $maxArea = $area;
                    $maxAreaPoint = $j;
                }
            }

            if ($maxAreaPoint === -1) {
                $maxAreaPoint = $rangeOffs;
            }

            $sampled[] = $data[$maxAreaPoint];
            $a = $maxAreaPoint;
        }

        $sampled[] = $data[$dataLength - 1];
        return $sampled;
    }

    /**
     * Applies LTTB downsampling on a Generator/Iterator stream.
     *
     * @param \Iterator<int, array{time_iso: string, value: string, flag: string|int}> $stream
     * @param int $dataLength
     * @param int $threshold
     * 
     * @return array<int, array{time_iso: string, value: string, flag: string|int}>
     */
    public function downsampleLTTBStream(\Iterator $stream, int $dataLength, int $threshold): array
    {
        if ($threshold >= $dataLength || $threshold <= 2 || $dataLength === 0) {
            $all = [];
            foreach ($stream as $row) {
                $all[] = $row;
            }
            return $all;
        }

        $sampled = [];
        $stream->rewind();
        if (!$stream->valid()) return [];

        $firstPoint = $stream->current();
        $sampled[] = $firstPoint;
        $stream->next();

        $every = ($dataLength - 2) / ($threshold - 2);
        
        $a = $firstPoint;
        $aIdx = 0.0;
        $currentIdx = 1;

        $bucketEndIdx = (int)(floor(1 * $every) + 1);
        $nextBucketEndIdx = (int)(floor(2 * $every) + 1);

        $currentBucket = [];
        while ($stream->valid() && $currentIdx < $bucketEndIdx) {
            $currentBucket[] = $stream->current();
            $currentIdx++;
            $stream->next();
        }

        $nextBucket = [];
        while ($stream->valid() && $currentIdx < $nextBucketEndIdx) {
            $nextBucket[] = $stream->current();
            $currentIdx++;
            $stream->next();
        }

        for ($i = 0; $i < $threshold - 2; $i++) {
            $avgX = 0;
            $avgY = 0;
            $avgRangeLength = count($nextBucket);
            
            if ($avgRangeLength > 0) {
                $nextStartIdx = (int)(floor(($i + 1) * $every) + 1); // This is not quite right for streaming without offset
                // Actually, the indices are absolute. The next bucket starts at nextBucketStartIdx.
                $nextBucketStartIdx = (int)(floor(($i + 1) * $every) + 1);
                $idx = $nextBucketStartIdx;
                foreach ($nextBucket as $row) {
                    $avgX += $idx;
                    $avgY += (float)$row['value'];
                    $idx++;
                }
                $avgX /= $avgRangeLength;
                $avgY /= $avgRangeLength;
            }

            $pointAX = $aIdx;
            $pointAY = (float)$a['value'];

            $maxArea = -1;
            $maxAreaPoint = null;
            $maxAreaPointIdx = -1;

            $currentBucketStartIdx = (int)(floor(($i + 0) * $every) + 1);
            $idx = $currentBucketStartIdx;
            foreach ($currentBucket as $row) {
                $pointBX = (float)$idx;
                $pointBY = (float)$row['value'];

                $area = abs( ($pointAX - $avgX) * ($pointBY - $pointAY) - ($pointAX - $pointBX) * ($avgY - $pointAY) ) * 0.5;
                if ($area > $maxArea) {
                    $maxArea = $area;
                    $maxAreaPoint = $row;
                    $maxAreaPointIdx = $idx;
                }
                $idx++;
            }

            if ($maxAreaPoint === null && count($currentBucket) > 0) {
                $maxAreaPoint = $currentBucket[0];
                $maxAreaPointIdx = $currentBucketStartIdx;
            }

            if ($maxAreaPoint) {
                $sampled[] = $maxAreaPoint;
                $a = $maxAreaPoint;
                $aIdx = (float)$maxAreaPointIdx;
            }

            $currentBucket = $nextBucket;

            $targetNextBucketEndIdx = (int)(floor(($i + 3) * $every) + 1);
            if ($targetNextBucketEndIdx > $dataLength) {
                $targetNextBucketEndIdx = $dataLength;
            }

            $nextBucket = [];
            while ($stream->valid() && $currentIdx < $targetNextBucketEndIdx) {
                $nextBucket[] = $stream->current();
                $currentIdx++;
                $stream->next();
            }
        }

        $lastPoint = $firstPoint;
        while ($stream->valid()) {
            $lastPoint = $stream->current();
            $stream->next();
        }
        $sampled[] = $lastPoint;

        return $sampled;
    }
}
