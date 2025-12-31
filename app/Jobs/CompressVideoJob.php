<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Events\VideoCompressedEvent;
use App\Services\CompressionService;

class CompressVideoJob implements ShouldQueue
{
    use Queueable;

    // Attributes for Single image compression
    protected $imageFile;
    protected $format;
    protected $quality;
    protected $width;

    /**
     * Create a new job instance.
     */
    public function __construct($imageFile, $format, $quality, $width)
    {
        // Construct Video Compression Job Details
        $this->imageFile = $imageFile;
        $this->format = $format;
        $this->quality = $quality;
        $this->width = $width;
    }

    /**
     * Execute the job.
     */
    public function handle(CompressionService $cs): void
    {
        try {
            $cs->compressImage($this->imageFile, $this->format, $this->quality, $this->width);
        } catch (\Exception $e) {}
    }

    /**
     * Handle failed job.
     */
    public function failed() {

    }
}
