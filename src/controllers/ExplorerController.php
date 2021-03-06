<?php
/**
 * @link      https://dukt.net/craft/videos/
 * @copyright Copyright (c) 2018, Dukt
 * @license   https://dukt.net/craft/videos/docs/license
 */

namespace dukt\videos\controllers;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;
use dukt\videos\errors\GatewayNotFoundException;
use dukt\videos\errors\VideoNotFoundException;
use dukt\videos\Plugin as Videos;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Explorer controller
 */
class ExplorerController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $explorerNav;

    // Public Methods
    // =========================================================================

    /**
     * Get Explorer Modal
     *
     * @return null
     */
    public function actionGetModal()
    {
        $this->requireAcceptsJson();

        $namespaceInputId = Craft::$app->getRequest()->getBodyParam('namespaceInputId');

        $gateways = [];
        $gatewaySections = [];
        $allGateways = Videos::$plugin->getGateways()->getGateways();

        foreach ($allGateways as $_gateway) {
            try {
                $gatewaySection = $_gateway->getExplorerSections();

                if($gatewaySection) {
                    $gatewaySections[] = $gatewaySection;

                    $gateway = [
                        'name' => $_gateway->getName(),
                        'handle' => $_gateway->getHandle(),
                        'supportsSearch' => $_gateway->supportsSearch(),
                    ];

                    $gateways[] = $gateway;
                }
            } catch (IdentityProviderException $e) {
                $errorMsg = $e->getMessage();

                $data = $e->getResponseBody();

                if (isset($data['error_description'])) {
                    $errorMsg = $data['error_description'];
                }

                Craft::error('Couldn’t load gateway `'.$_gateway->getHandle().'`: '.$errorMsg, __METHOD__);
            }
        }

        return $this->asJson([
            'success' => true,
            'html' => Craft::$app->getView()->renderTemplate('videos/_elements/explorer', [
                'namespaceInputId' => $namespaceInputId,
                'gateways' => $gateways,
                'gatewaySections' => $gatewaySections,
                'jsonGateways' => Json::encode($gateways)
            ])
        ]);
    }

    /**
     * Get Videos
     *
     * @return null
     */
    public function actionGetVideos()
    {
        $this->requireAcceptsJson();

        $gatewayHandle = Craft::$app->getRequest()->getParam('gateway');
        $gatewayHandle = strtolower($gatewayHandle);

        $method = Craft::$app->getRequest()->getParam('method');
        $options = Craft::$app->getRequest()->getParam('options', []);

        $gateway = Videos::$plugin->getGateways()->getGateway($gatewayHandle);

        if (!$gateway) {
            throw new GatewayNotFoundException("Gateway not found.");
        }

        $videosResponse = $gateway->getVideos($method, $options);

        $html = Craft::$app->getView()->renderTemplate('videos/_elements/videos', [
            'videos' => $videosResponse['videos']
        ]);

        return $this->asJson([
            'html' => $html,
            'more' => $videosResponse['more'],
            'moreToken' => $videosResponse['moreToken']
        ]);

    }

    /**
     * Field Preview
     *
     * @return null
     */
    public function actionFieldPreview()
    {
        $this->requireAcceptsJson();

        $url = Craft::$app->getRequest()->getParam('url');

        $video = Videos::$plugin->getCache()->get(['fieldPreview', $url]);

        if (!$video) {
            $video = Videos::$plugin->getVideos()->getVideoByUrl($url);

            if (!$video) {
                throw new VideoNotFoundException("Video not found.");
            }

            Videos::$plugin->getCache()->set(['fieldPreview', $url], $video);
        }

        return $this->asJson(
            [
                'video' => $video,
                'preview' => Craft::$app->getView()->renderTemplate('videos/_elements/fieldPreview', ['video' => $video])
            ]
        );
    }

    /**
     * Player
     *
     * @return null
     */
    public function actionPlayer()
    {
        $this->requireAcceptsJson();

        $gatewayHandle = strtolower(Craft::$app->getRequest()->getParam('gateway'));
        $videoId = Craft::$app->getRequest()->getParam('videoId');

        $video = Videos::$plugin->getVideos()->getVideoById($gatewayHandle, $videoId);

        $html = Craft::$app->getView()->renderTemplate('videos/_elements/player', [
            'video' => $video
        ]);

        return $this->asJson([
            'html' => $html
        ]);
    }
}
