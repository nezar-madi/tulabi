<?php

namespace App\Support;

use App\Models\Instructor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The instructor's photo. Every upload is decoded and saved again as a square JPEG, so
 * what is served is always a plain image (no EXIF location, no file posing as an image).
 */
class InstructorPhoto
{
    public const SIZE = 512;

    /** Larger images are refused before decoding (memory). */
    public const MAX_PIXELS = 25_000_000;

    public function store(Instructor $instructor, UploadedFile $file): string
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false || (int) $info[0] * (int) $info[1] > self::MAX_PIXELS) {
            throw new RuntimeException('Unreadable or too large image.');
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('Unreadable image.');
        }

        // Centre square crop, then scale down to SIZE (small images are not enlarged).
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $target = min(self::SIZE, $side);

        $canvas = imagecreatetruecolor($target, $target);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            intdiv($width - $side, 2),
            intdiv($height - $side, 2),
            $target,
            $target,
            $side,
            $side,
        );

        ob_start();
        imagejpeg($canvas, null, 85);
        $jpeg = (string) ob_get_clean();

        $path = Instructor::PHOTO_DIRECTORY.'/'.$instructor->getKey().'/'.Str::random(32).'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        $old = $instructor->photo_path;
        $instructor->forceFill(['photo_path' => $path])->save();

        if ($old !== null) {
            Storage::disk('public')->delete($old);
        }

        return $path;
    }

    public function delete(Instructor $instructor): void
    {
        if ($instructor->photo_path !== null) {
            Storage::disk('public')->delete($instructor->photo_path);
            $instructor->forceFill(['photo_path' => null])->save();
        }
    }
}
