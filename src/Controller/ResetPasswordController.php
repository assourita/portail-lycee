<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    private const DUREE_VALIDITE = '+1 hour';
    private const LONGUEUR_MIN_MOT_DE_PASSE = 8;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $expediteur,
    ) {}

    #[Route('/reset-password', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reset_password', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide, veuillez réessayer.');
                return $this->redirectToRoute('app_reset_password');
            }

            $email = trim((string) $request->request->get('email'));
            $user = $email !== '' ? $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) : null;

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime(self::DUREE_VALIDITE));
                $this->entityManager->flush();

                $resetUrl = $this->generateUrl('app_reset_password_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $this->envoyerEmail($user, $resetUrl);

                if ($this->getParameter('kernel.environment') === 'dev') {
                    $this->addFlash('info', 'Mode développement - lien de réinitialisation : '.$resetUrl);
                }
            }

            // Même message que le compte existe ou non, pour ne pas révéler les adresses inscrites.
            $this->addFlash('success', 'Si un compte est associé à cette adresse, un email de réinitialisation vient d\'être envoyé.');

            return $this->redirectToRoute('app_reset_password');
        }

        return $this->render('security/reset_password_request.html.twig');
    }

    #[Route('/reset-password/confirm/{token}', name: 'app_reset_password_confirm', methods: ['GET', 'POST'])]
    public function confirm(Request $request, string $token): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_reset_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = (string) $request->request->get('password');
            $passwordConfirm = (string) $request->request->get('password_confirm');

            if (!$this->isCsrfTokenValid('reset_password_confirm', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de sécurité invalide, veuillez réessayer.');
            } elseif (mb_strlen($newPassword) < self::LONGUEUR_MIN_MOT_DE_PASSE) {
                $this->addFlash('error', sprintf('Le mot de passe doit contenir au moins %d caractères.', self::LONGUEUR_MIN_MOT_DE_PASSE));
            } elseif ($newPassword !== $passwordConfirm) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } else {
                $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
                $user->setPasswordChangedAt(new \DateTime());
                $user->setResetToken(null);
                $user->setResetTokenExpiresAt(null);
                $this->entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password_confirm.html.twig', [
            'token' => $token,
        ]);
    }

    private function envoyerEmail(User $user, string $resetUrl): void
    {
        $email = (new TemplatedEmail())
            ->from($this->expediteur)
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe - Mon Portail Lycée')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context(['user' => $user, 'resetUrl' => $resetUrl]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Échec de l\'envoi de l\'email de réinitialisation.', ['erreur' => $e->getMessage()]);
        }
    }
}
