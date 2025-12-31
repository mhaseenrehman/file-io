<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Intervention\Image\Drivers\Gd\Encoders\PngEncoder;
use Intervention\Image\Drivers\Gd\Encoders\WebpEncoder;
use App\Models\ImageFile;
use App\Services\CompressionService;
use App\Jobs\CompressVideoJob;
use Inertia\Inertia;
use Inertia\Response;

class ImageController extends Controller
{
    protected $compressionService;

    /**
     * Show page using inertia.js
     */
    public function show(): Response {
        return Inertia::render('Functionality/Compression');
    }

    /**
     * Request image compression and queue job
     */
    public function imageCompress(Request $request) {
        try {
            // Utilises Intervention PHP Image manipulation library
            $image = $request->file('image');
            $format = strtolower($request->input("format"));
            $quality = (int)$request->input("quality");
            $width = (int)$request->input("width", null);

            // Need to store file on system for async compression and retrieval
            $imageFile = $this->compressionService->storeFile($image);

            // Queue async job for compression
            CompressVideoJob::dispatch($imageFile);

            // Return JSON response
            return response()->json([
                'message' => 'Successfully Queued file for Compression',
                'request_id' => $imageFile->id,
                'current_status' => $imageFile->current_status
            ], 200);

            // return response()->json([
            //     'filename' => $filenames->toJson(),
            //     'image_data' => $downloadableLinks->toJson(),
            //     'original_image_size' => $originalSizeKBs->toJson(),
            //     'compressed_image_size' => $compressedSizeKBs->toJson()
            // ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ping for status update on compression process
     */
    public function imageStatusPing($fileId) {
        try {
            $file = $this->compressionService->getFileDetails($fileId);

            $response = [
                'id' => $file->id,
                'orig_name' => $file->orig_name,
                'orig_size' => $file->orig_size,
                'current_status' => $file->current_status
            ];

            if ($file->current_status === 'complete') {
                $response['compressed_size'] = $file->compressed_size;
                $response['download_link'] = $this->compressionService->getDownloadLink($file);
            } elseif ($file->current_status === 'failed') {
                $response['error'] = $file->error_message;
            }

            return response()->json($response);

        } catch (\Exception $e) {}
    }

    /**
     * Request to download image
     */
    public function imageDownload($fileId) {
        try {
            switch($this->format) {
                case 'jpg':
                case 'jpeg':
                    $extension = 'jpg';
                    break;
                
                case 'png':
                    $extension = 'png';
                    break;

                case 'webp':
                default:
                    $extension = 'webp';
                    break;
            }

            $file = ImageFile::findOrFail($fileId);
            $path = $file->compressed_path;
            $filename = pathinfo($imageFile->orig_name, PATHINFO_FILENAME) . 'compressed_' . $extension;

            if (!Storage::exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return Storage::download($path, $filename);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed' . $e->getMessage()], 500);
        }
    }
}
