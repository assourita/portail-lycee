<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Matiere;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du cours',
                'attr' => ['class' => 'form-control']
            ])
            ->add('contenu', TextareaType::class, [
                'label' => 'Contenu du cours',
                'attr' => ['class' => 'form-control', 'rows' => 10]
            ])
            ->add('matiere', EntityType::class, [
                'class' => Matiere::class,
                'choices' => $options['matieres'] ?? [],
                'choice_label' => function(Matiere $matiere) {
                    return $matiere->getNom() . ' (' . ($matiere->getFiliere() ? $matiere->getFiliere()->getNom() : 'Filière non assignée') . ')';
                },
                'label' => 'Matière',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez une matière'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
            'matieres' => [],
        ]);
    }
}
