<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HR: Add recruitment_stage, recruitment_process and recruitment_process_tracking tables - refs HR#147';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE recruitment_stage (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            display_order INT NOT NULL DEFAULT 0,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE recruitment_process (
            id INT AUTO_INCREMENT NOT NULL,
            job_offer_id INT NOT NULL,
            application_id INT NOT NULL,
            created_by INT NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_RP_job_offer (job_offer_id),
            INDEX IDX_RP_application (application_id),
            INDEX IDX_RP_created_by (created_by),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE recruitment_process
            ADD CONSTRAINT FK_RP_job_offer FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_RP_application FOREIGN KEY (application_id) REFERENCES job_offer_application (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_RP_created_by FOREIGN KEY (created_by) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE recruitment_process_tracking (
            id INT AUTO_INCREMENT NOT NULL,
            process_id INT NOT NULL,
            stage_id INT NOT NULL,
            supervisor_id INT DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_RPT_process (process_id),
            INDEX IDX_RPT_stage (stage_id),
            INDEX IDX_RPT_supervisor (supervisor_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE recruitment_process_tracking
            ADD CONSTRAINT FK_RPT_process FOREIGN KEY (process_id) REFERENCES recruitment_process (id) ON DELETE CASCADE,
            ADD CONSTRAINT FK_RPT_stage FOREIGN KEY (stage_id) REFERENCES recruitment_stage (id) ON DELETE RESTRICT,
            ADD CONSTRAINT FK_RPT_supervisor FOREIGN KEY (supervisor_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recruitment_process_tracking DROP FOREIGN KEY FK_RPT_process');
        $this->addSql('ALTER TABLE recruitment_process_tracking DROP FOREIGN KEY FK_RPT_stage');
        $this->addSql('ALTER TABLE recruitment_process_tracking DROP FOREIGN KEY FK_RPT_supervisor');
        $this->addSql('DROP TABLE recruitment_process_tracking');

        $this->addSql('ALTER TABLE recruitment_process DROP FOREIGN KEY FK_RP_job_offer');
        $this->addSql('ALTER TABLE recruitment_process DROP FOREIGN KEY FK_RP_application');
        $this->addSql('ALTER TABLE recruitment_process DROP FOREIGN KEY FK_RP_created_by');
        $this->addSql('DROP TABLE recruitment_process');

        $this->addSql('DROP TABLE recruitment_stage');
    }
}
