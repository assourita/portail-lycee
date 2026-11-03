<?php

namespace App\Form;

use App\Entity\Enseignant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\Matiere;
use Doctrine\ORM\EntityManagerInterface;

class EnseignantType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupérer toutes les matières
        $matieres = $this->entityManager->getRepository(Matiere::class)->findAll();
        $choices = [];
        foreach ($matieres as $matiere) {
            $choices[$matiere->getNom()] = $matiere->getNom();
        }

        $builder
            ->add('nom')
            ->add('prenom')
            ->add('email')
            ->add('specialite', ChoiceType::class, [
                'choices' => $choices,
                'placeholder' => 'Sélectionnez une matière',
                'required' => true,
            ])
            ->add('dateEmbauche', null, [
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Enseignant::class,
        ]);
    }
}
