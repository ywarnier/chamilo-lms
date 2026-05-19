<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\DataFixtures;

// Chamilo HR extension

use Chamilo\CoreBundle\Entity\SocialResponsibilityGoal;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class SocialResponsibilityGoalFixtures extends Fixture
{
    /**
     * Official UN Sustainable Development Goals short titles (translation source keys).
     */
    private const SDG_TITLES = [
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

    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $locale = $this->translator->getLocale();

        foreach (self::SDG_TITLES as $number => $titleKey) {
            $goal = (new SocialResponsibilityGoal())
                ->setSdgNumber($number)
                ->setLanguage($locale)
                ->setTitle($this->translator->trans($titleKey))
                ->setIsPublished(false)
            ;

            $manager->persist($goal);
        }

        $manager->flush();
    }
}
