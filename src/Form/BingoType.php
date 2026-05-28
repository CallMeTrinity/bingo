<?php

namespace App\Form;

use App\Entity\Bingo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BingoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => ['placeholder' => 'Ex : Mes résolutions 2028', 'autofocus' => true],
            ])
            ->add('year', IntegerType::class, [
                'label' => 'Année',
                'attr' => ['placeholder' => (string) (int) date('Y')],
            ])
            ->add('size', ChoiceType::class, [
                'label' => 'Taille',
                'choices' => [
                    '3 × 3' => 3,
                    '4 × 4' => 4,
                    '5 × 5' => 5,
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 4,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bingo::class,
        ]);
    }
}
