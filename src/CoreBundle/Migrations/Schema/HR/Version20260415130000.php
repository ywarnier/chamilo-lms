<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20260415130000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR Benefits: add compensation_tag, compensation, compensation_rel_tag, benefit_assignment and benefit_notification tables.';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement(
            'CREATE TABLE compensation_tag (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                color VARCHAR(10) NOT NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->connection->executeStatement(
            "CREATE TABLE compensation (
                id INT AUTO_INCREMENT NOT NULL,
                author_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT DEFAULT NULL,
                duration VARCHAR(255) DEFAULT NULL,
                score DOUBLE PRECISION NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                INDEX IDX_B2DD12DAF675F31B (author_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC"
        );

        $this->connection->executeStatement(
            'ALTER TABLE compensation
                ADD CONSTRAINT FK_B2DD12DAF675F31B FOREIGN KEY (author_id) REFERENCES user (id)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE compensation_rel_tag (
                compensation_id INT NOT NULL,
                tag_id INT NOT NULL,
                INDEX IDX_5AB5397766C9ABDE (compensation_id),
                INDEX IDX_5AB53977BAD26311 (tag_id),
                PRIMARY KEY(compensation_id, tag_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC'
        );

        $this->connection->executeStatement(
            'ALTER TABLE compensation_rel_tag
                ADD CONSTRAINT FK_5AB5397766C9ABDE FOREIGN KEY (compensation_id) REFERENCES compensation (id),
                ADD CONSTRAINT FK_5AB53977BAD26311 FOREIGN KEY (tag_id) REFERENCES compensation_tag (id)'
        );

        $this->connection->executeStatement(
            "CREATE TABLE benefit_assignment (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                compensation_id INT NOT NULL,
                assignment_author_id INT NOT NULL,
                economical_equivalent DOUBLE PRECISION DEFAULT NULL,
                assignment_datetime DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                assignment_end_datetime DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                comment LONGTEXT DEFAULT NULL,
                INDEX IDX_8809FCD4A76ED395 (user_id),
                INDEX IDX_8809FCD466C9ABDE (compensation_id),
                INDEX IDX_8809FCD43B66F7FC (assignment_author_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC"
        );

        $this->connection->executeStatement(
            'ALTER TABLE benefit_assignment
                ADD CONSTRAINT FK_8809FCD4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id),
                ADD CONSTRAINT FK_8809FCD466C9ABDE FOREIGN KEY (compensation_id) REFERENCES compensation (id),
                ADD CONSTRAINT FK_8809FCD43B66F7FC FOREIGN KEY (assignment_author_id) REFERENCES user (id)'
        );

        $this->connection->executeStatement(
            "CREATE TABLE benefit_notification (
                id INT AUTO_INCREMENT NOT NULL,
                benefit_assignment_id INT NOT NULL,
                sender_id INT NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message LONGTEXT NOT NULL,
                sent_on DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                INDEX IDX_8190B3912D144E41 (benefit_assignment_id),
                INDEX IDX_8190B391F624B39D (sender_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC"
        );

        $this->connection->executeStatement(
            'ALTER TABLE benefit_notification
                ADD CONSTRAINT FK_8190B3912D144E41 FOREIGN KEY (benefit_assignment_id) REFERENCES benefit_assignment (id),
                ADD CONSTRAINT FK_8190B391F624B39D FOREIGN KEY (sender_id) REFERENCES user (id)'
        );

        $this->write('Created HR Benefits tables: compensation_tag, compensation, compensation_rel_tag, benefit_assignment, benefit_notification.');
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE benefit_notification DROP FOREIGN KEY FK_8190B3912D144E41, DROP FOREIGN KEY FK_8190B391F624B39D');
        $this->connection->executeStatement('ALTER TABLE benefit_assignment DROP FOREIGN KEY FK_8809FCD4A76ED395, DROP FOREIGN KEY FK_8809FCD466C9ABDE, DROP FOREIGN KEY FK_8809FCD43B66F7FC');
        $this->connection->executeStatement('ALTER TABLE compensation_rel_tag DROP FOREIGN KEY FK_5AB5397766C9ABDE, DROP FOREIGN KEY FK_5AB53977BAD26311');
        $this->connection->executeStatement('ALTER TABLE compensation DROP FOREIGN KEY FK_B2DD12DAF675F31B');

        $this->connection->executeStatement('DROP TABLE benefit_notification');
        $this->connection->executeStatement('DROP TABLE benefit_assignment');
        $this->connection->executeStatement('DROP TABLE compensation_rel_tag');
        $this->connection->executeStatement('DROP TABLE compensation');
        $this->connection->executeStatement('DROP TABLE compensation_tag');

        $this->write('Dropped HR Benefits tables.');
    }
}
