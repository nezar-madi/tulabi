<?php

namespace App\Support;

use App\Models\Program;
use App\Models\TeachingClass;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A class's or a program's promotional image. Like the instructor's photo, every upload is decoded
 * and saved again as a JPEG, so what is served is always a plain image with no EXIF data. The
 * whole picture is kept (owner's decision 2026-11-05: images must not be cut): it is only made
 * smaller to fit a box, never cropped. The cards of the public page frame it themselves.
 */
class ClassImage
{
    /** The longest side after saving; smaller images are not enlarged. */
    public const MAX_SIDE = 1600;

    /** Larger images are refused before decoding (memory). */
    public const MAX_PIXELS = 25_000_000;

    /**
     * The JPEG bytes of an upload, the whole picture scaled down to fit MAX_SIDE. Throws when it is not a readable image.
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

        // Small images are not enlarged.
        $scale = min(1, self::MAX_SIDE / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        // A transparent PNG goes onto white (a JPEG has no transparency).
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

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

    /** A new course starts with its program's image (its own copy, so changing either leaves the other alone). */
    public function copyFromProgram(TeachingClass $class, Program $program): void
    {
        $disk = Storage::disk('public');

        if ($program->image_path !== null && $disk->exists($program->image_path)) {
            $this->attach($class, (string) $disk->get($program->image_path));
        }
    }

    public function delete(TeachingClass|Program $model): void
    {
        if ($model->image_path !== null) {
            Storage::disk('public')->delete($model->image_path);
            $model->forceFill(['image_path' => null])->save();
        }
    }
}
