<?php

declare(strict_types=1);

// Chamilo HR extension

namespace Chamilo\CoreBundle\Settings;

use Chamilo\CoreBundle\Form\Type\YesNoType;
use Sylius\Bundle\SettingsBundle\Schema\AbstractSettingsBuilder;
use Symfony\Component\Form\FormBuilderInterface;

class HrSettingsSchema extends AbstractSettingsSchema
{
    public function buildSettings(AbstractSettingsBuilder $builder): void
    {
        $builder->setDefaults([
            'skills_orga_unit_public' => 'false',
            'skills_orga_people_public' => 'false',
        ]);

        $allowedTypes = [
            'skills_orga_unit_public' => ['string'],
            'skills_orga_people_public' => ['string'],
        ];
        $this->setMultipleAllowedTypes($allowedTypes, $builder);
    }

    public function buildForm(FormBuilderInterface $builder): void
    {
        $builder
            ->add('skills_orga_unit_public', YesNoType::class)
            ->add('skills_orga_people_public', YesNoType::class)
        ;

        $this->updateFormFieldsFromSettingsInfo($builder);
    }
}
