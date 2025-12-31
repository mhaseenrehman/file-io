<?php

namespace App\Services;

use App\Models\ImageFIle;

class CompressionService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Store file on disk
     */
    public function storeFile($file, $type) {
        $origFilename = $file->getClientOriginalName();
        $origSize = $file->getSize();

        // Using 'local' disk location makes it so that root @ /storage/app/private/
        // When retrieving file, ensure get absolute directory -> /storage/app/private/(TYPE)/requests/
        $filename = time() . '_' . uniqid('', true) . $file->extension();
        $storagePath - $type . '/requests';
        $origPath = $file->storeAs($storagePath, $filename, 'local');

        // Create final File
        return ImageFile::create([
            'orig_name' => $origFilename,
            'orig_path' => $origPath,
            'orig_size' => $origSize,
            'orig_format' => $file->extension(),
            'current_status' => 'waiting',
        ]);
    }

    /**
     * Compress image functionality
     *
     */
    public function compressImage(ImageFile $imageFile, $format, $quality, $width) {
        try {
            // Pick encoder
            switch($this->format) {
                case 'jpg':
                case 'jpeg':
                    $encoder = new JpegEncoder($this->quality);
                    $extension = 'jpg';
                    break;
                
                case 'png':
                    $encoder = new PngEncoder($this->quality);
                    $extension = 'png';
                    break;

                case 'webp':
                default:
                    $encoder = new WebpEncoder($this->quality);
                    $extension = 'webp';
                    break;
            }

            // Need to update and then retrieve image from local storage
            $imageFile->update(['current_status' => 'processing']);
            $originalPath = storage_path('app/private/' . $imageFile->orig_path);
            
            $compressedFilename = pathinfo($imageFile->orig_path, PATHINFO_FILENAME) . 'compressed_' . $extension;
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
            if ($this->width) {
                $image->resize($this->width, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Convert Encoded image data to base64 string - to be able to transmit back to frontend &
            // Calculate Sizes in kb - Round to 2 decimal places, getSize() - obtains file size in bytes / 1024 = kilobytes
            // Finally, save the file to local
            $encoded = $image->encode($encoder);
            $downloadableLink = base64_encode($encoded->toString());
            $originalSizeKB = round($this->imageFile->getSize() / 1024, 2);
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

        } catch (\Exception $e) {}
    }

    /**
     * General file compression functionality
     */
    public function compressFile() {}


    /**
     * Get file details from disk
     */
    public function getFileDetails($fileId, $type=null) {
        if (type === 'image') {
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

}
