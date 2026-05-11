<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HR: Add structured evaluation note fields to job_offer_application - refs HR#147';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer_application
            ADD cv_observation LONGTEXT DEFAULT NULL,
            ADD cv_private_observation LONGTEXT DEFAULT NULL,
            ADD motivation_letter_observation LONGTEXT DEFAULT NULL,
            ADD motivation_letter_private_observation LONGTEXT DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer_application
            DROP COLUMN cv_observation,
            DROP COLUMN cv_private_observation,
            DROP COLUMN motivation_letter_observation,
            DROP COLUMN motivation_letter_private_observation
        ');
    }
}
