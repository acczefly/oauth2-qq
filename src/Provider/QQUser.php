<?php

namespace Acczefly\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class QQUser implements ResourceOwnerInterface
{
    /**
     * @var array
     */
    protected $response;

    /**
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }

    public function __get($name)
    {
        return $this->response[$name];
    }

    public function getId()
    {
        return null;
    }

    /**
     * Get perferred display name.
     *
     * @return string
     */
    public function getNickname()
    {
        return $this->response['nickname'] ?: null;
    }

    /**
     * Get user data as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
