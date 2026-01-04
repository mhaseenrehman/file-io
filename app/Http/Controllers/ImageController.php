<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ImageFile;
use App\Services\CompressionService;
use App\Jobs\CompressVideoJob;
use Inertia\Inertia;
use Inertia\Response;

class ImageController extends Controller
{
    protected $compressionService;

    public function __construct(CompressionService $compService) {
        $this->compressionService = $compService;
    }

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
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                
                $i = 0;
                $indices = $request->input('indices');
                $responseIds = [];
                foreach ($images as $image) {
                    $format = strtolower($request->input("format"));
                    $quality = (int)$request->input("quality");
                    $width = (int)$request->input("width", null);
                    
                    // Need to store file on system for async compression and retrieval
                    $imageFile = $this->compressionService->storeFile($image, $indices[$i], 'image');
                    $responseIds[$indices[$i]] = $imageFile->id;
                    $i = $i + 1;

                    // Queue async job for compression
                    CompressVideoJob::dispatch($imageFile, $format, $quality, $width);
                }

                // Return JSON response
                return response()->json([
                    'message' => 'Successfully Queued file for Compression',
                    //'request_id' => $imageFile->id,
                    'current_status' => $imageFile->current_status,
                    'request_ids' => $responseIds
                ], 200);

            } else {
                throw new \Exception('No Files found');
            }

        } catch (\Exception $e) {
            // Return JSON response
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Ping for status update on compression process
     */
    public function imageStatusPing(Request $request) {
        try {
            $id = $request->input('id');
            $file = $this->compressionService->getFileDetails($id, 'image');

            $response = [
                'id' => $file->id,
                'orig_name' => $file->orig_name,
                'orig_size' => $file->orig_size,
                'file_request_index' => $file->file_request_index,
                'current_status' => $file->current_status
            ];

            if ($file->current_status === 'complete') {
                $response['compressed_size'] = $file->compressed_size;
                //$response['download_link'] = $this->compressionService->getDownloadLink($file);
            } elseif ($file->current_status === 'failed') {
                $response['error'] = $file->error_message;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'idUsed' => $request->input('id')], 500);
        }
    }

    /**
     * Request to download image
     */
    public function imageDownload(Request $request) {
        $id = $request->input('id');
        $format = $request->input('format');

        try {
            switch($format) {
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

            $file = ImageFile::findOrFail($id);
            $path = $file->compressed_path;
            $filename = pathinfo($file->orig_path, PATHINFO_FILENAME) . '_compressed.' . $extension;

            if (!Storage::exists($path)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            return Storage::download($path, $filename);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed' . $e->getMessage()], 500);
        }
    }
}
