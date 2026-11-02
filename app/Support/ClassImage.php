<?php

namespace App\Support;

use App\Models\Program;
use App\Models\TeachingClass;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A class's promotional image. Like the instructor's photo, every upload is decoded and saved
 * again (here as a 16:10 JPEG, the shape of the cards on the public page), so what is served is
 * always a plain image with no EXIF data.
 */
class ClassImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 750;

    /** Larger images are refused before decoding (memory). */
    public const MAX_PIXELS = 25_000_000;

    /**
     * The JPEG bytes of an upload, cropped from the centre to 16:10. Throws when it is not a readable image.
     */
    public function render(UploadedFile $file): string
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false || (int) $info[0] * (int) $info[1] > self::MAX_PIXELS) {
            throw new RuntimeException('Unreadable or too large image.');
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {
            throw new RuntimeException('Unreadable image.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $ratio = self::WIDTH / self::HEIGHT;

        // Largest 16:10 area centred in the image.
        $cropWidth = $width / $height > $ratio ? (int) round($height * $ratio) : $width;
        $cropHeight = $width / $height > $ratio ? $height : (int) round($width / $ratio);

        // Small images are not enlarged.
        $scale = min(1, self::WIDTH / $cropWidth);
        $targetWidth = max(1, (int) round($cropWidth * $scale));
        $targetHeight = max(1, (int) round($cropHeight * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            intdiv($width - $cropWidth, 2),
            intdiv($height - $cropHeight, 2),
            $targetWidth,
            $targetHeight,
            $cropWidth,
            $cropHeight,
        );

        ob_start();
        imagejpeg($canvas, null, 85);

        return (string) ob_get_clean();
    }

    /**
     * Save rendered bytes as the model's image (a class, or a program in its own folder) and
     * delete the one it replaces.
     */
    public function attach(TeachingClass|Program $model, string $jpeg): string
    {
        $directory = $model instanceof Program ? Program::IMAGE_DIRECTORY : TeachingClass::IMAGE_DIRECTORY;
        $path = $directory.'/'.$model->instructor_id.'/'.Str::random(32).'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        $old = $model->image_path;
        $model->forceFill(['image_path' => $path])->save();

        if ($old !== null) {
            Storage::disk('public')->delete($old);
        }

        return $path;
    }

    public function delete(TeachingClass|Program $model): void
    {
        if ($model->image_path !== null) {
            Storage::disk('public')->delete($model->image_path);
            $model->forceFill(['image_path' => null])->save();
        }
    }
}
