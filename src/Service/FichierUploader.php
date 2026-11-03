<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Stocke les documents privés (justificatifs, rendus de devoirs...) hors du dossier public.
 * Ils ne sont accessibles qu'au travers de FichierController, qui contrôle les droits.
 */
class FichierUploader
{
    public const TAILLE_MAX = 10 * 1024 * 1024;

    private const EXTENSIONS_AUTORISEES = [
        'pdf', 'doc', 'docx', 'odt', 'rtf', 'txt',
        'xls', 'xlsx', 'ods', 'csv', 'ppt', 'pptx', 'odp',
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'zip',
    ];

    public function __construct(
        #[Autowire('%private_uploads_directory%')]
        private string $dossierRacine,
        private SluggerInterface $slugger,
    ) {}

    /**
     * @return array{nom: string, fichier: string} nom d'origine et nom de stockage
     */
    public function upload(UploadedFile $fichier, string $sousDossier): array
    {
        if (!$fichier->isValid()) {
            throw new \InvalidArgumentException('Le fichier n\'a pas pu être envoyé : '.$fichier->getErrorMessage());
        }

        if ($fichier->getSize() > self::TAILLE_MAX) {
            throw new \InvalidArgumentException(sprintf('« %s » dépasse la taille maximale de 10 Mo.', $fichier->getClientOriginalName()));
        }

        $extension = strtolower($fichier->getClientOriginalExtension());
        if (!in_array($extension, self::EXTENSIONS_AUTORISEES, true)) {
            throw new \InvalidArgumentException(sprintf('Le format de « %s » n\'est pas autorisé.', $fichier->getClientOriginalName()));
        }

        $base = $this->slugger->slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME))->lower()->truncate(60);
        $nomStockage = sprintf('%s-%s.%s', $base ?: 'document', bin2hex(random_bytes(6)), $extension);

        $fichier->move($this->getDossier($sousDossier), $nomStockage);

        return ['nom' => $fichier->getClientOriginalName(), 'fichier' => $nomStockage];
    }

    public function getChemin(string $sousDossier, string $nomStockage): ?string
    {
        if ($nomStockage === '' || basename($nomStockage) !== $nomStockage) {
            return null;
        }

        $chemin = $this->getDossier($sousDossier).'/'.$nomStockage;

        return is_file($chemin) ? $chemin : null;
    }

    public function supprimer(string $sousDossier, ?string $nomStockage): void
    {
        if ($nomStockage && $chemin = $this->getChemin($sousDossier, $nomStockage)) {
            @unlink($chemin);
        }
    }

    public function getExtensionsAutorisees(): array
    {
        return self::EXTENSIONS_AUTORISEES;
    }

    private function getDossier(string $sousDossier): string
    {
        $dossier = $this->dossierRacine.'/'.$sousDossier;
        if (!is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        return $dossier;
    }
}
