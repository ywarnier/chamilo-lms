<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260519100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add withdrawn_at column to job_offer_application to track candidate withdrawals.';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement(
            'ALTER TABLE job_offer_application
                ADD withdrawn_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\''
        );

        $this->write('Added withdrawn_at to job_offer_application.');
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement(
            'ALTER TABLE job_offer_application DROP COLUMN withdrawn_at'
        );

        $this->write('Dropped withdrawn_at from job_offer_application.');
    }
}
