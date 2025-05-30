<?php

/* See license terms in /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Entity\Listener;

use Chamilo\CoreBundle\Entity\Language;
use Doctrine\ORM\Event\PostPersistEventArgs;

class LanguageListener
{
    public function postPersist(Language $language, PostPersistEventArgs $args): void
    {
        if ($language->getParent()) {
            $newIsoCode = $language->generateIsoCodeForChild();

            $language->setIsocode($newIsoCode);

            $args->getObjectManager()->flush();
        }
    }
}
