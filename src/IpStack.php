<?php

namespace Nails\GeoIp\Driver;

use Nails\Common\Driver\Base;
use Nails\Factory;
use Nails\GeoIp;
use Nails\GeoIp\Exception\GeoIpDriverException;
use Nails\GeoIp\Interfaces\Driver;

class IpStack extends Base implements Driver
{
    /**
     * The base url of the ipstack.com service.
     * @var string
     */
    const BASE_URL = 'http://api.ipstack.com';

    // --------------------------------------------------------------------------

    /**
     * The API Key to use.
     * @var string
     */
    protected $sAccessKey;

    // --------------------------------------------------------------------------

    /**
     * @param string $sIp The IP address to look up
     *
     * @return \Nails\GeoIp\Result\Ip
     * @deprecated
     */
    public function lookup($sIp)
    {
        $oHttpClient = Factory::factory('HttpClient');
        $oIp         = Factory::factory('Ip', GeoIp\Constants::MODULE_SLUG);

        $oIp->setIp($sIp);

        try {

            if (empty($this->sAccessKey)) {
                throw new GeoIpDriverException('An IPStack Access Key must be provided.');
            }

            try {

                $oResponse = $oHttpClient->request(
                    'GET',
                    static::BASE_URL . '/' . $sIp,
                    [
                        'query' => [
                            'access_key' => $this->sAccessKey,
                        ],
                    ]
                );

            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $oJson = json_decode($e->getResponse()->getBody());
                if (!empty($oJson->error->message)) {
                    throw new GeoIpDriverException(
                        $oJson->error->message,
                        $e->getCode()
                    );
                } else {
                    throw new GeoIpDriverException(
                        $e->getMessage(),
                        $e->getCode()
                    );
                }
            }

            $oJson = json_decode($oResponse->getBody());

            if (empty($oJson->error)) {

                if (!empty($oJson->city)) {
                    $oIp->setCity($oJson->city);
                }

                if (!empty($oJson->region_name)) {
                    $oIp->setRegion($oJson->region_name);
                }

                if (!empty($oJson->country_name)) {
                    $oIp->setCountry($oJson->country_name);
                }

                if (!empty($oJson->latitude)) {
                    $oIp->setLat($oJson->latitude);
                }

                if (!empty($oJson->longitude)) {
                    $oIp->setLng($oJson->longitude);
                }
            } else {
                throw new GeoIpDriverException(
                    $oJson->error->info,
                    $oJson->error->code
                );
            }

        } catch (\Exception $e) {
            $oIp->setError($e->getMessage());
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        return $oIp;
    }
}
