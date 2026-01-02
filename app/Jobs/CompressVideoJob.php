<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ImageFile;
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

    // Retries and Timeouts
    public $tries = 3;
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(ImageFile $imageFile, $format, $quality, $width)
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

        } catch (\Exception $e) {

            if ($this->attempts() >= $this->tries) {
                $this->imageFile->update([
                    'current_status' => 'failed',
                    'error_message' => 'Max attempts at compression failed'
                ]);
            }

            throw $e;
        }
    }

    /**
     * Handle failed job.
     */
    public function failed(\Exception $e) {
        $this->imageFile->update([
            'current_status' => 'failed',
            'error_message' => 'Compression job failed: ' . $e->getMessage()
        ]);
    }
}
