<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Overrides the session cookie SameSite attribute to "None" when the
 * security.security_session_cookie_samesite_none setting is enabled.
 *
 * Why two listeners instead of ini_set:
 *   Symfony's AbstractSessionListener::onKernelResponse (priority -1000) sets the
 *   session cookie via $response->headers->setCookie(), picking up cookie_samesite
 *   from framework.yaml.  Any ini_set() call is overridden by that.
 *   The response listener here runs at priority -1001 (after -1000) and replaces
 *   the already-queued cookie with a SameSite=None; Secure version.
 *
 * The request listener marks the request (via an attribute) so the response
 * listener can skip the DB lookup on every response.
 */
class SessionCookieSameSiteSubscriber implements EventSubscriberInterface
{
    private const ATTR = '_apply_samesite_none';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $installed = $this->parameterBag->has('installed') && 1 === (int) $this->parameterBag->get('installed');
        if (!$installed) {
            return;
        }

        if (!$event->getRequest()->isSecure()) {
            return;
        }

        $setting = $this->em->getRepository(SettingsCurrent::class)->findOneBy([
            'variable' => 'security_session_cookie_samesite_none',
        ]);

        if (!$setting instanceof SettingsCurrent || 'true' !== $setting->getSelectedValue()) {
            return;
        }

        $event->getRequest()->attributes->set(self::ATTR, true);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$event->getRequest()->attributes->get(self::ATTR)) {
            return;
        }

        $response = $event->getResponse();
        $sessionName = session_name();

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() !== $sessionName) {
                continue;
            }

            $response->headers->removeCookie($sessionName, $cookie->getPath(), $cookie->getDomain());
            $response->headers->setCookie(
                Cookie::create($sessionName)
                    ->withValue($cookie->getValue())
                    ->withExpires($cookie->getExpiresTime())
                    ->withPath($cookie->getPath())
                    ->withDomain($cookie->getDomain())
                    ->withSecure(true)
                    ->withHttpOnly($cookie->isHttpOnly())
                    ->withSameSite(Cookie::SAMESITE_NONE)
            );

            break;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 150]],
            KernelEvents::RESPONSE => [['onKernelResponse', -1001]],
        ];
    }
}
