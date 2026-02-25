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
     * This method evaluates the triangle area formed by points across three adjacent buckets
     * to determine the most visually significant data point to retain in each bucket.
     *
     * @param array<int, array{time_iso: string, value: string, flag: string}> $data The raw historical dataset.
     * @param int $threshold The desired maximum number of data points to return.
     * 
     * @return array<int, array{time_iso: string, value: string, flag: string}> The visually-representative downsampled dataset.
     */
    public function downsampleLTTB(array $data, int $threshold): array
    {
        $dataLength = count($data);
        if ($threshold >= $dataLength || $threshold <= 2 || $dataLength === 0) {
            return $data; // Nothing to do
        }

        $sampled = [];
        $sampled[] = $data[0]; // Always add the first point

        // Bucket size. Leave room for start and end data points
        $every = ($dataLength - 2) / ($threshold - 2);

        $a = 0; // Initially a is the first point in the triangle

        for ($i = 0; $i < $threshold - 2; $i++) {
            // Calculate point average for next bucket (containing c)
            $avgX = 0;
            $avgY = 0;
            $avgRangeStart  = (int)( floor( ($i + 1) * $every ) + 1 );
            $avgRangeEnd    = (int)( floor( ($i + 2) * $every ) + 1 );
            $avgRangeEnd = $avgRangeEnd < $dataLength ? $avgRangeEnd : $dataLength;

            $avgRangeLength = $avgRangeEnd - $avgRangeStart;

            for (; $avgRangeStart < $avgRangeEnd; $avgRangeStart++) {
                $avgX += strtotime($data[$avgRangeStart]['time_iso']) * 1000;
                $avgY += (float)$data[$avgRangeStart]['value'];
            }
            $avgX /= $avgRangeLength;
            $avgY /= $avgRangeLength;

            // Get the range for this bucket
            $rangeOffs = (int)(floor( ($i + 0) * $every ) + 1);
            $rangeTo   = (int)(floor( ($i + 1) * $every ) + 1);

            // Point a
            $pointAX = strtotime($data[$a]['time_iso']) * 1000;
            $pointAY = (float)$data[$a]['value'];

            $maxArea = -1;
            $area = -1;
            $maxAreaPoint = -1;

            for (; $rangeOffs < $rangeTo; $rangeOffs++) {
                // Calculate triangle area over three buckets
                $pointBX = strtotime($data[$rangeOffs]['time_iso']) * 1000;
                $pointBY = (float)$data[$rangeOffs]['value'];

                $area = abs( ($pointAX - $avgX) * ($pointBY - $pointAY) - ($pointAX - $pointBX) * ($avgY - $pointAY) ) * 0.5;
                if ($area > $maxArea) {
                    $maxArea = $area;
                    $maxAreaPoint = $rangeOffs;
                }
            }

            // In case of identical points or precision issues resulting in -1
            if ($maxAreaPoint === -1 && $rangeTo > $rangeOffs) {
                $maxAreaPoint = $rangeOffs - 1; // pick the last eval'd
            } else if ($maxAreaPoint === -1) {
                $maxAreaPoint = $rangeOffs;
            }

            $sampled[] = $data[$maxAreaPoint];
            $a = $maxAreaPoint;
        }

        $sampled[] = $data[$dataLength - 1]; // Always add last

        return $sampled;
    }

    /**
     * Applies LTTB downsampling on a Generator/Iterator stream.
     * 
     * This method is specifically designed for unbuffered database queries (e.g. PDO Unbuffered).
     * By consuming the data as a forward-only stream in tightly controlled memory arrays (buckets),
     * it ensures O(1) memory usage regardless of the millions of rows processed from the data source.
     *
     * @param \Iterator<int, array{time_iso: string, value: string, flag: string|int}> $stream The unbuffered data generator.
     * @param int $dataLength The pre-calculated total number of rows in the generator/stream.
     * @param int $threshold The desired maximum number of data points to return.
     * 
     * @return array<int, array{time_iso: string, value: string, flag: string|int}> The visually-representative downsampled dataset.
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
        $currentIdx = 1;

        $lastPoint = $firstPoint;

        // Pre-fill Bucket 0 and Bucket 1
        $bucketEndIdx = (int)(floor(1 * $every) + 1);
        $nextBucketEndIdx = (int)(floor(2 * $every) + 1);

        $currentBucket = [];
        while ($stream->valid() && $currentIdx < $bucketEndIdx) {
            $lastPoint = $stream->current();
            $currentBucket[] = $lastPoint;
            $currentIdx++;
            $stream->next();
        }

        $nextBucket = [];
        while ($stream->valid() && $currentIdx < $nextBucketEndIdx) {
            $lastPoint = $stream->current();
            $nextBucket[] = $lastPoint;
            $currentIdx++;
            $stream->next();
        }

        for ($i = 0; $i < $threshold - 2; $i++) {
            // Calculate average of the next bucket
            $avgX = 0;
            $avgY = 0;
            $avgRangeLength = count($nextBucket);
            
            if ($avgRangeLength > 0) {
                foreach ($nextBucket as $row) {
                    $avgX += strtotime($row['time_iso']) * 1000;
                    $avgY += (float)$row['value'];
                }
                $avgX /= $avgRangeLength;
                $avgY /= $avgRangeLength;
            }

            // Calculate area over current bucket
            $pointAX = strtotime($a['time_iso']) * 1000;
            $pointAY = (float)$a['value'];

            $maxArea = -1;
            $maxAreaPoint = null;

            foreach ($currentBucket as $row) {
                $pointBX = strtotime($row['time_iso']) * 1000;
                $pointBY = (float)$row['value'];

                $area = abs( ($pointAX - $avgX) * ($pointBY - $pointAY) - ($pointAX - $pointBX) * ($avgY - $pointAY) ) * 0.5;
                if ($area > $maxArea) {
                    $maxArea = $area;
                    $maxAreaPoint = $row;
                }
            }

            if ($maxAreaPoint === null && count($currentBucket) > 0) {
                $maxAreaPoint = $currentBucket[count($currentBucket) - 1]; // fallback
            }

            if ($maxAreaPoint) {
                $sampled[] = $maxAreaPoint;
                $a = $maxAreaPoint;
            }

            // Shift buckets
            $currentBucket = $nextBucket;

            // Load new next bucket
            $targetNextBucketEndIdx = (int)(floor(($i + 3) * $every) + 1);
            if ($targetNextBucketEndIdx > $dataLength) {
                $targetNextBucketEndIdx = $dataLength;
            }

            $nextBucket = [];
            while ($stream->valid() && $currentIdx < $targetNextBucketEndIdx) {
                $lastPoint = $stream->current();
                $nextBucket[] = $lastPoint;
                $currentIdx++;
                $stream->next();
            }
        }

        // Exhaust the rest of the stream to get the absolute VERY LAST point
        while ($stream->valid()) {
            $lastPoint = $stream->current();
            $stream->next();
        }
        
        $sampled[] = $lastPoint;

        return $sampled;
    }
}
