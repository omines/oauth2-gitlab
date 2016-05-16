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
    public $domain = 'https://github.com';

    /**
     * Api domain.
     *
     * @var string
     */
    public $apiDomain = 'https://api.github.com';

    /**
     * Get authorization url to begin OAuth flow.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/login/oauth/authorize';
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
        return $this->domain . '/login/oauth/access_token';
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
        if ($this->domain === 'https://github.com') {
            return $this->apiDomain . '/user';
        }
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
     * @link   https://developer.github.com/v3/#client-errors
     * @link   https://developer.github.com/v3/oauth/#common-errors-for-the-access-token-request
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
     * @param  array                                                $response
     * @param  AccessToken                                          $token
     * @return League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new GitlabResourceOwner($response);

        return $user->setDomain($this->domain);
    }
}
