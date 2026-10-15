<?php

namespace App\Helpers;

use Auth;
use Session;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Helper {
    public static function image($path = null, $width = null, $height = null, $public_path = false)
    {
        if(is_null($path) || $path == "") return null;
        
        if(Str::startsWith($path, url(''))) $path = trim(str_replace(url(''), '', $path), '/');

        $replace = 'storage';
        $path = trim($path, '/');
        if(Str::startsWith($path, $replace)) $path = trim(substr($path, strlen($replace)), '/');

        $width = intval($width) > 5000 ? 0 : intval($width);
        $height = intval($height) > 5000 ? 0 : intval($height);

        if(($width == 0 || $height == 0) && Storage::disk('public')->exists($path)) return $public_path ? public_path('storage/'.$path) : asset('storage/'.$path);
        if(Storage::disk('public')->exists($path)) {
            if(Str::lower(pathinfo($path, PATHINFO_EXTENSION)) === "svg") return $public_path ? public_path('storage/'.$path) : asset('storage/'.$path);

            if($width > 0 && $height > 0 && Storage::disk('public')->exists('cache/'.$width.'x'.$height.'/'.$path)) return $public_path ? public_path('storage/cache/'.$width.'x'.$height.'/'.$path) : asset('storage/cache/'.$width.'x'.$height.'/'.$path);
            if($width > 0 && $height > 0) {
                try {
                    $manager = new ImageManager(new Driver());

                    $originalImage = $manager->read(Storage::disk('public')->get($path));

                    if($width == 5000 && $height < 5000) Storage::disk('public')->put('cache/'.$width.'x'.$height.'/'.$path, $originalImage->resize(null, $height)->encode());
                    else if($height == 5000 && $width < 5000) Storage::disk('public')->put('cache/'.$width.'x'.$height.'/'.$path, $originalImage->resize($width, null)->encode());
                    else Storage::disk('public')->put('cache/'.$width.'x'.$height.'/'.$path, $originalImage->resize($width,$height)->encode());

                    if(Storage::disk('public')->exists('cache/'.$width.'x'.$height.'/'.$path)) return $public_path ? public_path('storage/cache/'.$width.'x'.$height.'/'.$path) : asset('storage/cache/'.$width.'x'.$height.'/'.$path);
                } catch (Exception $e) {}

                return $public_path ? public_path('storage/'.$path) : asset('storage/'.$path);
            }
        }

        return null;
    }

    public static function deleteImage($oldImage)
    {
        try {
            if(is_null($oldImage)) return false;
            
            if(Storage::disk('public')->exists($oldImage)) Storage::disk('public')->delete($oldImage);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function saveFile($file, $folder, $title, $oldFile = null)
    {
        try {
            if(!is_null($oldFile) && Storage::exists('private/'.$oldFile)) Storage::delete('private/'.$oldFile);

            if($title === "" || !is_string($title)) $title = Str::random(40);

            $time = time();
            $date = date('d-m-Y');
            $filename = $time.'_'.Str::limit(Str::slug($title), 100, '').'.'.$file->getClientOriginalExtension();

            $br = 2;
            while(Storage::exists('private/'.$folder.'/'.$date.'/'.$filename)) {
                $filename = $time.'_'.Str::limit(Str::slug($title), 100, '').'-'.$br.'.'.$file->getClientOriginalExtension();
                $br++;
            }
            $file->storeAs('private/'.$folder.'/'.$date, $filename);

            return $folder.'/'.$date.'/'.$filename;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function deleteFile($oldFile)
    {
        try {
            if(is_null($oldFile)) return false;
            
            if(Storage::exists('private/'.$oldFile)) Storage::delete('private/'.$oldFile);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function renameImage($oldImage, $folder, $title)
    {
        try {
            if($title === "" || !is_string($title)) $title = Str::random(40);

            $time = time();
            $date = date('d-m-Y');
            $filename = $time.'_'.Str::limit(Str::slug(strip_tags($title)), 100, '').'.'.pathinfo($oldImage, PATHINFO_EXTENSION);
            $br = 2;
            while(Storage::disk('public')->exists($folder.'/'.$date.'/'.$filename)) {
                $filename = $time.'_'.Str::limit(Str::slug(strip_tags($title)), 100, '').'-'.$br.'.'.pathinfo($oldImage, PATHINFO_EXTENSION);
                $br++;
            }

            Storage::disk('public')->move($oldImage, $folder.'/'.$date.'/'.$filename);

            return $folder.'/'.$date.'/'.$filename;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function saveImage($image, $folder, $name, $oldImage = null) 
    {
        try {

            if(!is_null($oldImage) && Storage::disk('public')->exists($oldImage)) Storage::disk('public')->delete($oldImage);

            if ($name === "" || !is_string($name))
                $name = Str::random(40);

            $time = time();
            $date = date('d-m-Y');
            $filename = $time . '_' . Str::limit(Str::slug($name), 100, '') . '.' . $image->getClientOriginalExtension();
            $br = 2;

            while (Storage::disk('public')->exists($folder . '/' . $date . '/' . $filename)) {
                $filename = $time . '_' . Str::limit(Str::slug($name), 100, '') . '-' . $br . '.' . $image->getClientOriginalExtension();
                $br++;
            }

            if (Str::lower($image->getClientOriginalExtension()) === "svg") {
                $image->storeAs($folder . '/' . $date, $filename, 'public');

                return $folder . '/' . $date . '/' . $filename;
            }


            $manager = new ImageManager(new Driver());

            $resizedImage = $manager->read($image)
            ->resize(null, null)
            // function ($image) {
            //     $image->aspectRatio();
            //     $image->preventUpscale();
            // }
            ->encode();

        Storage::disk('public')->put($folder . '/' . $date . '/' . $filename, $resizedImage->toString());

            return $folder . '/' . $date . '/' . $filename;
        } catch (Exception $e) {
            Log::error('Error saving image: ' . $e->getMessage());
            echo ($e);
            return null;
        }
    }
}