<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR210;

// Chamilo HR extension

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates social_responsibility_goal and social_responsibility_goal_history tables
 * and seeds all 17 UN Sustainable Development Goals (English).
 */
final class Version20260417120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR: create social_responsibility_goal and social_responsibility_goal_history tables with 17 SDG seed rows';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE social_responsibility_goal (
                id          INT NOT NULL AUTO_INCREMENT,
                updated_by  INT DEFAULT NULL,
                sdg_number  INT NOT NULL,
                language    VARCHAR(10) NOT NULL,
                title       VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                enforcement LONGTEXT DEFAULT NULL,
                is_published TINYINT(1) NOT NULL DEFAULT 0,
                updated_at  DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX sdg_language_unique (sdg_number, language),
                INDEX IDX_SRG_UPDATED_BY (updated_by),
                CONSTRAINT FK_SRG_UPDATED_BY
                    FOREIGN KEY (updated_by)
                    REFERENCES user (id)
                    ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE social_responsibility_goal_history (
                id          INT NOT NULL AUTO_INCREMENT,
                goal_id     INT NOT NULL,
                changed_by  INT DEFAULT NULL,
                description LONGTEXT DEFAULT NULL,
                enforcement LONGTEXT DEFAULT NULL,
                is_published TINYINT(1) NOT NULL DEFAULT 0,
                changed_at  DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX IDX_SRGH_GOAL (goal_id),
                INDEX IDX_SRGH_CHANGED_BY (changed_by),
                CONSTRAINT FK_SRGH_GOAL
                    FOREIGN KEY (goal_id)
                    REFERENCES social_responsibility_goal (id)
                    ON DELETE CASCADE,
                CONSTRAINT FK_SRGH_CHANGED_BY
                    FOREIGN KEY (changed_by)
                    REFERENCES user (id)
                    ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        // Seed the 17 official UN SDG short titles (English)
        $sdgs = [
            1 => 'No Poverty',
            2 => 'Zero Hunger',
            3 => 'Good Health and Well-being',
            4 => 'Quality Education',
            5 => 'Gender Equality',
            6 => 'Clean Water and Sanitation',
            7 => 'Affordable and Clean Energy',
            8 => 'Decent Work and Economic Growth',
            9 => 'Industry, Innovation and Infrastructure',
            10 => 'Reduced Inequalities',
            11 => 'Sustainable Cities and Communities',
            12 => 'Responsible Consumption and Production',
            13 => 'Climate Action',
            14 => 'Life Below Water',
            15 => 'Life on Land',
            16 => 'Peace, Justice and Strong Institutions',
            17 => 'Partnerships for the Goals',
        ];

        foreach ($sdgs as $number => $title) {
            $this->addSql(
                "INSERT INTO social_responsibility_goal (sdg_number, language, title, is_published)
                 VALUES ($number, 'en_US', ".$this->connection->quote($title).', 0)'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE social_responsibility_goal_history');
        $this->addSql('DROP TABLE social_responsibility_goal');
    }
}
