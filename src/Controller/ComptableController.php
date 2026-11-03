<?php

namespace App\Controller;

use App\Entity\Etudiant;
use App\Entity\Facture;
use App\Entity\Filiere;
use App\Entity\Paiement;
use App\Repository\EtudiantRepository;
use App\Repository\FactureRepository;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/comptable')]
#[IsGranted('ROLE_COMPTABLE')]
class ComptableController extends AbstractController
{
    public const TYPES_PAIEMENT = [
        'Frais d\'inscription',
        'Frais de scolarité',
        'Frais de documentation',
        'Frais d\'examen',
        'Cantine',
        'Transport',
        'Autre',
    ];

    public const MODES_PAIEMENT = [
        'especes' => 'Espèces',
        'cheque' => 'Chèque',
        'virement' => 'Virement',
        'carte' => 'Carte bancaire',
        'mobile' => 'Paiement mobile',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaiementRepository $paiementRepository,
        private FactureRepository $factureRepository,
        private EtudiantRepository $etudiantRepository,
    ) {}

    #[Route('/dashboard', name: 'comptable_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $payees = $this->factureRepository->countByStatut(Facture::STATUT_PAYE);
        $ouvertes = $this->factureRepository->findByStatut(Facture::STATUT_EN_ATTENTE);
        $enRetard = $this->factureRepository->findFacturesEnRetard();
        $totalFactures = $payees + count($ouvertes);

        $debut = new \DateTime('first day of -5 months midnight');
        $parMois = $this->paiementRepository->getTotauxParMois($debut, new \DateTime('today 23:59:59'));

        return $this->render('comptable/dashboard.html.twig', [
            'montantTotalMois' => $this->paiementRepository->getMontantTotalDuMois(),
            'montantMoisPrecedent' => $this->paiementRepository->getTotalEncaisse(new \DateTime('first day of last month midnight'), new \DateTime('last day of last month 23:59:59')),
            'resteARecouvrer' => array_sum(array_map(fn (Facture $f) => $f->getResteAPayer(), $ouvertes)),
            'facturesEnAttente' => count($ouvertes),
            'montantEnRetard' => array_sum(array_map(fn (Facture $f) => $f->getResteAPayer(), $enRetard)),
            'retardsPaiement' => count($enRetard),
            'tauxPaiement' => $totalFactures > 0 ? round($payees / $totalFactures * 100, 1) : 0,
            'derniersPaiements' => $this->paiementRepository->findDerniersPaiements(6),
            'facturesEnRetard' => array_slice($enRetard, 0, 5),
            'mois' => array_keys($parMois),
            'montantsParMois' => array_values($parMois),
            'modes' => self::MODES_PAIEMENT,
        ]);
    }

    #[Route('/paiements', name: 'comptable_paiements', methods: ['GET'])]
    public function paiements(Request $request): Response
    {
        $filtres = $this->lireFiltres($request, ['statut', 'type', 'recherche']);
        $paiements = $this->paiementRepository->findFiltres($filtres);

        return $this->render('comptable/paiements.html.twig', [
            'paiements' => $paiements,
            'filtres' => $filtres,
            'types' => array_unique(array_merge(self::TYPES_PAIEMENT, $this->paiementRepository->findTypesUtilises())),
            'modes' => self::MODES_PAIEMENT,
            'total' => array_sum(array_map(fn (Paiement $p) => $p->getStatut() === 'paye' ? $p->getMontant() : 0, $paiements)),
        ]);
    }

    #[Route('/paiements/new', name: 'comptable_paiements_new', methods: ['GET', 'POST'])]
    public function nouveauPaiement(Request $request): Response
    {
        $etudiant = $request->query->getInt('etudiant')
            ? $this->etudiantRepository->find($request->query->getInt('etudiant'))
            : null;
        $facture = $request->query->getInt('facture')
            ? $this->factureRepository->find($request->query->getInt('facture'))
            : null;
        $etudiant ??= $facture?->getEtudiant();

        if ($request->isMethod('POST')) {
            $this->verifierCsrf($request, 'paiement');

            $etudiant = $this->etudiantRepository->find($request->request->getInt('etudiant'));
            $facture = $request->request->getInt('facture') ? $this->factureRepository->find($request->request->getInt('facture')) : null;
            $montant = (float) str_replace(',', '.', (string) $request->request->get('montant'));
            $type = trim((string) $request->request->get('type'));
            $mode = $request->request->get('mode_paiement');

            $erreurs = [];
            if (!$etudiant) {
                $erreurs[] = 'Veuillez choisir un étudiant.';
            }
            if ($montant <= 0) {
                $erreurs[] = 'Le montant doit être supérieur à zéro.';
            }
            if ($type === '') {
                $erreurs[] = 'Veuillez préciser le type de paiement.';
            }
            if ($facture && $facture->getEtudiant() !== $etudiant) {
                $erreurs[] = 'La facture sélectionnée ne concerne pas cet étudiant.';
            }
            if ($facture && $montant > $facture->getResteAPayer() + 0.001) {
                $erreurs[] = sprintf('Le montant dépasse le reste à payer de la facture (%s €).', number_format($facture->getResteAPayer(), 2, ',', ' '));
            }

            if (!$erreurs) {
                $paiement = (new Paiement())
                    ->setEtudiant($etudiant)
                    ->setFacture($facture)
                    ->setMontant($montant)
                    ->setType($type)
                    ->setStatut('paye')
                    ->setModePaiement(isset(self::MODES_PAIEMENT[$mode]) ? $mode : null)
                    ->setReference($request->request->get('reference') ?: null)
                    ->setDatePaiement(new \DateTime($request->request->get('date_paiement') ?: 'now'));

                $this->entityManager->persist($paiement);
                if ($facture) {
                    $facture->getPaiements()->add($paiement);
                    $facture->actualiserStatut();
                }
                $this->entityManager->flush();

                $this->addFlash('success', 'Paiement enregistré.');

                return $this->redirectToRoute('comptable_etudiants_show', ['id' => $etudiant->getId()]);
            }

            foreach ($erreurs as $erreur) {
                $this->addFlash('error', $erreur);
            }
        }

        return $this->render('comptable/paiement_new.html.twig', [
            'etudiants' => $this->etudiantRepository->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']),
            'etudiantSelectionne' => $etudiant,
            'factureSelectionnee' => $facture,
            'facturesOuvertes' => $etudiant ? $this->factureRepository->findOuvertesPourEtudiant($etudiant) : [],
            'types' => self::TYPES_PAIEMENT,
            'modes' => self::MODES_PAIEMENT,
        ]);
    }

    #[Route('/paiements/{id}/annuler', name: 'comptable_paiements_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function annulerPaiement(Request $request, Paiement $paiement): Response
    {
        $this->verifierCsrf($request, 'annuler-paiement'.$paiement->getId());

        $paiement->setStatut('annule');
        $paiement->getFacture()?->actualiserStatut();
        $this->entityManager->flush();

        $this->addFlash('success', 'Le paiement a été annulé.');

        return $this->redirectToRoute('comptable_etudiants_show', ['id' => $paiement->getEtudiant()->getId()]);
    }

    #[Route('/factures', name: 'comptable_factures', methods: ['GET'])]
    public function factures(Request $request): Response
    {
        $filtres = $this->lireFiltres($request, ['statut', 'recherche']);
        $toutes = $this->factureRepository->findAll();

        return $this->render('comptable/factures.html.twig', [
            'factures' => $this->factureRepository->findFiltres($filtres),
            'filtres' => $filtres,
            'stats' => [
                'total' => count($toutes),
                'en_attente' => count(array_filter($toutes, fn (Facture $f) => $f->getStatut() === Facture::STATUT_EN_ATTENTE)),
                'payees' => count(array_filter($toutes, fn (Facture $f) => $f->getStatut() === Facture::STATUT_PAYE)),
                'en_retard' => count(array_filter($toutes, fn (Facture $f) => $f->isEnRetard())),
            ],
        ]);
    }

    #[Route('/factures/new', name: 'comptable_factures_new', methods: ['GET', 'POST'])]
    public function nouvelleFacture(Request $request): Response
    {
        $filieres = $this->entityManager->getRepository(Filiere::class)->findBy([], ['nom' => 'ASC']);

        if ($request->isMethod('POST')) {
            $this->verifierCsrf($request, 'facture');

            $libelle = trim((string) $request->request->get('libelle'));
            $montant = (float) str_replace(',', '.', (string) $request->request->get('montant'));
            $echeance = $request->request->get('date_echeance');

            $destinataires = [];
            if ($request->request->get('cible') === 'filiere') {
                $filiere = $this->entityManager->getRepository(Filiere::class)->find($request->request->getInt('filiere'));
                $destinataires = $filiere ? $filiere->getEtudiants()->toArray() : [];
            } elseif ($etudiant = $this->etudiantRepository->find($request->request->getInt('etudiant'))) {
                $destinataires = [$etudiant];
            }

            if ($libelle === '' || $montant <= 0 || !$echeance || !$destinataires) {
                $this->addFlash('error', 'Veuillez renseigner le libellé, un montant positif, l\'échéance et au moins un étudiant.');
            } else {
                foreach ($destinataires as $etudiant) {
                    $facture = (new Facture())
                        ->setEtudiant($etudiant)
                        ->setLibelle($libelle)
                        ->setMontant($montant)
                        ->setDateEcheance(new \DateTime($echeance));
                    $this->entityManager->persist($facture);
                }
                $this->entityManager->flush();

                $this->addFlash('success', sprintf('%d facture(s) émise(s).', count($destinataires)));

                return count($destinataires) === 1
                    ? $this->redirectToRoute('comptable_etudiants_show', ['id' => $destinataires[0]->getId()])
                    : $this->redirectToRoute('comptable_factures');
            }
        }

        return $this->render('comptable/facture_new.html.twig', [
            'etudiants' => $this->etudiantRepository->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']),
            'filieres' => $filieres,
            'etudiantSelectionne' => $request->query->getInt('etudiant'),
            'types' => self::TYPES_PAIEMENT,
        ]);
    }

    #[Route('/factures/{id}', name: 'comptable_factures_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function facture(Facture $facture): Response
    {
        return $this->render('comptable/facture_show.html.twig', [
            'facture' => $facture,
            'modes' => self::MODES_PAIEMENT,
        ]);
    }

    #[Route('/factures/{id}/annuler', name: 'comptable_factures_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function annulerFacture(Request $request, Facture $facture): Response
    {
        $this->verifierCsrf($request, 'annuler-facture'.$facture->getId());

        if ($facture->getMontantPaye() > 0) {
            $this->addFlash('error', 'Cette facture a déjà reçu des paiements : annulez-les d\'abord.');
        } else {
            $facture->setStatut(Facture::STATUT_ANNULE);
            $this->entityManager->flush();
            $this->addFlash('success', 'La facture a été annulée.');
        }

        return $this->redirectToRoute('comptable_factures_show', ['id' => $facture->getId()]);
    }

    #[Route('/etudiants', name: 'comptable_etudiants', methods: ['GET'])]
    public function etudiants(Request $request): Response
    {
        $filtres = $this->lireFiltres($request, ['statut', 'recherche', 'filiere']);
        $comptes = $this->calculerComptes($filtres);

        return $this->render('comptable/etudiants.html.twig', [
            'comptes' => $comptes,
            'filtres' => $filtres,
            'filieres' => $this->entityManager->getRepository(Filiere::class)->findBy([], ['nom' => 'ASC']),
            'totaux' => [
                'du' => array_sum(array_column($comptes, 'du')),
                'paye' => array_sum(array_column($comptes, 'paye')),
                'solde' => array_sum(array_column($comptes, 'solde')),
            ],
        ]);
    }

    #[Route('/etudiants/{id}', name: 'comptable_etudiants_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function etudiant(Etudiant $etudiant): Response
    {
        $factures = $this->factureRepository->findBy(['etudiant' => $etudiant], ['dateCreation' => 'DESC']);
        $paiements = $this->paiementRepository->findBy(['etudiant' => $etudiant], ['datePaiement' => 'DESC']);

        $du = array_sum(array_map(fn (Facture $f) => $f->getStatut() !== Facture::STATUT_ANNULE ? $f->getMontant() : 0, $factures));
        $paye = array_sum(array_map(fn (Paiement $p) => $p->getStatut() === 'paye' ? $p->getMontant() : 0, $paiements));

        return $this->render('comptable/etudiant_show.html.twig', [
            'etudiant' => $etudiant,
            'factures' => $factures,
            'paiements' => $paiements,
            'modes' => self::MODES_PAIEMENT,
            'compte' => ['du' => $du, 'paye' => $paye, 'solde' => $du - $paye],
        ]);
    }

    #[Route('/rapports', name: 'comptable_rapports', methods: ['GET'])]
    public function rapports(Request $request): Response
    {
        [$debut, $fin] = $this->lirePeriode($request, '-11 months');

        $parMois = $this->paiementRepository->getTotauxParMois($debut, $fin);
        $parType = $this->paiementRepository->getTotauxParType($debut, $fin);
        $encaisse = $this->paiementRepository->getTotalEncaisse($debut, $fin);
        $facture = $this->factureRepository->getTotalFacture($debut, $fin);

        return $this->render('comptable/rapports.html.twig', [
            'debut' => $debut,
            'fin' => $fin,
            'encaisse' => $encaisse,
            'facture' => $facture,
            'tauxRecouvrement' => $facture > 0 ? round($encaisse / $facture * 100, 1) : null,
            'mois' => array_keys($parMois),
            'montantsParMois' => array_values($parMois),
            'parType' => $parType,
            'types' => array_keys($parType),
            'montantsParType' => array_values($parType),
            'retards' => $this->factureRepository->findFacturesEnRetard(),
        ]);
    }

    #[Route('/export/{rapport}.csv', name: 'comptable_export', methods: ['GET'], requirements: ['rapport' => 'paiements|factures|retards|soldes'])]
    public function export(Request $request, string $rapport): StreamedResponse
    {
        [$debut, $fin] = $this->lirePeriode($request, '-11 months');

        [$entetes, $lignes] = match ($rapport) {
            'paiements' => [
                ['Date', 'Étudiant', 'Type', 'Mode', 'Référence', 'Facture', 'Montant', 'Statut'],
                array_map(fn (Paiement $p) => [
                    $p->getDatePaiement()?->format('d/m/Y'),
                    $p->getEtudiant()->getNom().' '.$p->getEtudiant()->getPrenom(),
                    $p->getType(),
                    self::MODES_PAIEMENT[$p->getModePaiement()] ?? '',
                    $p->getReference(),
                    $p->getFacture() ? 'F-'.$p->getFacture()->getId() : '',
                    $this->montantCsv($p->getMontant()),
                    $p->getStatut(),
                ], $this->paiementRepository->findFiltres(['debut' => $debut, 'fin' => $fin])),
            ],
            'factures' => [
                ['N°', 'Émise le', 'Étudiant', 'Libellé', 'Montant', 'Payé', 'Reste', 'Échéance', 'Statut'],
                array_map(fn (Facture $f) => $this->ligneFacture($f), $this->factureRepository->findFiltres(['debut' => $debut, 'fin' => $fin])),
            ],
            'retards' => [
                ['N°', 'Émise le', 'Étudiant', 'Libellé', 'Montant', 'Payé', 'Reste', 'Échéance', 'Statut'],
                array_map(fn (Facture $f) => $this->ligneFacture($f), $this->factureRepository->findFacturesEnRetard()),
            ],
            'soldes' => [
                ['Étudiant', 'Filière', 'Total facturé', 'Total payé', 'Solde', 'Statut'],
                array_map(fn (array $c) => [
                    $c['etudiant']->getNom().' '.$c['etudiant']->getPrenom(),
                    $c['etudiant']->getFiliere()?->getNom(),
                    $this->montantCsv($c['du']),
                    $this->montantCsv($c['paye']),
                    $this->montantCsv($c['solde']),
                    $c['statut_libelle'],
                ], $this->calculerComptes([])),
            ],
        };

        $response = new StreamedResponse(function () use ($entetes, $lignes) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");
            fputcsv($sortie, $entetes, ';');
            foreach ($lignes as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }
            fclose($sortie);
        });
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s-%s.csv"', $rapport, date('Y-m-d')));

        return $response;
    }

    /**
     * @return list<array{etudiant: Etudiant, du: float, paye: float, solde: float, statut: string, statut_libelle: string}>
     */
    private function calculerComptes(array $filtres): array
    {
        $qb = $this->etudiantRepository->createQueryBuilder('e')
            ->leftJoin('e.filiere', 'fi')
            ->addSelect('fi')
            ->orderBy('e.nom')
            ->addOrderBy('e.prenom');
        if (!empty($filtres['recherche'])) {
            $qb->andWhere('e.nom LIKE :q OR e.prenom LIKE :q OR e.email LIKE :q')->setParameter('q', '%'.$filtres['recherche'].'%');
        }
        if (!empty($filtres['filiere'])) {
            $qb->andWhere('fi.id = :filiere')->setParameter('filiere', (int) $filtres['filiere']);
        }

        $totauxDus = $this->factureRepository->getTotauxParEtudiant();
        $totauxPayes = $this->paiementRepository->getTotauxParEtudiant();
        $enRetard = [];
        foreach ($this->factureRepository->findFacturesEnRetard() as $facture) {
            $enRetard[$facture->getEtudiant()->getId()] = true;
        }

        $comptes = [];
        foreach ($qb->getQuery()->getResult() as $etudiant) {
            $id = $etudiant->getId();
            $du = $totauxDus[$id] ?? 0.0;
            $paye = $totauxPayes[$id] ?? 0.0;
            $solde = $du - $paye;

            [$statut, $libelle] = match (true) {
                isset($enRetard[$id]) => ['en_retard', 'En retard'],
                $solde > 0.001 => ['solde_du', 'Solde à régler'],
                default => ['a_jour', 'À jour'],
            };

            if (!empty($filtres['statut']) && $filtres['statut'] !== $statut) {
                continue;
            }

            $comptes[] = [
                'etudiant' => $etudiant,
                'du' => $du,
                'paye' => $paye,
                'solde' => $solde,
                'statut' => $statut,
                'statut_libelle' => $libelle,
            ];
        }

        return $comptes;
    }

    private function ligneFacture(Facture $f): array
    {
        return [
            'F-'.$f->getId(),
            $f->getDateCreation()?->format('d/m/Y'),
            $f->getEtudiant()->getNom().' '.$f->getEtudiant()->getPrenom(),
            $f->getLibelle(),
            $this->montantCsv($f->getMontant()),
            $this->montantCsv($f->getMontantPaye()),
            $this->montantCsv($f->getResteAPayer()),
            $f->getDateEcheance()?->format('d/m/Y'),
            $f->isEnRetard() ? 'en retard' : $f->getStatut(),
        ];
    }

    private function montantCsv(?float $montant): string
    {
        return number_format((float) $montant, 2, ',', '');
    }

    /**
     * @return array<string, mixed>
     */
    private function lireFiltres(Request $request, array $cles): array
    {
        $filtres = [];
        foreach ($cles as $cle) {
            $filtres[$cle] = trim((string) $request->query->get($cle, ''));
        }

        return $filtres;
    }

    /**
     * @return array{0: \DateTime, 1: \DateTime}
     */
    private function lirePeriode(Request $request, string $debutParDefaut): array
    {
        try {
            $debut = new \DateTime($request->query->get('debut') ?: 'first day of '.$debutParDefaut);
            $fin = new \DateTime($request->query->get('fin') ?: 'today');
        } catch (\Exception) {
            $debut = new \DateTime('first day of '.$debutParDefaut);
            $fin = new \DateTime('today');
        }
        $debut->setTime(0, 0);
        $fin->setTime(23, 59, 59);

        return $debut <= $fin ? [$debut, $fin] : [$fin->setTime(0, 0), $debut->setTime(23, 59, 59)];
    }

    private function verifierCsrf(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de sécurité invalide.');
        }
    }
}
