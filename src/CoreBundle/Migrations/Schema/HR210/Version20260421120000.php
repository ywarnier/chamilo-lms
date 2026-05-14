<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\HR;

// Chamilo HR extension

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Pre-populates the description field for all 17 SDG rows (English)
 * with the official UN goal texts from https://sdgs.un.org/goals.
 * Rows that already have a description are left unchanged.
 */
final class Version20260421120000 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'HR: pre-populate SDG descriptions with official UN goal texts (English)';
    }

    public function up(Schema $schema): void
    {
        $descriptions = [
            1 => 'End poverty in all its forms everywhere.',
            2 => 'End hunger, achieve food security and improved nutrition and promote sustainable agriculture.',
            3 => 'Ensure healthy lives and promote well-being for all at all ages.',
            4 => 'Ensure inclusive and equitable quality education and promote lifelong learning opportunities for all.',
            5 => 'Achieve gender equality and empower all women and girls.',
            6 => 'Ensure availability and sustainable management of water and sanitation for all.',
            7 => 'Ensure access to affordable, reliable, sustainable and modern energy for all.',
            8 => 'Promote sustained, inclusive and sustainable economic growth, full and productive employment and decent work for all.',
            9 => 'Build resilient infrastructure, promote inclusive and sustainable industrialization and foster innovation.',
            10 => 'Reduce inequality within and among countries.',
            11 => 'Make cities and human settlements inclusive, safe, resilient and sustainable.',
            12 => 'Ensure sustainable consumption and production patterns.',
            13 => 'Take urgent action to combat climate change and its impacts.',
            14 => 'Conserve and sustainably use the oceans, seas and marine resources for sustainable development.',
            15 => 'Protect, restore and promote sustainable use of terrestrial ecosystems, sustainably manage forests, combat desertification, and halt and reverse land degradation and halt biodiversity loss.',
            16 => 'Promote peaceful and inclusive societies for sustainable development, provide access to justice for all and build effective, accountable and inclusive institutions at all levels.',
            17 => 'Strengthen the means of implementation and revitalize the Global Partnership for Sustainable Development.',
        ];

        foreach ($descriptions as $number => $description) {
            $this->addSql(
                'UPDATE social_responsibility_goal SET description = '
                .$this->connection->quote($description)
                .' WHERE sdg_number = '.$number." AND language = 'en' AND description IS NULL"
            );
        }
    }

    public function down(Schema $schema): void
    {
        // Clear descriptions that match the seeded values exactly
        $descriptions = [
            1 => 'End poverty in all its forms everywhere.',
            2 => 'End hunger, achieve food security and improved nutrition and promote sustainable agriculture.',
            3 => 'Ensure healthy lives and promote well-being for all at all ages.',
            4 => 'Ensure inclusive and equitable quality education and promote lifelong learning opportunities for all.',
            5 => 'Achieve gender equality and empower all women and girls.',
            6 => 'Ensure availability and sustainable management of water and sanitation for all.',
            7 => 'Ensure access to affordable, reliable, sustainable and modern energy for all.',
            8 => 'Promote sustained, inclusive and sustainable economic growth, full and productive employment and decent work for all.',
            9 => 'Build resilient infrastructure, promote inclusive and sustainable industrialization and foster innovation.',
            10 => 'Reduce inequality within and among countries.',
            11 => 'Make cities and human settlements inclusive, safe, resilient and sustainable.',
            12 => 'Ensure sustainable consumption and production patterns.',
            13 => 'Take urgent action to combat climate change and its impacts.',
            14 => 'Conserve and sustainably use the oceans, seas and marine resources for sustainable development.',
            15 => 'Protect, restore and promote sustainable use of terrestrial ecosystems, sustainably manage forests, combat desertification, and halt and reverse land degradation and halt biodiversity loss.',
            16 => 'Promote peaceful and inclusive societies for sustainable development, provide access to justice for all and build effective, accountable and inclusive institutions at all levels.',
            17 => 'Strengthen the means of implementation and revitalize the Global Partnership for Sustainable Development.',
        ];

        foreach ($descriptions as $number => $description) {
            $this->addSql(
                'UPDATE social_responsibility_goal SET description = NULL'
                .' WHERE sdg_number = '.$number." AND language = 'en' AND description = "
                .$this->connection->quote($description)
            );
        }
    }
}
