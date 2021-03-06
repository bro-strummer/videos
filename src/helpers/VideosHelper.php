<?php
/**
 * @link      https://dukt.net/craft/videos/
 * @copyright Copyright (c) 2018, Dukt
 * @license   https://dukt.net/craft/videos/docs/license
 */

namespace dukt\videos\helpers;

use Craft;
use craft\helpers\FileHelper;
use dukt\videos\Plugin;

/**
 * Videos helper
 */
class VideosHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Formats seconds to hh:mm:ss
     */
    public static function getDuration($seconds)
    {
        $hours = (int)((int)($seconds) / 3600);
        $minutes = (int)(($seconds / 60) % 60);
        $seconds = (int)($seconds % 60);

        $hms = "";

        if ($hours > 0) {
            $hms .= str_pad($hours, 2, "0", STR_PAD_LEFT).":";
        }

        $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT).":";

        $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

        return $hms;
    }

    /**
     * Returns a video thumbnail’s published URL.
     *
     * @param $gatewayHandle
     * @param $videoId
     * @param $size
     *
     * @return null|string
     */
    public static function getVideoThumbnail($gatewayHandle, $videoId, $size)
    {
        $baseDir = Craft::$app->getPath()->getRuntimePath().DIRECTORY_SEPARATOR.'videos'.DIRECTORY_SEPARATOR.'thumbnails'.DIRECTORY_SEPARATOR.$gatewayHandle.DIRECTORY_SEPARATOR.$videoId;
        $originalDir = $baseDir.DIRECTORY_SEPARATOR.'original';
        $dir = $baseDir.DIRECTORY_SEPARATOR.$size;
        $file = null;

        if(is_dir($dir)) {
            $files = FileHelper::findFiles($dir);

            if(count($files) > 0) {
                $file = $files[0];
            }
        }

        if (!$file) {
            // Retrieve original image
            $originalPath = null;

            if(is_dir($originalDir)) {
                $originalFiles = FileHelper::findFiles($originalDir);

                if(count($originalFiles) > 0) {
                    $originalPath = $originalFiles[0];
                }
            }

            if(!$originalPath) {
                $video = Plugin::$plugin->getVideos()->getVideoById($gatewayHandle, $videoId);
                $url = $video->thumbnailSource;

                $name = pathinfo($url, PATHINFO_BASENAME);
                $originalPath = $originalDir.DIRECTORY_SEPARATOR.$name;

                FileHelper::createDirectory($originalDir);
                $client = new \GuzzleHttp\Client();
                $response = $client->request('GET', $url, [
                    'save_to' => $originalPath,
                ]);

                if ($response->getStatusCode() != 200) {
                    return null;
                }
            } else {
                $name = pathinfo($originalPath, PATHINFO_BASENAME);
            }

            // Generate the thumb
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            FileHelper::createDirectory($dir);
            Craft::$app->getImages()->loadImage($originalPath, false, $size)
                ->scaleToFit($size, $size)
                ->saveAs($path);

        } else {
            $name = pathinfo($file, PATHINFO_BASENAME);
        }

        return Craft::$app->getAssetManager()->getPublishedUrl($dir, true)."/{$name}";
    }
}
