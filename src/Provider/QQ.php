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

    public function __get($name)
    {
        return $this->$name;
    }

    public function __call($name, $arguments)
    {
        return $this->$name($arguments);
    }

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
        $this->openid = $this->requestOpenid($token);

        return 'https://graph.qq.com/user/get_user_info?' . http_build_query([
            'access_token'       => $token->getToken(),
            'oauth_consumer_key' => $this->clientId,
            'openid'             => $this->openid,
        ]);
    }

    protected function getOpenidUrl(AccessToken $token)
    {
        return 'https://graph.qq.com/oauth2.0/me?' . http_build_query([
            'access_token' => $token->getToken(),
        ]);
    }

    protected function requestOpenid(AccessToken $token)
    {
        $url     = $this->getOpenidUrl($token);
        $request = $this->getRequest(self::METHOD_GET, $url);
        $data    = $this->getResponse($request);
        $data    = $this->parse_jsonp($data);
        if (!empty($data) && isset($data['openid']))
        {
            $data = $data['openid'];
        }

        return $data;
    }

    public function getOpenId()
    {
        return $this->openid;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    protected function parse_jsonp($jsonp_str)
    {
        if (strpos($jsonp_str, 'callback') !== false)
        {
            $json_str = str_replace('callback( ', '', str_replace(' );', '', $jsonp_str));
            $data = json_decode($json_str, true);
        }

        return isset($data) ? $data : null;
    }

    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param  mixed $grant
     * @param  array $options
     * @return AccessToken
     */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        $params = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
        ];

        $params   = $grant->prepareRequestParameters($params, $options);
        $request  = $this->getAccessTokenRequest($params);
        $response = $this->sendRequest($request);
        $content  = (string)$response->getBody();
        $parsed   = $this->parse_jsonp($content);
        if (!empty($parsed))
        {
            $this->checkResponse($response, $parsed);
        }
        else
        {
            parse_str($content, $parsed);
        }

        $prepared = $this->prepareAccessTokenResponse($parsed);
        $token    = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error']))
        {
            throw new IdentityProviderException(
                $data['error_description'],
                $data['error'],
                $response
            );
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new QQUser($response);
    }
}
