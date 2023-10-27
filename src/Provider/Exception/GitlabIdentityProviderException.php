<?php

/*
 * Gitlab OAuth2 Provider
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

/**
 * GitlabIdentityProviderException.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
final class GitlabIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     *
     * @param array<string, mixed>|string $data Parsed response data
     */
    public static function clientException(ResponseInterface $response, array|string $data): IdentityProviderException
    {
        return self::fromResponse(
            $response,
            $data['message'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates oauth exception from response.
     *
     * @param ResponseInterface $response Response received from upstream
     * @param array<string, mixed> $data Parsed response data
     */
    public static function oauthException(ResponseInterface $response, array $data): IdentityProviderException
    {
        return self::fromResponse(
            $response,
            $data['error'] ?? $response->getReasonPhrase()
        );
    }

    /**
     * Creates identity exception from response.
     *
     * @param ResponseInterface $response Response received from upstream
     * @param string|null $message        Parsed message
     */
    private static function fromResponse(ResponseInterface $response, string $message = null): IdentityProviderException
    {
        return new self($message ?? self::class, $response->getStatusCode(), $response->getBody()->getContents());
    }
}
