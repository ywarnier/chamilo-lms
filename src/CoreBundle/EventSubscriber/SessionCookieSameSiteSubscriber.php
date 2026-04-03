<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use Chamilo\CoreBundle\Entity\SettingsCurrent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Overrides the session cookie SameSite attribute to "None" when the
 * security.security_session_cookie_samesite_none setting is enabled.
 *
 * Runs at priority -1001, after Symfony's AbstractSessionListener (-1000),
 * which writes the session cookie into the response via setCookie().
 * We find that cookie and replace it with an identical one carrying SameSite=None.
 *
 * The session cookie only appears in the response when the session ID changes
 * (new session, login, regeneration), so the DB query is rarely reached.
 */
class SessionCookieSameSiteSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ParameterBagInterface $parameterBag,
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // Find the session cookie in the response — if absent, nothing to do.
        // This is the common case: the cookie is only re-sent when the session ID changes.
        $sessionName = session_name();
        $sessionCookie = null;
        foreach ($event->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $sessionName) {
                $sessionCookie = $cookie;

                break;
            }
        }

        if (null === $sessionCookie) {
            return;
        }

        // A session cookie is being set — check conditions before modifying it.
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

        $response = $event->getResponse();
        $response->headers->removeCookie($sessionName, $sessionCookie->getPath(), $sessionCookie->getDomain());
        $response->headers->setCookie(
            Cookie::create($sessionName)
                ->withValue($sessionCookie->getValue())
                ->withExpires($sessionCookie->getExpiresTime())
                ->withPath($sessionCookie->getPath())
                ->withDomain($sessionCookie->getDomain())
                ->withSecure(true)
                ->withHttpOnly($sessionCookie->isHttpOnly())
                ->withSameSite(Cookie::SAMESITE_NONE)
        );
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [['onKernelResponse', -1001]],
        ];
    }
}
