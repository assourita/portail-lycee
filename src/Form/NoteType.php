<?php

namespace App\Form;

use App\Entity\Note;
use App\Entity\Etudiant;
use App\Entity\Matiere;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('etudiant', EntityType::class, [
                'class' => Etudiant::class,
                'choices' => $options['etudiants'],
                'choice_label' => function (Etudiant $etudiant) {
                    return $etudiant->getNom() . ' ' . $etudiant->getPrenom();
                },
                'label' => 'Étudiant',
                'attr' => ['class' => 'form-control']
            ])
            ->add('matiere', EntityType::class, [
                'class' => Matiere::class,
                'choices' => $options['matieres'],
                'choice_label' => 'nom',
                'label' => 'Matière',
                'attr' => ['class' => 'form-control']
            ])
            ->add('valeur', NumberType::class, [
                'label' => 'Note',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'max' => 20,
                    'step' => 0.5
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de note',
                'choices' => [
                    'Devoir' => 'devoir',
                    'Examen' => 'examen',
                    'Projet' => 'projet',
                    'Participation' => 'participation'
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Note::class,
            'etudiants' => [],
            'matieres' => [],
        ]);
    }
}
