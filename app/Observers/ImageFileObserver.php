<?php

namespace App\Observers;

use Illuminate\Support\Facades\Storage;
use App\Models\ImageFile;

class ImageFileObserver
{
    /**
     * Handle the ImageFile "created" event.
     */
    public function created(ImageFile $imageFile): void
    {
        //
    }

    /**
     * Handle the ImageFile "updated" event.
     */
    public function updated(ImageFile $imageFile): void
    {
        //
    }

    /**
     * Handle the ImageFile "deleted" event.
     */
    public function deleted(ImageFile $imageFile): void
    {
        // Delete the original image file uploaded
        if ($imageFile->orig_path && Storage::disk('local')->exists($imageFile->orig_path)) {
            Storage::disk('local')->delete($imageFile->orig_path);
        }

        // Delete the compressed image file saved
        if ($imageFile->compressed_path && Storage::disk('local')->exists($imageFile->compressed_path)) {
            Storage::disk('local')->delete($imageFile->compressed_path);
        }
    }

    /**
     * Handle the ImageFile "restored" event.
     */
    public function restored(ImageFile $imageFile): void
    {
        //
    }

    /**
     * Handle the ImageFile "force deleted" event.
     */
    public function forceDeleted(ImageFile $imageFile): void
    {
        //
    }
}
