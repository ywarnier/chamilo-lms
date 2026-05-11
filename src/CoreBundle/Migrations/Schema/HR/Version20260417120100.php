<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260417120100 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Rename diversity_criteria indexes to Doctrine-generated names.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_diversity_criteria_updated_by');
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_diversity_criteria_created_by');
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_diversity_criteria_extra_field');
        $this->addSql('DROP INDEX idx_diversity_criteria_extra_field ON diversity_criteria');
        $this->addSql('CREATE INDEX IDX_E7D0560AAEB5FE3 ON diversity_criteria (extra_field_id)');
        $this->addSql('DROP INDEX idx_diversity_criteria_created_by ON diversity_criteria');
        $this->addSql('CREATE INDEX IDX_E7D0560ADE12AB56 ON diversity_criteria (created_by)');
        $this->addSql('DROP INDEX idx_diversity_criteria_updated_by ON diversity_criteria');
        $this->addSql('CREATE INDEX IDX_E7D0560A16FE72E1 ON diversity_criteria (updated_by)');
        $this->addSql(
            'ALTER TABLE diversity_criteria
                ADD CONSTRAINT FK_E7D0560AAEB5FE3 FOREIGN KEY (extra_field_id) REFERENCES extra_field (id),
                ADD CONSTRAINT FK_E7D0560ADE12AB56 FOREIGN KEY (created_by) REFERENCES user (id),
                ADD CONSTRAINT FK_E7D0560A16FE72E1 FOREIGN KEY (updated_by) REFERENCES user (id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_E7D0560AAEB5FE3');
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_E7D0560ADE12AB56');
        $this->addSql('ALTER TABLE diversity_criteria DROP FOREIGN KEY FK_E7D0560A16FE72E1');
        $this->addSql('DROP INDEX IDX_E7D0560AAEB5FE3 ON diversity_criteria');
        $this->addSql('CREATE INDEX idx_diversity_criteria_extra_field ON diversity_criteria (extra_field_id)');
        $this->addSql('DROP INDEX IDX_E7D0560ADE12AB56 ON diversity_criteria');
        $this->addSql('CREATE INDEX idx_diversity_criteria_created_by ON diversity_criteria (created_by)');
        $this->addSql('DROP INDEX IDX_E7D0560A16FE72E1 ON diversity_criteria');
        $this->addSql('CREATE INDEX idx_diversity_criteria_updated_by ON diversity_criteria (updated_by)');
        $this->addSql(
            'ALTER TABLE diversity_criteria
                ADD CONSTRAINT FK_diversity_criteria_extra_field FOREIGN KEY (extra_field_id) REFERENCES extra_field (id),
                ADD CONSTRAINT FK_diversity_criteria_created_by FOREIGN KEY (created_by) REFERENCES user (id),
                ADD CONSTRAINT FK_diversity_criteria_updated_by FOREIGN KEY (updated_by) REFERENCES user (id)'
        );
    }
}
