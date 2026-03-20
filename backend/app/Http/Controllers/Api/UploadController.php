<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class UploadController extends Controller
{
    /**
     * Upload an image and return its URL.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('images/inspections', 'public');
            
            // Build URL using the request's host so it works from LAN devices
            $url = $request->getSchemeAndHttpHost() . Storage::url($path);

            return response()->json([
                'url' => $url,
                'path' => $path,
                'message' => 'Image uploaded successfully'
            ], 201);
        }

        return response()->json([
            'message' => 'No image file provided'
        ], 400);
    }
}
