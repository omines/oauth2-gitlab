<?php

/*
 * Gitlab OAuth2 Provider
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\OAuth2\Client\Test\Provider;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Mockery as m;
use Omines\OAuth2\Client\Provider\Gitlab;
use Omines\OAuth2\Client\Provider\GitlabResourceOwner;
use PHPUnit\Framework\TestCase;

class GitlabTest extends TestCase
{
    protected Gitlab $provider;

    protected function setUp(): void
    {
        $this->provider = new Gitlab([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testShorthandedSelfhostedConstructor(): void
    {
        $provider = new Gitlab([
            'domain' => 'https://gitlab.example.org',
        ]);
        $this->assertSame('https://gitlab.example.org', $provider->domain);
    }

    public function testAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('approval_prompt', $query);
        $this->assertNotNull($this->provider->getState());
    }

    public function testScopes(): void
    {
        $options = ['scope' => [uniqid(), uniqid()]];
        $url = $this->provider->getAuthorizationUrl($options);
        $this->assertStringContainsString(rawurlencode(implode(Gitlab::SCOPE_SEPARATOR, $options['scope'])), $url);

        // Default scope
        $this->assertStringContainsString('&scope=api&', $this->provider->getAuthorizationUrl());
    }

    public function testGetAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->andReturn(Utils::streamFor('{"access_token":"mock_access_token", "scope":"repo,gist", "token_type":"bearer"}'));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getExpires());
        $this->assertNull($token->getRefreshToken());
        $this->assertNull($token->getResourceOwnerId());
    }

    public function testSelfHostedGitlabDomainUrls()
    {
        $this->provider->domain = 'https://gitlab.company.com';

        $response = m::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getBody')->times(1)->andReturn(Utils::streamFor('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}'));
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $response->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')->times(1)->andReturn($response);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals($this->provider->domain . '/oauth/authorize', $this->provider->getBaseAuthorizationUrl());
        $this->assertEquals($this->provider->domain . '/oauth/token', $this->provider->getBaseAccessTokenUrl([]));
        $this->assertEquals($this->provider->domain . '/api/v4/user', $this->provider->getResourceOwnerDetailsUrl($token));
        // $this->assertEquals($this->provider->domain.'/api/v4/user/emails', $this->provider->urlUserEmails($token));
    }

    public function testUserData()
    {
        $userdata = [
            'id' => rand(1000, 9999),
            'name' => uniqid('name'),
            'username' => uniqid('username'),
            'email' => uniqid('email'),
            'avatar_url' => 'https://example.org/' . uniqid('avatar'),
            'web_url' => 'https://example.org/' . uniqid('web'),
            'state' => 'active',
            'is_admin' => true,
            'external' => true,
        ];

        $postResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $postResponse->shouldReceive('getBody')->andReturn(Utils::streamFor('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}'));
        $postResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->shouldReceive('getStatusCode')->andReturn(200);

        $userResponse = m::mock('Psr\Http\Message\ResponseInterface');
        $userResponse->shouldReceive('getBody')->andReturn(Utils::streamFor(json_encode($userdata)));
        $userResponse->shouldReceive('getHeader')->andReturn(['content-type' => 'json']);
        $userResponse->shouldReceive('getStatusCode')->andReturn(200);

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(2)
            ->andReturn($postResponse, $userResponse);
        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
        $this->assertInstanceOf(AccessToken::class, $token);
        $user = $this->provider->getResourceOwner($token);
        $this->assertInstanceOf(GitlabResourceOwner::class, $user);

        $this->assertSame($userdata, $user->toArray());
        $this->assertEquals($userdata['id'], $user->getId());
        $this->assertEquals($userdata['name'], $user->getName());
        $this->assertEquals($userdata['username'], $user->getUsername());
        $this->assertEquals($userdata['email'], $user->getEmail());
        $this->assertEquals($userdata['avatar_url'], $user->getAvatarUrl());
        $this->assertEquals($userdata['web_url'], $user->getProfileUrl());
        $this->assertEquals('https://gitlab.com', $user->getDomain());
        $this->assertEquals('mock_access_token', $user->getToken()->getToken());
        $this->assertTrue($user->isActive());
        $this->assertTrue($user->isAdmin());
        $this->assertTrue($user->isExternal());

        return $user;
    }

    public function testBuggyResourceOwner(): void
    {
        $owner = new GitlabResourceOwner([
            'id' => 'foo', // Should be an integer
            'is_admin' => 'bar', // Should be a bool
        ], new AccessToken([
            'access_token' => 'foobar',
        ]));

        $this->assertSame(0, $owner->getId());
        $this->assertTrue($owner->isAdmin());
    }

    public function testDefaultValuesForResourceOwner(): void
    {
        $owner = new GitlabResourceOwner([
        ], new AccessToken([
            'access_token' => 'foobar',
        ]));

        $this->assertSame(0, $owner->getId());
        $this->assertFalse($owner->isAdmin());
        $this->assertFalse($owner->isActive());
        $this->assertTrue($owner->isExternal());
    }

    /**
     * @depends testUserData
     */
    public function testApiClient(GitlabResourceOwner $owner): void
    {
        $client = $owner->getApiClient();
        $this->assertInstanceOf(\Gitlab\Client::class, $client);
    }

    public function provideErrorCodes(): array
    {
        return [
            [400],
            [404],
            [500],
            [rand(401, 600)],
        ];
    }

    /**
     * @dataProvider provideErrorCodes
     */
    public function testExceptionThrownWhenErrorObjectReceived(int $status): void
    {
        $response = new Response($status, ['content-type' => 'json'], '{"message": "Validation Failed","errors": [{"resource": "Issue","field": "title","code": "missing_field"}]}');

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($response);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Validation Failed');
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    public function testExceptionThrownWhenOAuthErrorReceived(): void
    {
        $response = new Response(200, ['content-type' => 'json'], '{"error": "bad_verification_code","error_description": "The code passed is incorrect or expired.","error_uri": "https://developer.github.com/v4/oauth/#bad-verification-code"}');

        $client = m::mock('GuzzleHttp\ClientInterface');
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn($response);
        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('bad_verification_code');
        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
