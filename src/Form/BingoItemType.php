<?php

namespace App\Form;

use App\Entity\BingoItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Vich\UploaderBundle\Form\Type\VichImageType;

class BingoItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'Intitulé',
                'required' => false,
                'attr' => ['placeholder' => 'Ex : Lire un roman', 'autofocus' => true],
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
                'attr' => ['placeholder' => 'Une pensée manuscrite…', 'rows' => 3],
            ])
            ->add('completed', CheckboxType::class, [
                'label' => 'Fait',
                'mapped' => false,
                'required' => false,
                'data' => $options['completed_default'],
            ])
            ->add('emoji', TextType::class, [
                'label' => 'Emoji',
                'required' => false,
                'attr' => ['placeholder' => '😀'],
                'constraints' => [new Length(max: 16)],
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Photo-preuve',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
                'constraints' => [
                    new Image(
                        maxSize: '8M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        mimeTypesMessage: 'Formats acceptés : JPG, PNG, WebP, GIF.',
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BingoItem::class,
            'completed_default' => false,
        ]);
        $resolver->setAllowedTypes('completed_default', 'bool');
    }
}
