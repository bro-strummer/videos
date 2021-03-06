<?php
/**
 * @link      https://dukt.net/craft/videos/
 * @copyright Copyright (c) 2018, Dukt
 * @license   https://dukt.net/craft/videos/docs/license
 */

namespace dukt\videos\web\twig\variables;

use dukt\videos\Plugin as Videos;

class VideosVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Get Embed
     */
    public function getEmbed($videoUrl, $embedOptions = [])
    {
        return Videos::$plugin->getVideos()->getEmbed($videoUrl, $embedOptions);
    }

    /**
     * Get a video from its URL
     */
    public function getVideoByUrl($videoUrl, $enableCache = true, $cacheExpiry = 3600)
    {
        try {
            return Videos::$plugin->getVideos()->getVideoByUrl($videoUrl, $enableCache, $cacheExpiry);
        } catch (\Exception $e) {
            Craft::info('Couldn’t get video from its url ('.$videoUrl.'): '.$e->getMessage(), __METHOD__);
        }
    }

    /**
     * Alias for getVideoByUrl()
     */
    public function url($videoUrl, $enableCache = true, $cacheExpiry = 3600)
    {
        $this->getVideoByUrl($videoUrl, $enableCache, $cacheExpiry);
    }
}
