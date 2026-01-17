<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\ImageFile;

class DeleteFileJob implements ShouldQueue
{
    use Queueable;

    protected $file;

    /**
     * Create a new job instance.
     */
    public function __construct(ImageFile $f)
    {
        $this->file = $f;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->file->delete();
    }
}
