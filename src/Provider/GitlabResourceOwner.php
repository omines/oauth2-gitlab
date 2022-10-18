<?php

/*
 * Gitlab OAuth2 Provider
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\OAuth2\Client\Provider;

use Gitlab\Client;
use Gitlab\HttpClient\Builder;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;

/**
 * GitlabResourceOwner.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class GitlabResourceOwner implements ResourceOwnerInterface
{
    public const PATH_API = '/api/v4/';

    private array $data;
    private string $domain;
    private AccessToken $token;

    /**
     * Creates new resource owner.
     */
    public function __construct(array $response, AccessToken $token)
    {
        $this->data = $response;
        $this->token = $token;
    }

    /**
     * Returns the identifier of the authorized resource owner.
     */
    public function getId(): int
    {
        return (int) $this->get('id');
    }

    /**
     * Returns an authenticated API client.
     *
     * Requires optional Gitlab API client to be installed.
     *
     * @infection-ignore-all
     */
    public function getApiClient(Builder $builder = null): Client
    {
        if (!class_exists('\\Gitlab\\Client')) {
            throw new \LogicException(__METHOD__ . ' requires package m4tthumphrey/php-gitlab-api to be installed and autoloaded'); // @codeCoverageIgnore
        }
        $client = new Client($builder);
        $client->setUrl(rtrim($this->domain, '/') . self::PATH_API);
        $client->authenticate($this->token->getToken(), Client::AUTH_OAUTH_TOKEN);

        return $client;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * The full name of the owner.
     */
    public function getName(): string
    {
        return $this->get('name');
    }

    /**
     * Username of the owner.
     */
    public function getUsername(): string
    {
        return $this->get('username');
    }

    /**
     * Email address of the owner.
     */
    public function getEmail(): string
    {
        return $this->get('email');
    }

    /**
     * URL to the user's avatar.
     */
    public function getAvatarUrl(): ?string
    {
        return $this->get('avatar_url');
    }

    /**
     * URL to the user's profile page.
     */
    public function getProfileUrl(): ?string
    {
        return $this->get('web_url');
    }

    public function getToken(): AccessToken
    {
        return $this->token;
    }

    /**
     * Whether the user is active.
     */
    public function isActive(): bool
    {
        return 'active' === $this->get('state');
    }

    /**
     * Whether the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->get('is_admin', false);
    }

    /**
     * Whether the user is external.
     */
    public function isExternal(): bool
    {
        return $this->get('external', true);
    }

    /**
     * Return all of the owner details available as an array.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
