<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

// Chamilo HR extension

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates geographic_zone, staff_status, contract_type, and business_unit tables,
 * and adds the geographic_zone_id FK to branch_sync.
 */
final class Version20260415120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR: create geographic_zone, staff_status, contract_type, business_unit tables and add geographic_zone_id to branch_sync';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE geographic_zone (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE staff_status (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE contract_type (
                id INT NOT NULL AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'CREATE TABLE business_unit (
                id INT NOT NULL AUTO_INCREMENT,
                parent_id INT DEFAULT NULL,
                branch_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                PRIMARY KEY (id),
                INDEX IDX_BU_PARENT (parent_id),
                INDEX IDX_BU_BRANCH (branch_id),
                CONSTRAINT FK_BU_PARENT
                    FOREIGN KEY (parent_id)
                    REFERENCES business_unit (id)
                    ON DELETE SET NULL,
                CONSTRAINT FK_BU_BRANCH
                    FOREIGN KEY (branch_id)
                    REFERENCES branch_sync (id)
                    ON DELETE SET NULL
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );

        $this->addSql(
            'ALTER TABLE branch_sync
                ADD geographic_zone_id INT DEFAULT NULL,
                ADD CONSTRAINT FK_BS_GEO_ZONE
                    FOREIGN KEY (geographic_zone_id)
                    REFERENCES geographic_zone (id)
                    ON DELETE SET NULL,
                ADD INDEX IDX_BS_GEO_ZONE (geographic_zone_id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE branch_sync DROP FOREIGN KEY FK_BS_GEO_ZONE');
        $this->addSql('ALTER TABLE branch_sync DROP INDEX IDX_BS_GEO_ZONE');
        $this->addSql('ALTER TABLE branch_sync DROP COLUMN geographic_zone_id');
        $this->addSql('ALTER TABLE business_unit DROP FOREIGN KEY FK_BU_PARENT');
        $this->addSql('ALTER TABLE business_unit DROP FOREIGN KEY FK_BU_BRANCH');
        $this->addSql('DROP TABLE business_unit');
        $this->addSql('DROP TABLE contract_type');
        $this->addSql('DROP TABLE staff_status');
        $this->addSql('DROP TABLE geographic_zone');
    }
}
