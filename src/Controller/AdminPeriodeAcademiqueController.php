<?php

namespace App\Controller;

use App\Entity\PeriodeAcademique;
use App\Repository\PeriodeAcademiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/periodes-academiques')]
#[IsGranted('ROLE_ADMIN')]
class AdminPeriodeAcademiqueController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PeriodeAcademiqueRepository $periodeAcademiqueRepository
    ) {}

    #[Route('/', name: 'admin_periodes_academiques_index', methods: ['GET'])]
    public function index(): Response
    {
        $periodes = $this->periodeAcademiqueRepository->findBy([], ['anneeScolaire' => 'DESC', 'dateDebut' => 'ASC']);
        $periodeActive = $this->periodeAcademiqueRepository->findActive();
        $anneesScolaires = $this->periodeAcademiqueRepository->findAnneesScolaires();

        return $this->render('admin/periode_academique/index.html.twig', [
            'periodes' => $periodes,
            'periodeActive' => $periodeActive,
            'anneesScolaires' => $anneesScolaires,
        ]);
    }

    #[Route('/new', name: 'admin_periode_academique_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $periode = new PeriodeAcademique();

        if ($request->isMethod('POST')) {
            $anneeScolaire = $request->request->get('annee_scolaire');
            $periodeNom = $request->request->get('periode');
            $dateDebut = $request->request->get('date_debut');
            $dateFin = $request->request->get('date_fin');
            $isActive = $request->request->get('is_active') === '1';

            if ($anneeScolaire && $periodeNom && $dateDebut && $dateFin) {
                // Désactiver toutes les autres périodes si on active celle-ci
                if ($isActive) {
                    $this->periodeAcademiqueRepository->desactiverToutes();
                }

                $periode->setAnneeScolaire($anneeScolaire);
                $periode->setPeriode($periodeNom);
                $periode->setDateDebut(new \DateTime($dateDebut));
                $periode->setDateFin(new \DateTime($dateFin));
                $periode->setIsActive($isActive);

                $this->entityManager->persist($periode);
                $this->entityManager->flush();

                $this->addFlash('success', 'Période académique créée avec succès !');
                return $this->redirectToRoute('admin_periodes_academiques_index');
            }
        }

        return $this->render('admin/periode_academique/new.html.twig', [
            'periode' => $periode,
        ]);
    }

    #[Route('/{id}/activate', name: 'admin_periode_academique_activate', methods: ['POST'])]
    public function activate(PeriodeAcademique $periode): Response
    {
        // Désactiver toutes les autres périodes
        $this->periodeAcademiqueRepository->desactiverToutes();

        // Activer la période sélectionnée
        $periode->setIsActive(true);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Période "%s" activée avec succès !',
            $periode->getPeriode()
        ));

        return $this->redirectToRoute('admin_periodes_academiques_index');
    }

    #[Route('/{id}/deactivate', name: 'admin_periode_academique_deactivate', methods: ['POST'])]
    public function deactivate(PeriodeAcademique $periode): Response
    {
        $periode->setIsActive(false);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'Période "%s" désactivée avec succès !',
            $periode->getPeriode()
        ));

        return $this->redirectToRoute('admin_periodes_academiques_index');
    }

    #[Route('/{id}/edit', name: 'admin_periode_academique_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PeriodeAcademique $periode): Response
    {
        if ($request->isMethod('POST')) {
            $anneeScolaire = $request->request->get('annee_scolaire');
            $periodeNom = $request->request->get('periode');
            $dateDebut = $request->request->get('date_debut');
            $dateFin = $request->request->get('date_fin');
            $isActive = $request->request->get('is_active') === '1';

            if ($anneeScolaire && $periodeNom && $dateDebut && $dateFin) {
                // Désactiver toutes les autres périodes si on active celle-ci
                if ($isActive && !$periode->isActive()) {
                    $this->periodeAcademiqueRepository->desactiverToutes();
                }

                $periode->setAnneeScolaire($anneeScolaire);
                $periode->setPeriode($periodeNom);
                $periode->setDateDebut(new \DateTime($dateDebut));
                $periode->setDateFin(new \DateTime($dateFin));
                $periode->setIsActive($isActive);
                $periode->setDateModification(new \DateTime());

                $this->entityManager->flush();

                $this->addFlash('success', 'Période académique modifiée avec succès !');
                return $this->redirectToRoute('admin_periodes_academiques_index');
            }
        }

        return $this->render('admin/periode_academique/edit.html.twig', [
            'periode' => $periode,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_periode_academique_delete', methods: ['POST'])]
    public function delete(Request $request, PeriodeAcademique $periode): Response
    {
        if ($this->isCsrfTokenValid('delete-periode', $request->request->get('_token'))) {
            // Vérifier s'il y a des données liées
            if ($periode->getNotes()->count() > 0 || $periode->getCours()->count() > 0 || $periode->getEvaluations()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette période car elle contient des données (notes, cours, évaluations).');
            } else {
                $this->entityManager->remove($periode);
                $this->entityManager->flush();
                $this->addFlash('success', 'Période académique supprimée avec succès !');
            }
        }

        return $this->redirectToRoute('admin_periodes_academiques_index');
    }

    #[Route('/create-from-trimestres', name: 'admin_periodes_academiques_create_from_trimestres', methods: ['POST'])]
    public function createFromTrimestres(Request $request): Response
    {
        $anneeScolaire = $request->request->get('annee_scolaire');
        
        if (!$anneeScolaire) {
            $this->addFlash('error', 'Année scolaire requise.');
            return $this->redirectToRoute('admin_periodes_academiques_index');
        }

        // Désactiver toutes les périodes existantes
        $this->periodeAcademiqueRepository->desactiverToutes();

        // Créer les périodes à partir des trimestres existants
        $trimestres = $this->entityManager->getRepository(\App\Entity\Trimestre::class)->findAll();
        
        $periodesCreees = 0;
        foreach ($trimestres as $trimestre) {
            $periode = new PeriodeAcademique();
            $periode->setAnneeScolaire($anneeScolaire);
            $periode->setPeriode($trimestre->getNom());
            $periode->setDateDebut($trimestre->getDateDebut());
            $periode->setDateFin($trimestre->getDateFin());
            $periode->setIsActive(false); // On n'active que la première par défaut

            $this->entityManager->persist($periode);
            $periodesCreees++;
        }

        // Activer la première période créée
        if ($periodesCreees > 0) {
            $premierePeriode = $this->periodeAcademiqueRepository->findOneBy(
                ['anneeScolaire' => $anneeScolaire],
                ['dateDebut' => 'ASC']
            );
            if ($premierePeriode) {
                $premierePeriode->setIsActive(true);
            }
        }

        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            '%d période(s) académique(s) créée(s) à partir des trimestres pour l\'année %s !',
            $periodesCreees,
            $anneeScolaire
        ));

        return $this->redirectToRoute('admin_periodes_academiques_index');
    }
}
