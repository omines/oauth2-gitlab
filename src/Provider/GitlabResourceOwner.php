<?php

/*
 * Gitlab OAuth2 Provider
 * (c) Omines Internetbureau B.V. - www.omines.nl
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

/**
 * GitlabResourceOwner.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class GitlabResourceOwner implements ResourceOwnerInterface
{
    /**
     * Domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * Raw response.
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id.
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['id'] ?: null;
    }

    /**
     * Get resource owner email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['email'] ?: null;
    }

    /**
     * Get resource owner name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->response['name'] ?: null;
    }

    /**
     * Get resource owner nickname.
     *
     * @return string|null
     */
    public function getNickname()
    {
        return $this->response['login'] ?: null;
    }

    /**
     * Get resource owner url.
     *
     * @return string|null
     */
    public function getUrl()
    {
        return trim($this->domain . '/' . $this->getNickname()) ?: null;
    }

    /**
     * Set resource owner domain.
     *
     * @param string $domain
     *
     * @return ResourceOwner
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
