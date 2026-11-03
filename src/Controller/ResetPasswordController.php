<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/reset-password', name: 'app_reset_password')]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if ($user) {
                // Générer un token de réinitialisation
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour'));
                $this->entityManager->flush();
                
                // Pour l'instant, affichons le token (en production, on enverrait un email)
                $this->addFlash('success', 'Un email de réinitialisation a été envoyé à votre adresse email.');
                $this->addFlash('info', 'Token de test : ' . $token);
            } else {
                $this->addFlash('error', 'Aucun compte trouvé avec cette adresse email.');
            }
            
            return $this->redirectToRoute('app_reset_password');
        }

        return $this->render('security/reset_password_request.html.twig');
    }

    #[Route('/reset-password/confirm/{token}', name: 'app_reset_password_confirm')]
    public function confirm(Request $request, string $token): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
        
        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_reset_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');
            
            if ($newPassword && $newPassword === $passwordConfirm) {
                // Hasher le nouveau mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                
                // Nettoyer le token
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            }
        }

        return $this->render('security/reset_password_confirm.html.twig', [
            'token' => $token
        ]);
    }
}
