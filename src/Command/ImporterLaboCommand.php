<?php

namespace App\Command;

use App\Entity\Laboratoire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:labo',
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
            $response = $this->HTTPClient->request(
                'GET',
                $_ENV['API_LABO_URL'].'?where=etat%3D%27Active%27&limit=100&offset='.$currentIndex,
                ['http_version' => '1.1']
            );
            if (!Response::HTTP_OK == $response->getStatusCode()) {
                $output->writeln('erreur');
                $output->writeln($response->getContent());

                return $this::FAILURE;
            }
            $results = $response->toArray()['results'];
            foreach ($results as $ligne) {
                $libelle = $ligne['libelle'];
                if (null != $libelle && !$this->entityManager->getRepository(Laboratoire::class)->findBy([
                        'nomLabo' => $libelle,
                    ])) {
                    $lab = (new Laboratoire())
                        ->setNomLabo($libelle)
                        ->setAcroLabo($ligne['sigle'])
                        ->setNumeroLabo('12');
                    $this->entityManager->persist($lab);
                    $output->writeln('Ajout du laboratoire '.$libelle);
                }
            }
            $currentIndex += 100;
        } while (0 != count($results));
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
