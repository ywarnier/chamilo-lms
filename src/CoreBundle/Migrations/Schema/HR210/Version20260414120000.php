<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR210;

// Chamilo HR extension

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds description column to activity_category and performance_objective_category tables.
 */
final class Version20260414120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR: add description to activity_category and performance_objective_category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_category ADD description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE performance_objective_category ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_category DROP COLUMN description');
        $this->addSql('ALTER TABLE performance_objective_category DROP COLUMN description');
    }
}
