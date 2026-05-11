<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260430100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HR: Add motivation_letter_personal_file_id to job_offer_application - refs HR#147';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer_application ADD motivation_letter_personal_file_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offer_application ADD CONSTRAINT FK_217FA13A89FCFF90 FOREIGN KEY (motivation_letter_personal_file_id) REFERENCES personal_file (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_217FA13A89FCFF90 ON job_offer_application (motivation_letter_personal_file_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer_application DROP FOREIGN KEY FK_217FA13A89FCFF90');
        $this->addSql('DROP INDEX IDX_217FA13A89FCFF90 ON job_offer_application');
        $this->addSql('ALTER TABLE job_offer_application DROP COLUMN motivation_letter_personal_file_id');
    }
}
