<?php

namespace App\Command;

use App\Entity\Laboratoire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'import:labo',
    description: 'Importe les nouveaux serveurs disponibles dans le RNSR',
)]
class ImporterLaboCommand extends Command
{
    public function __construct(
        private HttpClientInterface $HTTPClient,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    /**
     * Importe les laboratoires actifs et note les inactifs déjà présents en bd. Doit être exécutée une fois pour les actifs et une fois pour les inactifs à cause d'une limitation de l'API qui n'autorise pas les requêtes au-delà de l'inex 10000.
     *
     * @param OutputInterface $output
     * @param bool $actif Détermine si on gère les serveurs actifs ou inactifs
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function gererLabos(OutputInterface $output, bool $actif): void
    {
        $currentIndex = 0; // utilisé pour le paramètre offset de l'api
        do {
            $output->writeln($currentIndex.' laboratoires scannés');
            $response = $this->faireRequete($currentIndex, $actif);
            if (!Response::HTTP_OK == $response->getStatusCode()) {
                $this->gererEchec($output, $response);
            }
            $results = $response->toArray()['results'];
            for ($i = 0; $i < count($results); ++$i) {
                $ligne = $results[$i];
                // ajout des labosCorrespondants actifs
                $numeroNationalStructure = $ligne['numero_national_de_structure'];
                $labosCorrespondants = $this->entityManager->getRepository(Laboratoire::class)->findBy([
                    'numeroNationalStructure' => $numeroNationalStructure,
                ]);
                if ($actif) {
                    if (!$labosCorrespondants) {
                        $lab = (new Laboratoire())
                            ->setNomLabo($ligne['libelle'])
                            ->setAcroLabo($ligne['sigle'])
                            ->setNumeroLabo($currentIndex + $i)
                            ->setNumeroNationalStructure($numeroNationalStructure)
                            ->setActif(true);
                        $this->entityManager->persist($lab);
                        $output->writeln('Ajout du laboratoire '.$ligne['libelle']);
                    }
                } // Cherche les labosCorrespondants inactifs
                else {
                    if ($labosCorrespondants) {
                        foreach ($labosCorrespondants as $lab) {
                            $lab->setActif(false)->setNumeroDeStructureSuccesseur($ligne('numero_de_structure_successeur'));
                            $output->writeln($lab->getNomLabo().' est devenu inactif');
                        }
                    }
                }
            }
            $currentIndex += 100;
        } while (0 != count($results));
        $this->entityManager->flush();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Laboratoires actifs');
        $output->writeln('Laboratoires inactifs');
        $this->gererLabos($output, actif: true);
        $this->gererLabos($output, actif: false);

        return Command::SUCCESS;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function faireRequete(int $currentIndex, bool $actif): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        if ($actif) {
            $etat = 'Active';
        } else {
            $etat = 'Inactive';
        }

        return $this->HTTPClient->request(
            'GET',
            $_ENV['API_LABO_URL'].'?limit=100&where=etat%3D\''.$etat.'\'&offset='.$currentIndex,
            ['http_version' => '1.1']);
    }

    public function gererEchec(OutputInterface $output, \Symfony\Contracts\HttpClient\ResponseInterface $response)
    {
        $output->writeln('erreur');
        $output->writeln($response->getContent());

        return $this::FAILURE;
    }
}
