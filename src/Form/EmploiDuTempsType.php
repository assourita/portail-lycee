<?php

namespace App\Form;

use App\Entity\EmploiDuTemps;
use App\Entity\Jour;
use App\Entity\Matiere;
use App\Entity\Cours;
use App\Entity\Filiere;
use App\Entity\Enseignant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmploiDuTempsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jour', EntityType::class, [
                'class' => Jour::class,
                'choice_label' => 'nom',
                'label' => 'Jour de la semaine',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un jour'
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'attr' => ['class' => 'form-control'],
                'widget' => 'single_text'
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'attr' => ['class' => 'form-control'],
                'widget' => 'single_text'
            ])
            ->add('date', DateType::class, [
                'label' => 'Date (optionnel - pour les cours ponctuels)',
                'attr' => ['class' => 'form-control'],
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('filiere', EntityType::class, [
                'class' => Filiere::class,
                'choice_label' => 'nom',
                'label' => 'Filière',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez une filière'
            ])
            ->add('enseignant', EntityType::class, [
                'class' => Enseignant::class,
                'choice_label' => function(Enseignant $enseignant) {
                    return $enseignant->getNom() . ' ' . $enseignant->getPrenom();
                },
                'label' => 'Enseignant',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un enseignant'
            ])
            ->add('matiere', EntityType::class, [
                'class' => Matiere::class,
                'choice_label' => function(Matiere $matiere) {
                    return $matiere->getNom() . ' (' . ($matiere->getFiliere() ? $matiere->getFiliere()->getNom() : 'Filière non assignée') . ')';
                },
                'label' => 'Matière',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez une matière'
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'titre',
                'label' => 'Cours (optionnel)',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionnez un cours (optionnel)',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmploiDuTemps::class,
        ]);
    }
}