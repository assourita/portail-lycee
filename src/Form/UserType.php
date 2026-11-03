<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre nom d\'utilisateur'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre adresse email'
                ]
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre nouveau mot de passe'
                ],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                ],
            ])
        ;

        // Ajouter d'autres champs uniquement pour la création (pas pour la modification)
        if (isset($options['include_role']) && $options['include_role']) {
            $builder->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Étudiant' => 'ROLE_ETUDIANT',
                    'Enseignant' => 'ROLE_ENSEIGNANT',
                    'Comptable' => 'ROLE_COMPTABLE',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'form-control'
                ]
            ]);
        }

        if (isset($options['include_activation']) && $options['include_activation']) {
            $builder->add('isActivate', CheckboxType::class, [
                'label' => 'Compte activé',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'include_role' => false,
            'include_activation' => false,
        ]);
    }
}
