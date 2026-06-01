<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR210;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260526120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Add hr_category column to c_survey for course-independent HR surveys.';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement(
            "ALTER TABLE c_survey ADD hr_category VARCHAR(32) DEFAULT NULL"
        );

        $this->write('Added hr_category to c_survey.');
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement(
            'ALTER TABLE c_survey DROP COLUMN hr_category'
        );

        $this->write('Dropped hr_category from c_survey.');
    }
}
