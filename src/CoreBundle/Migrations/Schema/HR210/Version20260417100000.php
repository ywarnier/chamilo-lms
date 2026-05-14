<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260417100000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR Organisation: add professional_function, function_in_unit, function_in_unit_skill, user_to_function_in_unit tables and settings for public org chart.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE professional_function (
                id INT AUTO_INCREMENT NOT NULL,
                parent_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                start_date DATETIME DEFAULT NULL,
                end_date DATETIME DEFAULT NULL,
                INDEX IDX_PF_PARENT (parent_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            'ALTER TABLE professional_function
                ADD CONSTRAINT FK_PF_PARENT FOREIGN KEY (parent_id) REFERENCES professional_function (id) ON DELETE SET NULL'
        );

        $this->addSql(
            'CREATE TABLE professional_function_activity (
                professional_function_id INT NOT NULL,
                activity_id INT NOT NULL,
                INDEX IDX_PFA_PF (professional_function_id),
                INDEX IDX_PFA_ACT (activity_id),
                PRIMARY KEY(professional_function_id, activity_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            'ALTER TABLE professional_function_activity
                ADD CONSTRAINT FK_PFA_PF FOREIGN KEY (professional_function_id) REFERENCES professional_function (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_PFA_ACT FOREIGN KEY (activity_id) REFERENCES activity (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'CREATE TABLE function_in_unit (
                id INT AUTO_INCREMENT NOT NULL,
                professional_function_id INT NOT NULL,
                business_unit_id INT NOT NULL,
                geographic_zone_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                INDEX IDX_FIU_PF (professional_function_id),
                INDEX IDX_FIU_BU (business_unit_id),
                INDEX IDX_FIU_GZ (geographic_zone_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            'ALTER TABLE function_in_unit
                ADD CONSTRAINT FK_FIU_PF FOREIGN KEY (professional_function_id) REFERENCES professional_function (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_FIU_BU FOREIGN KEY (business_unit_id) REFERENCES business_unit (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_FIU_GZ FOREIGN KEY (geographic_zone_id) REFERENCES geographic_zone (id) ON DELETE SET NULL'
        );

        $this->addSql(
            'CREATE TABLE function_in_unit_activity (
                function_in_unit_id INT NOT NULL,
                activity_id INT NOT NULL,
                INDEX IDX_FIUA_FIU (function_in_unit_id),
                INDEX IDX_FIUA_ACT (activity_id),
                PRIMARY KEY(function_in_unit_id, activity_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            'ALTER TABLE function_in_unit_activity
                ADD CONSTRAINT FK_FIUA_FIU FOREIGN KEY (function_in_unit_id) REFERENCES function_in_unit (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_FIUA_ACT FOREIGN KEY (activity_id) REFERENCES activity (id) ON DELETE CASCADE'
        );

        $this->addSql(
            'CREATE TABLE function_in_unit_skill (
                id INT AUTO_INCREMENT NOT NULL,
                function_in_unit_id INT NOT NULL,
                skill_id INT NOT NULL,
                level_id INT DEFAULT NULL,
                INDEX IDX_FIUS_FIU (function_in_unit_id),
                INDEX IDX_FIUS_SKILL (skill_id),
                INDEX IDX_FIUS_LEVEL (level_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            'ALTER TABLE function_in_unit_skill
                ADD CONSTRAINT FK_FIUS_FIU FOREIGN KEY (function_in_unit_id) REFERENCES function_in_unit (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_FIUS_SKILL FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_FIUS_LEVEL FOREIGN KEY (level_id) REFERENCES skill_level (id) ON DELETE SET NULL'
        );

        $this->addSql(
            'CREATE TABLE user_to_function_in_unit (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                function_in_unit_id INT NOT NULL,
                branch_id INT DEFAULT NULL,
                geographic_zone_id INT DEFAULT NULL,
                start_date DATETIME NOT NULL,
                end_date DATETIME DEFAULT NULL,
                is_boss TINYINT(1) NOT NULL DEFAULT 0,
                contract_etp NUMERIC(3,2) DEFAULT NULL,
                INDEX IDX_UTFIU_USER (user_id),
                INDEX IDX_UTFIU_FIU (function_in_unit_id),
                INDEX IDX_UTFIU_BRANCH (branch_id),
                INDEX IDX_UTFIU_GZ (geographic_zone_id),
                INDEX IDX_UTFIU_END (end_date),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->addSql(
            'ALTER TABLE user_to_function_in_unit
                ADD CONSTRAINT FK_UTFIU_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_UTFIU_FIU FOREIGN KEY (function_in_unit_id) REFERENCES function_in_unit (id) ON DELETE CASCADE,
                ADD CONSTRAINT FK_UTFIU_BRANCH FOREIGN KEY (branch_id) REFERENCES branch_sync (id) ON DELETE SET NULL,
                ADD CONSTRAINT FK_UTFIU_GZ FOREIGN KEY (geographic_zone_id) REFERENCES geographic_zone (id) ON DELETE SET NULL'
        );

        // Settings for public organisational chart (HR category)
        $this->addSql(
            "INSERT INTO settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked)
             VALUES ('skills_orga_unit_public', NULL, 'radio', 'hr', 'false',
                     'Make organisational unit chart public',
                     'When enabled, the organisational unit hierarchy chart tab is accessible without login.',
                     NULL, NULL, 1, 1, 0)
             ON DUPLICATE KEY UPDATE id = id"
        );

        $this->addSql(
            "INSERT INTO settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked)
             VALUES ('skills_orga_people_public', NULL, 'radio', 'hr', 'false',
                     'Make organisational people chart public',
                     'When enabled, the all-staff organisational chart tab is accessible without login.',
                     NULL, NULL, 1, 1, 0)
             ON DUPLICATE KEY UPDATE id = id"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_to_function_in_unit DROP FOREIGN KEY FK_UTFIU_USER');
        $this->addSql('ALTER TABLE user_to_function_in_unit DROP FOREIGN KEY FK_UTFIU_FIU');
        $this->addSql('ALTER TABLE user_to_function_in_unit DROP FOREIGN KEY FK_UTFIU_BRANCH');
        $this->addSql('ALTER TABLE user_to_function_in_unit DROP FOREIGN KEY FK_UTFIU_GZ');
        $this->addSql('DROP TABLE user_to_function_in_unit');

        $this->addSql('ALTER TABLE function_in_unit_skill DROP FOREIGN KEY FK_FIUS_FIU');
        $this->addSql('ALTER TABLE function_in_unit_skill DROP FOREIGN KEY FK_FIUS_SKILL');
        $this->addSql('ALTER TABLE function_in_unit_skill DROP FOREIGN KEY FK_FIUS_LEVEL');
        $this->addSql('DROP TABLE function_in_unit_skill');

        $this->addSql('ALTER TABLE function_in_unit_activity DROP FOREIGN KEY FK_FIUA_FIU');
        $this->addSql('ALTER TABLE function_in_unit_activity DROP FOREIGN KEY FK_FIUA_ACT');
        $this->addSql('DROP TABLE function_in_unit_activity');

        $this->addSql('ALTER TABLE function_in_unit DROP FOREIGN KEY FK_FIU_PF');
        $this->addSql('ALTER TABLE function_in_unit DROP FOREIGN KEY FK_FIU_BU');
        $this->addSql('ALTER TABLE function_in_unit DROP FOREIGN KEY FK_FIU_GZ');
        $this->addSql('DROP TABLE function_in_unit');

        $this->addSql('ALTER TABLE professional_function_activity DROP FOREIGN KEY FK_PFA_PF');
        $this->addSql('ALTER TABLE professional_function_activity DROP FOREIGN KEY FK_PFA_ACT');
        $this->addSql('DROP TABLE professional_function_activity');

        $this->addSql('ALTER TABLE professional_function DROP FOREIGN KEY FK_PF_PARENT');
        $this->addSql('DROP TABLE professional_function');

        $this->addSql("DELETE FROM settings WHERE variable IN ('skills_orga_unit_public', 'skills_orga_people_public')");
    }
}
