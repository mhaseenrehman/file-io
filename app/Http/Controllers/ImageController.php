<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Intervention\Image\Drivers\Gd\Encoders\PngEncoder;
use Intervention\Image\Drivers\Gd\Encoders\WebpEncoder;
use Inertia\Inertia;
use Inertia\Response;

class ImageController extends Controller
{
    public function show(): Response {
        return Inertia::render('Functionality/Compression');
    }

    public function imageCompress(Request $request) {
        // Utilises Intervention PHP Image manipulation library
        $imageFile = $request->file('image');
        $format = strtolower($request->input("format"));
        $quality = (int)$request->input("quality");
        $width = (int)$request->input("width", null);

        // Start Intervention
        $manager = new ImageManager(new Driver());
        $image = $manager->read($imageFile->getPathname());
        if ($width) {
            $image->resize($width, null, function ($constrant) {
                $constrant->aspectRatio();
                $constrant->upsize();
            });
        }

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

        // Convert Encoded image data to base64 string
        $encoded = $image->encode($encoder);
        $downloadableLink = base64_encode($encoded->toString());

        // Convert encoded image to a base64 string -> Allows user to download the image
        $filename = 'compressed_' . uniqid() . '.' . $extension;

        // Calculate Sizes in kb - Round to 2 decimal places, getSize() - obtains file size in bytes / 1024 = kilobytes
        $originalSizeKB = round($imageFile->getSize() / 1024, 2);
        $compressedSizeKB = round(strlen($encoded->toString()) / 1024, 2);

        // Return JSON response
        return response()->json([
            'filename' => $filename,
            'image_data' => $downloadableLink,
            'original_image_size' => $originalSizeKB,
            'compressed_image_size' => $compressedSizeKB
        ]);
    }

}
