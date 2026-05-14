<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR210;

// Chamilo HR extension

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates activity_category, activity, performance_objective_category, and
 * performance_objective tables for the Chamilo HR Activities and Objectives feature.
 */
final class Version20260413120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR: create activity and performance objective tables with category support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE activity_category (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE activity (
                id INT NOT NULL AUTO_INCREMENT,
                category_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_AC74095A12469DE2 (category_id),
                CONSTRAINT FK_AC74095A12469DE2
                    FOREIGN KEY (category_id)
                    REFERENCES activity_category (id)
                    ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE performance_objective_category (
                id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE performance_objective (
                id INT NOT NULL AUTO_INCREMENT,
                category_id INT DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_7B5B0B5812469DE2 (category_id),
                CONSTRAINT FK_7B5B0B5812469DE2
                    FOREIGN KEY (category_id)
                    REFERENCES performance_objective_category (id)
                    ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE activity DROP FOREIGN KEY FK_AC74095A12469DE2');
        $this->addSql('ALTER TABLE performance_objective DROP FOREIGN KEY FK_7B5B0B5812469DE2');
        $this->addSql('DROP TABLE activity');
        $this->addSql('DROP TABLE activity_category');
        $this->addSql('DROP TABLE performance_objective');
        $this->addSql('DROP TABLE performance_objective_category');
    }
}
