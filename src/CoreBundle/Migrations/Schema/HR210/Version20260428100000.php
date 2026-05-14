<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR210;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * HR: Add Evaluation / GPEC module tables.
 * Includes: periodicity, recruitment_stage, performance_appraisal_template,
 * performance_appraisal_template_item, performance_appraisal,
 * performance_appraisal_item, performance_appraisal_item_comment,
 * performance_appraisal_reminder.
 */
final class Version20260428100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HR: Evaluation/GPEC module — create all evaluation tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE periodicity (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                days INT NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE recruitment_stage (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE performance_appraisal_template (
                id INT AUTO_INCREMENT NOT NULL,
                periodicity_id INT DEFAULT NULL,
                created_by_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT NULL,
                INDEX IDX_DC17336A33E79D0D (periodicity_id),
                INDEX IDX_DC17336AB03A8386 (created_by_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE performance_appraisal_template_item (
                id INT AUTO_INCREMENT NOT NULL,
                template_id INT NOT NULL,
                type VARCHAR(20) NOT NULL,
                ref_id INT NOT NULL,
                percentage INT DEFAULT 0 NOT NULL,
                INDEX IDX_47C652405DA0FB8 (template_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE performance_appraisal (
                id INT AUTO_INCREMENT NOT NULL,
                template_id INT NOT NULL,
                stage_id INT NOT NULL,
                evaluator_user_id INT NOT NULL,
                evaluated_user_id INT NOT NULL,
                action_plan_id INT DEFAULT NULL,
                scheduled_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                status VARCHAR(20) DEFAULT 'scheduled' NOT NULL,
                total_score DOUBLE PRECISION DEFAULT NULL,
                INDEX IDX_C96F57985DA0FB8 (template_id),
                INDEX IDX_C96F57982298D193 (stage_id),
                INDEX IDX_C96F57988DD48763 (evaluator_user_id),
                INDEX IDX_C96F5798452C2C51 (evaluated_user_id),
                UNIQUE INDEX UNIQ_C96F5798323B8A7A (action_plan_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE performance_appraisal_item (
                id INT AUTO_INCREMENT NOT NULL,
                appraisal_id INT NOT NULL,
                type VARCHAR(20) NOT NULL,
                ref_id INT NOT NULL,
                percentage INT DEFAULT 0 NOT NULL,
                score INT DEFAULT NULL,
                responsible_comment LONGTEXT DEFAULT NULL,
                collaborator_comment LONGTEXT DEFAULT NULL,
                INDEX IDX_AC21EED5DD670628 (appraisal_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE performance_appraisal_item_comment (
                id INT AUTO_INCREMENT NOT NULL,
                item_id INT NOT NULL,
                sender_id INT NOT NULL,
                comment LONGTEXT NOT NULL,
                created_on DATETIME NOT NULL,
                INDEX IDX_8479B227126F525E (item_id),
                INDEX IDX_8479B227F624B39D (sender_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE performance_appraisal_reminder (
                id INT AUTO_INCREMENT NOT NULL,
                appraisal_id INT NOT NULL,
                scheduled_at DATETIME NOT NULL,
                offset_days INT NOT NULL,
                is_report TINYINT(1) DEFAULT 0 NOT NULL,
                sent TINYINT(1) DEFAULT 0 NOT NULL,
                INDEX IDX_B4063A9DD670628 (appraisal_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        SQL);

        // Foreign keys
        $this->addSql('ALTER TABLE performance_appraisal_template ADD CONSTRAINT FK_PAT_PERIODICITY FOREIGN KEY (periodicity_id) REFERENCES periodicity (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE performance_appraisal_template ADD CONSTRAINT FK_PAT_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE performance_appraisal_template_item ADD CONSTRAINT FK_PATI_TEMPLATE FOREIGN KEY (template_id) REFERENCES performance_appraisal_template (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE performance_appraisal ADD CONSTRAINT FK_PA_TEMPLATE FOREIGN KEY (template_id) REFERENCES performance_appraisal_template (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE performance_appraisal ADD CONSTRAINT FK_PA_STAGE FOREIGN KEY (stage_id) REFERENCES recruitment_stage (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE performance_appraisal ADD CONSTRAINT FK_PA_EVALUATOR FOREIGN KEY (evaluator_user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE performance_appraisal ADD CONSTRAINT FK_PA_EVALUATED FOREIGN KEY (evaluated_user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE performance_appraisal ADD CONSTRAINT FK_PA_ACTION_PLAN FOREIGN KEY (action_plan_id) REFERENCES performance_appraisal (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE performance_appraisal_item ADD CONSTRAINT FK_PAI_APPRAISAL FOREIGN KEY (appraisal_id) REFERENCES performance_appraisal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE performance_appraisal_item_comment ADD CONSTRAINT FK_PAIC_ITEM FOREIGN KEY (item_id) REFERENCES performance_appraisal_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE performance_appraisal_item_comment ADD CONSTRAINT FK_PAIC_SENDER FOREIGN KEY (sender_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE performance_appraisal_reminder ADD CONSTRAINT FK_PAR_APPRAISAL FOREIGN KEY (appraisal_id) REFERENCES performance_appraisal (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE performance_appraisal_reminder DROP FOREIGN KEY FK_PAR_APPRAISAL');
        $this->addSql('ALTER TABLE performance_appraisal_item_comment DROP FOREIGN KEY FK_PAIC_ITEM');
        $this->addSql('ALTER TABLE performance_appraisal_item_comment DROP FOREIGN KEY FK_PAIC_SENDER');
        $this->addSql('ALTER TABLE performance_appraisal_item DROP FOREIGN KEY FK_PAI_APPRAISAL');
        $this->addSql('ALTER TABLE performance_appraisal DROP FOREIGN KEY FK_PA_TEMPLATE');
        $this->addSql('ALTER TABLE performance_appraisal DROP FOREIGN KEY FK_PA_STAGE');
        $this->addSql('ALTER TABLE performance_appraisal DROP FOREIGN KEY FK_PA_EVALUATOR');
        $this->addSql('ALTER TABLE performance_appraisal DROP FOREIGN KEY FK_PA_EVALUATED');
        $this->addSql('ALTER TABLE performance_appraisal DROP FOREIGN KEY FK_PA_ACTION_PLAN');
        $this->addSql('ALTER TABLE performance_appraisal_template_item DROP FOREIGN KEY FK_PATI_TEMPLATE');
        $this->addSql('ALTER TABLE performance_appraisal_template DROP FOREIGN KEY FK_PAT_PERIODICITY');
        $this->addSql('ALTER TABLE performance_appraisal_template DROP FOREIGN KEY FK_PAT_CREATED_BY');
        $this->addSql('DROP TABLE IF EXISTS performance_appraisal_reminder');
        $this->addSql('DROP TABLE IF EXISTS performance_appraisal_item_comment');
        $this->addSql('DROP TABLE IF EXISTS performance_appraisal_item');
        $this->addSql('DROP TABLE IF EXISTS performance_appraisal');
        $this->addSql('DROP TABLE IF EXISTS performance_appraisal_template_item');
        $this->addSql('DROP TABLE IF EXISTS performance_appraisal_template');
        $this->addSql('DROP TABLE IF EXISTS recruitment_stage');
        $this->addSql('DROP TABLE IF EXISTS periodicity');
    }
}
