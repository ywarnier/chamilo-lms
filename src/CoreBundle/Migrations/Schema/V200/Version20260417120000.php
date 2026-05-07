<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260417120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Create diversity_criteria table (HR extension).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE diversity_criteria (
                id INT AUTO_INCREMENT NOT NULL,
                extra_field_id INT NOT NULL,
                created_by INT NOT NULL,
                updated_by INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                created_on DATETIME NOT NULL,
                updated_on DATETIME DEFAULT NULL,
                INDEX IDX_diversity_criteria_extra_field (extra_field_id),
                INDEX IDX_diversity_criteria_created_by (created_by),
                INDEX IDX_diversity_criteria_updated_by (updated_by),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE diversity_criteria
                ADD CONSTRAINT FK_diversity_criteria_extra_field
                    FOREIGN KEY (extra_field_id) REFERENCES extra_field (id),
                ADD CONSTRAINT FK_diversity_criteria_created_by
                    FOREIGN KEY (created_by) REFERENCES user (id),
                ADD CONSTRAINT FK_diversity_criteria_updated_by
                    FOREIGN KEY (updated_by) REFERENCES user (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_diversity_criteria_extra_field');
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_diversity_criteria_created_by');
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_diversity_criteria_updated_by');
        $this->addSql('DROP TABLE diversity_criteria');
    }
}
