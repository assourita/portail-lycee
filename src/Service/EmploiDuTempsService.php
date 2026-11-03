<?php

namespace App\Service;

use App\Entity\EmploiDuTemps;
use Doctrine\ORM\EntityManagerInterface;

class EmploiDuTempsService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Créneaux du jour, ou du prochain jour de la semaine qui en comporte.
     *
     * @param string $champ « enseignant » ou « filiere »
     *
     * @return array{libelle: string, creneaux: EmploiDuTemps[], parSemaine: int}
     */
    public function prochainsCreneaux(string $champ, ?object $valeur): array
    {
        if (!$valeur || !in_array($champ, ['enseignant', 'filiere'], true)) {
            return ['libelle' => 'Aujourd\'hui', 'creneaux' => [], 'parSemaine' => 0];
        }

        $tous = $this->em->getRepository(EmploiDuTemps::class)->createQueryBuilder('e')
            ->join('e.jour', 'j')
            ->addSelect('j')
            ->leftJoin('e.matiere', 'm')
            ->addSelect('m')
            ->where("e.$champ = :valeur")
            ->setParameter('valeur', $valeur)
            ->orderBy('j.ordre', 'ASC')
            ->addOrderBy('e.heureDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $parJour = [];
        foreach ($tous as $creneau) {
            $parJour[$creneau->getJour()->getOrdre()][] = $creneau;
        }

        $aujourdhui = (int) date('N');
        for ($decalage = 0; $decalage < 7; $decalage++) {
            $ordre = ($aujourdhui - 1 + $decalage) % 7 + 1;
            if (!empty($parJour[$ordre])) {
                $libelle = match ($decalage) {
                    0 => 'Aujourd\'hui',
                    1 => 'Demain',
                    default => $parJour[$ordre][0]->getJour()->getNom(),
                };

                return ['libelle' => $libelle, 'creneaux' => $parJour[$ordre], 'parSemaine' => count($tous)];
            }
        }

        return ['libelle' => 'Aujourd\'hui', 'creneaux' => [], 'parSemaine' => 0];
    }
}
