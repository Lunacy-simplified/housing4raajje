<?php

namespace App\Http\Controllers;

use App\Models\PropertyPhoto;
use Illuminate\Support\Facades\Storage;

/** Property photos are kept on the private disk and shown only to signed-in users. */
class PhotoController extends Controller
{
    public function show(PropertyPhoto $photo)
    {
        abort_unless(Storage::disk('local')->exists($photo->file_path), 404);

        return response()->file(Storage::disk('local')->path($photo->file_path));
    }
}
