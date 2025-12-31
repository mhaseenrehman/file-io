<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;

class CompressionStorageService
{
    private const STORAGE_KEY = 'compression_results';
    private const CLEANUP_INTERVAL = 300; // 5 minutes in seconds

    /**
     * Store compression result in memory
     */
    public function store(string $jobId, array $data): void
    {
        $storage = $this->getStorage();
        $storage[$jobId] = [
            'data' => $data,
            'created_at' => now()->timestamp,
            'expires_at' => now()->addMinutes(10)->timestamp // Keep for 10 minutes
        ];
        
        Session::put(self::STORAGE_KEY, $storage);
    }

    /**
     * Retrieve compression result from memory
     */
    public function retrieve(string $jobId): ?array
    {
        $storage = $this->getStorage();
        
        if (!isset($storage[$jobId])) {
            return null;
        }

        // Check if expired
        if (now()->timestamp > $storage[$jobId]['expires_at']) {
            $this->remove($jobId);
            return null;
        }

        return $storage[$jobId]['data'];
    }

    /**
     * Check if compression result exists
     */
    public function exists(string $jobId): bool
    {
        return $this->retrieve($jobId) !== null;
    }

    /**
     * Remove compression result
     */
    public function remove(string $jobId): void
    {
        $storage = $this->getStorage();
        unset($storage[$jobId]);
        Session::put(self::STORAGE_KEY, $storage);
    }

    /**
     * Clean up expired results
     */
    public function cleanup(): void
    {
        $storage = $this->getStorage();
        $now = now()->timestamp;
        
        foreach ($storage as $jobId => $result) {
            if ($now > $result['expires_at']) {
                unset($storage[$jobId]);
            }
        }
        
        Session::put(self::STORAGE_KEY, $storage);
    }

    /**
     * Get all storage data
     */
    private function getStorage(): array
    {
        $storage = Session::get(self::STORAGE_KEY, []);
        
        // Auto-cleanup if needed
        if (rand(1, 100) <= 10) { // 10% chance each time
            $this->cleanup();
        }
        
        return $storage;
    }

    /**
     * Generate a unique job ID
     */
    public function generateJobId(): string
    {
        return 'comp_' . uniqid() . '_' . time();
    }
}