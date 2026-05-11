<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'HR: Add job_offer, job_offer_application, job_offer_quiz tables - refs HR#147';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE job_offer (
                id INT AUTO_INCREMENT NOT NULL,
                function_in_unit_id INT NOT NULL,
                created_by INT NOT NULL,
                contract_type_id INT DEFAULT NULL,
                title VARCHAR(255) NOT NULL,
                description LONGTEXT NOT NULL,
                vacancy INT NOT NULL,
                salary VARCHAR(255) DEFAULT NULL,
                expected_start_date DATE DEFAULT NULL,
                contract_duration INT DEFAULT NULL,
                is_public TINYINT(1) DEFAULT 0 NOT NULL,
                publication_start_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                publication_end_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                INDEX IDX_288A3A4EDE0B56AE (function_in_unit_id),
                INDEX IDX_288A3A4EDE12AB56 (created_by),
                INDEX IDX_288A3A4ECD1DF15B (contract_type_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        ");

        $this->addSql("
            CREATE TABLE job_offer_application (
                id INT AUTO_INCREMENT NOT NULL,
                job_offer_id INT NOT NULL,
                created_by INT NOT NULL,
                cv_personal_file_id INT DEFAULT NULL,
                contract_personal_file_id INT DEFAULT NULL,
                evaluated_by INT DEFAULT NULL,
                introduction VARCHAR(255) DEFAULT NULL,
                salary_expectations VARCHAR(255) DEFAULT NULL,
                availability_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                hired INT DEFAULT 0 NOT NULL,
                observation LONGTEXT DEFAULT NULL,
                private_observation LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime)',
                total_score DOUBLE PRECISION DEFAULT NULL,
                first_evaluated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime)',
                INDEX IDX_217FA13A3481D195 (job_offer_id),
                INDEX IDX_217FA13ADE12AB56 (created_by),
                INDEX IDX_217FA13AA5040979 (cv_personal_file_id),
                INDEX IDX_217FA13A4D573538 (contract_personal_file_id),
                INDEX IDX_217FA13AFC67FE12 (evaluated_by),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        ");

        $this->addSql('
            CREATE TABLE job_offer_quiz (
                id INT AUTO_INCREMENT NOT NULL,
                job_offer_id INT NOT NULL,
                c_id INT NOT NULL,
                c_quiz_id INT NOT NULL,
                INDEX IDX_309D44983481D195 (job_offer_id),
                INDEX IDX_309D449891D79BD3 (c_id),
                INDEX IDX_309D449817B146AE (c_quiz_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB ROW_FORMAT = DYNAMIC
        ');

        $this->addSql('ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4EDE0B56AE FOREIGN KEY (function_in_unit_id) REFERENCES function_in_unit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4EDE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_offer ADD CONSTRAINT FK_288A3A4ECD1DF15B FOREIGN KEY (contract_type_id) REFERENCES contract_type (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE job_offer_application ADD CONSTRAINT FK_217FA13A3481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_application ADD CONSTRAINT FK_217FA13ADE12AB56 FOREIGN KEY (created_by) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_offer_application ADD CONSTRAINT FK_217FA13AA5040979 FOREIGN KEY (cv_personal_file_id) REFERENCES personal_file (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE job_offer_application ADD CONSTRAINT FK_217FA13A4D573538 FOREIGN KEY (contract_personal_file_id) REFERENCES personal_file (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE job_offer_application ADD CONSTRAINT FK_217FA13AFC67FE12 FOREIGN KEY (evaluated_by) REFERENCES user (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE job_offer_quiz ADD CONSTRAINT FK_309D44983481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_quiz ADD CONSTRAINT FK_309D449891D79BD3 FOREIGN KEY (c_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_quiz ADD CONSTRAINT FK_309D449817B146AE FOREIGN KEY (c_quiz_id) REFERENCES c_quiz (iid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_offer_application DROP FOREIGN KEY FK_217FA13A3481D195');
        $this->addSql('ALTER TABLE job_offer_quiz DROP FOREIGN KEY FK_309D44983481D195');
        $this->addSql('DROP TABLE job_offer_application');
        $this->addSql('DROP TABLE job_offer_quiz');
        $this->addSql('DROP TABLE job_offer');
    }
}
