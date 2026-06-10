<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function secureDownload($id) {
      $attachment = Attachment::findOrFail($id);


            $url = Storage::disk('public')->temporaryUrl(
                $attachment->file_path,
                now()->addMinutes(10) // expira en 10 min
            );

      return redirect()->away($url);
    }

    public function imageDownload($id)
    {
      $user = User::findOrFail($id);

     $url = Storage::disk('public')->temporaryUrl(
                $user->featured_image,
                now()->addMinutes(10) // expira en 10 min
            );

      return redirect()->away($url);
  }
}
