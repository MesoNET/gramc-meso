<?php

namespace App\Command;

use App\Entity\Laboratoire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentIndex = 0; // utilisé pour le paramètre offset de l'api
        do {
            $output->writeln($currentIndex.' laboratoires scannés');
            $response = $this->faireRequete($currentIndex);
            if (!Response::HTTP_OK == $response->getStatusCode()) {
                $this->gererEchec($output, $response);
            }
            $totalCount = (int) $response->toArray()['total_count'];
            $results = $response->toArray()['results'];
            for ($i = 0; $i < count($results); ++$i) {
                $ligne = $results[$i];
                // ajout des labosCorrespondants actifs
                $numeroNnationalStructure = $ligne['numero_national_de_structure'];
                $labosCorrespondants = $this->entityManager->getRepository(Laboratoire::class)->findBy([
                    'numeroNnationalStructure' => $numeroNnationalStructure,
                ]);
                if ('active' == strtolower($ligne['etat'])) {
                    if (!$labosCorrespondants) {
                        $lab = (new Laboratoire())
                            ->setNomLabo($ligne['libelle'])
                            ->setAcroLabo($ligne['sigle'])
                            ->setNumeroLabo($currentIndex + $i)
                            ->setNumeroNnationalStructure($numeroNnationalStructure)
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
        } while (0 != count($results) && $currentIndex < 9900);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function faireRequete(int $currentIndex): \Symfony\Contracts\HttpClient\ResponseInterface
    {
        return $this->HTTPClient->request(
            'GET',
            $_ENV['API_LABO_URL'].'?limit=100&offset='.$currentIndex,
            ['http_version' => '1.1']);
    }

    public function gererEchec(OutputInterface $output, \Symfony\Contracts\HttpClient\ResponseInterface $response)
    {
        $output->writeln('erreur');
        $output->writeln($response->getContent());

        return $this::FAILURE;
    }
}
