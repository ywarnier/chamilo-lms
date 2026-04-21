<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

// Chamilo HR extension

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Normalises SDG language codes to Chamilo ISO format (en → en_US).
 */
final class Version20260421130000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR: normalise SDG language code from "en" to "en_US" (Chamilo ISO format)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE social_responsibility_goal SET language = 'en_US' WHERE language = 'en'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE social_responsibility_goal SET language = 'en' WHERE language = 'en_US'");
    }
}
