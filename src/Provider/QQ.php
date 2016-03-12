<?php

namespace Acczefly\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class QQ extends AbstractProvider
{
    use BearerAuthorizationTrait;

    protected $openid;

    public function getBaseAuthorizationUrl()
    {
        return 'https://graph.qq.com/oauth2.0/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://graph.qq.com/oauth2.0/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $this->openid = $this->getOpenid($token);

        return 'https://graph.qq.com/user/get_user_info?' . http_build_query([
            'access_token'       => $token,
            'oauth_consumer_key' => $this->clientId,
            'openid'             => $this->openid,
        ]);
    }

    protected function getOpenidUrl(AccessToken $token)
    {
        return 'https://graph.qq.com/oauth2.0/me?' . http_build_query([
            'access_token' => $token,
        ]);
    }

    protected function getOpenid(AccessToken $token)
    {
        $url     = $this->getOpenidUrl($token);
        $request = $this->getRequest(self::METHOD_GET, $url);
        $data    = $this->getResponse($request);

        if (strpos($data, 'callback') !== false)
        {
            $data = str_replace('callback( ', '', str_replace(' );', '', $data));
            $data = json_decode($data, true);
            $data = $data['openid'];
        }

        return $data;
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error']))
        {
            throw new IdentityProviderException(
                $data['error'] ?: $response->getReasonPhrase(),
                $response->getStatusCode(),
                $response
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new QQUser($response);
    }
}
