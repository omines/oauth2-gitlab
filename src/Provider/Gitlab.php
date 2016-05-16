<?php

/*
 * Gitlab OAuth2 Provider
 * (c) Omines Internetbureau B.V. - www.omines.nl
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Omines\OAuth2\Client\Provider\Exception\GitlabIdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * Gitlab.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Gitlab extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Domain.
     *
     * @var string
     */
    public $domain = 'https://gitlab.com';

    /**
     * Gitlab constructor.
     *
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options, array $collaborators = [])
    {
        if (isset($options['domain'])) {
            $this->domain = $options['domain'];
        }
        parent::__construct($options, $collaborators);
    }

    /**
     * Get authorization url to begin OAuth flow.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/oauth/authorize';
    }

    /**
     * Get access token url to retrieve token.
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain . '/oauth/access_token';
    }

    /**
     * Get provider url to fetch user details.
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domain . '/api/v3/user';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @throws IdentityProviderException
     * @param  ResponseInterface         $response
     * @param  string                    $data     Parsed response data
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw GitlabIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw GitlabIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param  array                                                 $response
     * @param  AccessToken                                           $token
     * @return \League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new GitlabResourceOwner($response);

        return $user->setDomain($this->domain);
    }
}
