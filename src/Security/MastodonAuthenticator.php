<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use Lrf141\OAuth2\Client\Provider\MastodonResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class MastodonAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $em,
        private RouterInterface $router,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return 'connect_mastodon_check' === $request->attributes->get('_route');
    }

    private function fetchAccessToken(OAuth2ClientInterface $client, $options = []): AccessToken
    {
        try {
            return $client->getAccessToken($options);
        } catch (\Throwable $e) {
            throw new AuthenticationExpiredException($e->getMessage());
        }
    }

    public function authenticate(Request $request): Passport
    {
        $accessToken = $this->fetchAccessToken($this->getMastodonClient());
        $client = $this->getMastodonClient();

        return new SelfValidatingPassport(
            userBadge: new UserBadge($accessToken->getToken(), function () use ($accessToken): User {
                /** @var MastodonResourceOwner $mastodonUser */
                $mastodonUser = $this->getMastodonClient()->fetchUserFromToken($accessToken);
                $user = $this->em->getRepository(User::class)->findOneByMastodonId($mastodonUser->getId());

                if (null === $user) {
                    $user = new User();
                    $user
                        ->setMastodonId(
                            $mastodonUser->getId() ?? throw new AuthenticationException('Missing mastodon ID')
                        )
                        ->setUsername($mastodonUser->getName() ?? throw new AuthenticationException('Missing mastodon username'))
                        ->setMastodonAccessToken(
                            (new \App\Entity\AccessToken())
                            ->setOwner($user)
                            ->setType(\App\Entity\AccessToken::TYPE_MASTODON)
                            ->setToken($accessToken->getToken())
                            ->setExpires($accessToken->getExpires())
                            ->setRefreshToken($accessToken->getRefreshToken())
                            ->setValues($accessToken->getValues())
                        )
                    ;
                    $this->em->persist($user);
                    $this->em->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if (null !== ($path = $this->getTargetPath($request->getSession(), $firewallName))) {
            return new RedirectResponse($path);
        }

        return new RedirectResponse($this->router->generate('app_contributors'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw $exception;
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return $this->getMastodonClient()
            ->redirect(
                scopes: [
                    'scope' => 'read',
                ]
            );
    }

    private function getMastodonClient(): OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('mastodon_oauth');
    }
}
