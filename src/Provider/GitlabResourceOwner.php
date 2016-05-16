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
    private $data;

    private $domain;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->data = $response;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return int
     */
    public function getId()
    {
        return (int) $this->get('id');
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param  string $domain
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * The full name of the owner.
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * Username of the owner.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->get('username');
    }

    /**
     * Email address of the owner.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->get('email');
    }

    /**
     * URL to the user's avatar.
     *
     * @return string|null
     */
    public function getAvatarUrl()
    {
        return $this->get('avatar_url');
    }

    /**
     * URL to the user's profile page.
     *
     * @return string
     */
    public function getProfileUrl()
    {
        return $this->get('web_url');
    }

    /**
     * Whether the user is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->get('state') === 'active';
    }

    /**
     * Whether the user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return (bool) $this->get('is_admin', false);
    }

    /**
     * Whether the user is external.
     *
     * @return bool
     */
    public function isExternal()
    {
        return (bool) $this->get('external', true);
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param  string     $key
     * @param  mixed|null $default
     * @return mixed|null
     */
    protected function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
}
