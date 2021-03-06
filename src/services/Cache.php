<?php
/**
 * @link      https://dukt.net/craft/videos/
 * @copyright Copyright (c) 2018, Dukt
 * @license   https://dukt.net/craft/videos/docs/license
 */

namespace dukt\videos\services;

use Craft;
use dukt\videos\Plugin as VideosPlugin;
use yii\base\Component;
use DateInterval;

/**
 * Class Cache service.
 *
 * An instance of the Cache service is globally accessible via [[Plugin::cache `VideosPlugin::$plugin->getCache()`]].
 *
 * @author Dukt <support@dukt.net>
 * @since  2.0
 */
class Cache extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get cache
     *
     * @param $id
     *
     * @return mixed
     */
    public function get($id)
    {
        if (VideosPlugin::$plugin->getSettings()->cacheDuration == true) {
            $cacheKey = $this->getCacheKey($id);

            return Craft::$app->cache->get($cacheKey);
        }
    }

    /**
     * Set cache
     *
     * @param      $id
     * @param      $value
     * @param null $expire
     * @param null $dependency
     * @param null $enableCache
     *
     * @return mixed
     */
    public function set($id, $value, $expire = null, $dependency = null, $enableCache = null)
    {
        if (is_null($enableCache)) {
            $enableCache = VideosPlugin::$plugin->getSettings()->cacheDuration;
        }

        if ($enableCache) {
            $cacheKey = $this->getCacheKey($id);

            if (!$expire) {
                $expire = VideosPlugin::$plugin->getSettings()->cacheDuration;
                $expire = new DateInterval($expire);
                $expire = $expire->format('%s');
            }

            return Craft::$app->cache->set($cacheKey, $value, $expire, $dependency);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Return the cache key
     *
     * @param array $request
     *
     * @return string
     */
    private function getCacheKey(array $request)
    {
        unset($request['CRAFT_CSRF_TOKEN']);

        $hash = md5(serialize($request));

        return 'videos.'.$hash;
    }
}
