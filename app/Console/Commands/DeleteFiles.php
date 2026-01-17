<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ImageFile;
use App\Jobs\DeleteFileJob;

class DeleteFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete File command necessary to maintain Database and Storage space';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = ImageFile::where('created_at', '<=', now()->subMinute())->get();

        foreach ($files as $f) {
            //dispatch(); Could use this
            DeleteFileJob::dispatch($f);
        }
    }
}
