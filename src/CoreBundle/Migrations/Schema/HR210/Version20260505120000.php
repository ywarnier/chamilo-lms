<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260505120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR Training ROI: add hr_survey_distribution table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE hr_survey_distribution (
                id INT AUTO_INCREMENT NOT NULL,
                survey_id INT NOT NULL,
                business_unit_id INT NOT NULL,
                category VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX IDX_HSD_SURVEY (survey_id),
                INDEX IDX_HSD_UNIT (business_unit_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            'ALTER TABLE hr_survey_distribution
                ADD CONSTRAINT FK_HSD_SURVEY FOREIGN KEY (survey_id) REFERENCES c_survey (iid) ON DELETE CASCADE,
                ADD CONSTRAINT FK_HSD_UNIT FOREIGN KEY (business_unit_id) REFERENCES business_unit (id) ON DELETE CASCADE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hr_survey_distribution DROP FOREIGN KEY FK_HSD_SURVEY');
        $this->addSql('ALTER TABLE hr_survey_distribution DROP FOREIGN KEY FK_HSD_UNIT');
        $this->addSql('DROP TABLE hr_survey_distribution');
    }
}
