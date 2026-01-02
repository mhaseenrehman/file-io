<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Intervention\Image\Drivers\Gd\Encoders\PngEncoder;
use Intervention\Image\Drivers\Gd\Encoders\WebpEncoder;

use App\Models\ImageFile;

class CompressionService
{
    
    /**
     * Store file on disk
     */
    public function storeFile($file, $type) {
        $origFilename = $file->getClientOriginalName();
        $origSize = $file->getSize();

        // Using 'local' disk location makes it so that root @ /storage/app/private/
        // When retrieving file, ensure get absolute directory -> /storage/app/private/(TYPE)/requests/
        $filename = time() . '_' . uniqid('', true) . $file->extension();
        if ($type === 'image') { $storagePath = 'images/requests'; }
        $origPath = $file->storeAs($storagePath, $filename, 'local');

        // Create final File
        $result = ImageFile::create([
            'orig_name' => $origFilename,
            'orig_path' => $origPath,
            'orig_size' => $origSize,
            'orig_format' => $file->extension(),
            'current_status' => 'waiting'
        ]);

        return $result;
    }

    //////////////////////////////////////////////////////////
    ////////////////////////// TEST //////////////////////////
    //////////////////////////////////////////////////////////
    /**
     * Compress image functionality
     *
     */
    public function compressImage(ImageFile $imageFile, $format, $quality, $width) {
        try {
            // Pick encoder
            switch($format) {
                case 'jpg':
                case 'jpeg':
                    $encoder = new JpegEncoder($quality);
                    $extension = 'jpg';
                    break;
                
                case 'png':
                    $encoder = new PngEncoder($quality);
                    $extension = 'png';
                    break;

                case 'webp':
                default:
                    $encoder = new WebpEncoder($quality);
                    $extension = 'webp';
                    break;
            }

            // Need to update and then retrieve image from local storage
            $originalPath = storage_path('app/private/' . $imageFile->orig_path);
            $compressedFilename = pathinfo($imageFile->orig_path, PATHINFO_FILENAME) . '_compressed' . $extension;
            $compressedPath = 'images/compressed/' . $compressedFilename;
            $absoluteCompressedPath = storage_path('app/private/' . $compressedPath);

            // Check directory and place correct permissions
            $compressedDir = dirname($absoluteCompressedPath);
            if (!file_exists($compressedDir)) {
                if (!mkdir($compressedDir, 0777, true) && !is_dir($compressedDir)) {
                    throw new \RuntimeException(sprintf('Directory was not created.' . $compressedDir));
                }
            }
            chmod($compressedDir, 0777);
            if (file_exists($originalPath)) {
                chmod($originalPath, 0644);
            }

            // Actual compression process
            // Compression Code for one single file - Start Intervention
            $manager = new ImageManager(new Driver());
            $image = $manager->read($originalPath);
            //$image = $manager->read($imageFile->getPathname());
            if ($width) {
                $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Convert Encoded image data to base64 string - to be able to transmit back to frontend &
            // Calculate Sizes in kb - Round to 2 decimal places, getSize() - obtains file size in bytes / 1024 = kilobytes
            // Finally, save the file to local
            $encoded = $image->encode($encoder);
            $downloadableLink = base64_encode($encoded->toString());
            //$originalSizeKB = round($image->getSize() / 1024, 2);
            $compressedSizeKB = round(strlen($encoded->toString()) / 1024, 2);
            $encoded->save($absoluteCompressedPath);

            // Update the imageFile to ensure it can be easily retrieved
            $imageFile->update([
                'compressed_name' => $compressedFilename,
                'compressed_path' => $compressedPath,
                'compressed_size' => $compressedSizeKB,
                'current_status' => 'complete'
            ]);

            // Return newly updated $imageFile
            return $imageFile;

        } catch (\Exception $e) {
            $imageFile->update([
                'current_status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * General file compression functionality
     */
    public function compressFile() {}


    /**
     * Get file details from disk
     */
    public function getFileDetails($fileId, $type=null) {
        if ($type === 'image') {
            return ImageFile::findOrFail($fileId);
        }
    }

    /**
     * Obtain download link to get file
     */
    public function getDownloadLink(ImageFile $imageFile) {
        $path = $imageFile->compressed_path;
        return url('api/imageDownload/' . $imageFile->id);
    }



    // ----------------------------------------------------- TEST FUNCTIONALITY -----------------------------------------------------
    public function storeFileTest() {
        // Create final File
        $img = ImageFile::create([
            'orig_name' => 'filex.png',
            'orig_path' => 'local/something/something/',
            'orig_size' => '5kb',
            'orig_format' => 'png',
            'current_status' => 'waiting'
        ]);
        return $img;
    }
}
