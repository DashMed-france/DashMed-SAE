<?php

namespace modules\services;

/**
 * Class DownsamplingService
 *
 * Implements data downsampling algorithms such as LTTB (Largest Triangle Three Buckets)
 * to reduce the number of data points while preserving the visual shape of the data.
 */
class DownsamplingService
{
    /**
     * Applies the Largest Triangle Three Buckets (LTTB) downsampling algorithm.
     *
     * @param array<int, array{time_iso: string, value: string, flag: string}> $data
     * @param int $threshold Max number of points to return
     * @return array<int, array{time_iso: string, value: string, flag: string}>
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
     * Applies LTTB downsampling on a Generator/Iterator stream to keep memory O(1).
     * Needs the total count of elements beforehand to calculate bucket sizes.
     *
     * @param \Iterator $stream PDO Statement or Generator
     * @param int $dataLength Total number of rows in the stream
     * @param int $threshold Max number of data points to return
     * @return array<int, array{time_iso: string, value: string, flag: string}>
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

        // Since we can only stream forward, we need to buffer 2 buckets at a time (Current Bucket + Next Bucket for average)
        // But to keep it simple and truly O(1) memory, we can read chunks.
        
        $bucketData = [];
        $nextBucketData = [];
        
        // Advance stream to fill first nextBucket (bucket 1) -> Actually bucket $i+1
        $targetNextBucketEnd = (int)( floor( (0 + 2) * $every ) + 1 );
        
        while ($stream->valid() && $currentIdx < $targetNextBucketEnd) {
            $nextBucketData[] = $stream->current();
            $currentIdx++;
            $stream->next();
        }

        for ($i = 0; $i < $threshold - 2; $i++) {
            // The "current" bucket we are evaluating is what was "nextBucket" in the previous iteration, up to rangeTo.
            // But wait, it's easier to just hold window of arrays. Max bucket size is $dataLength / $threshold.
            // If data is 2M and threshold is 250, bucket is ~8000 elements. 8000 elements in array is ~2MB. Perfectly safe.
            
            $bucketStartIdx = (int)(floor( ($i + 0) * $every ) + 1);
            $bucketEndIdx   = (int)(floor( ($i + 1) * $every ) + 1);
            
            $nextBucketStartIdx = $bucketEndIdx;
            $nextBucketEndIdx   = (int)(floor( ($i + 2) * $every ) + 1);
            $nextBucketEndIdx = $nextBucketEndIdx < $dataLength ? $nextBucketEndIdx : $dataLength;

            $bucketData = $nextBucketData; 
            // We only need the parts from $bucketStartIdx to $bucketEndIdx
            // Since we loaded up to $nextBucketEndIdx in previous steps, $bucketData already has everything for the current bucket.
            // Wait, we need to load the NEW next bucket.
            
            $nextBucketData = [];
            while ($stream->valid() && $currentIdx < $nextBucketEndIdx) {
                $nextBucketData[] = $stream->current();
                $currentIdx++;
                $stream->next();
            }

            // Calculate average of the next bucket
            $avgX = 0;
            $avgY = 0;
            $avgRangeLength = count($nextBucketData);
            
            if ($avgRangeLength > 0) {
                foreach ($nextBucketData as $row) {
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

            foreach ($bucketData as $row) {
                $pointBX = strtotime($row['time_iso']) * 1000;
                $pointBY = (float)$row['value'];

                $area = abs( ($pointAX - $avgX) * ($pointBY - $pointAY) - ($pointAX - $pointBX) * ($avgY - $pointAY) ) * 0.5;
                if ($area > $maxArea) {
                    $maxArea = $area;
                    $maxAreaPoint = $row;
                }
            }

            if ($maxAreaPoint === null && count($bucketData) > 0) {
                $maxAreaPoint = $bucketData[count($bucketData) - 1]; // fallback
            }

            if ($maxAreaPoint) {
                $sampled[] = $maxAreaPoint;
                $a = $maxAreaPoint;
            }
        }

        // Exhaust the rest of the stream to get the absolute VERY LAST point
        $lastPoint = $a; // fallback
        while ($stream->valid()) {
            $lastPoint = $stream->current();
            $stream->next();
        }
        
        $sampled[] = $lastPoint;

        return $sampled;
    }
}
