<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR210;

// Chamilo HR extension

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Renames the `name` column to `title` in all four HR entity tables.
 */
final class Version20260414130000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR: rename name column to title in activity_category, activity, performance_objective_category, performance_objective';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_category RENAME COLUMN name TO title');
        $this->addSql('ALTER TABLE activity RENAME COLUMN name TO title');
        $this->addSql('ALTER TABLE performance_objective_category RENAME COLUMN name TO title');
        $this->addSql('ALTER TABLE performance_objective RENAME COLUMN name TO title');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity_category RENAME COLUMN title TO name');
        $this->addSql('ALTER TABLE activity RENAME COLUMN title TO name');
        $this->addSql('ALTER TABLE performance_objective_category RENAME COLUMN title TO name');
        $this->addSql('ALTER TABLE performance_objective RENAME COLUMN title TO name');
    }
}
