<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/parametres')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ParametresController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_parametres')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer les paramètres de l'utilisateur
        $parametres = [
            'theme' => $user->getTheme() ?? 'light',
            'langue' => $user->getLangue() ?? 'fr',
            'notifications_email' => $user->getNotificationsEmail() ?? true,
            'notifications_sms' => $user->getNotificationsSms() ?? false,
            'double_auth' => $user->getGoogleAuthenticatorSecret() !== null,
        ];

        return $this->render('parametres/index.html.twig', [
            'user' => $user,
            'parametres' => $parametres
        ]);
    }

    #[Route('/modifier', name: 'app_parametres_modifier')]
    public function modifier(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            // Mettre à jour les paramètres
            $theme = $request->request->get('theme');
            $langue = $request->request->get('langue');
            $notificationsEmail = $request->request->get('notifications_email') === 'on';
            $notificationsSms = $request->request->get('notifications_sms') === 'on';

            $user->setTheme($theme);
            $user->setLangue($langue);
            $user->setNotificationsEmail($notificationsEmail);
            $user->setNotificationsSms($notificationsSms);

            $this->entityManager->flush();
            $this->addFlash('success', 'Vos paramètres ont été mis à jour avec succès.');

            return $this->redirectToRoute('app_parametres');
        }

        return $this->redirectToRoute('app_parametres');
    }

    #[Route('/securite', name: 'app_parametres_securite')]
    public function securite(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $securiteInfo = [
            'double_auth_active' => $user->getGoogleAuthenticatorSecret() !== null,
            'last_login' => $user->getLastLogin(),
            'password_changed_at' => $user->getPasswordChangedAt(),
            'active_sessions' => $this->getActiveSessions($user),
        ];

        return $this->render('parametres/securite.html.twig', [
            'user' => $user,
            'securiteInfo' => $securiteInfo
        ]);
    }

    #[Route('/notifications', name: 'app_parametres_notifications')]
    public function notifications(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('parametres/notifications.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/confidentialite', name: 'app_parametres_confidentialite')]
    public function confidentialite(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('parametres/confidentialite.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/aide', name: 'app_parametres_aide')]
    public function aide(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('parametres/aide.html.twig', [
            'user' => $user
        ]);
    }

    private function getActiveSessions(User $user): array
    {
        // Pour l'instant, retourner des données simulées
        // Dans une vraie application, vous utiliseriez un système de sessions
        return [
            [
                'id' => 'current',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'created_at' => new \DateTime(),
                'last_activity' => new \DateTime(),
                'is_current' => true
            ]
        ];
    }
}
