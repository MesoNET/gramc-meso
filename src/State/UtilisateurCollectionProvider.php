<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Individu;
use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class UtilisateurCollectionProvider implements ProviderInterface
{
    public function __construct(
        // #[Autowire('api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $itemProvider,
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): null|array|object
    {
        if ($operation instanceof CollectionOperationInterface) {
            $individus = $this->itemProvider->provide($operation, $uriVariables, $context);

            return array_filter($individus, function (Individu $individu) {
                $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);

                return array_intersect($individu->getUser()->toArray(), $serveur->getUser()->toArray());
            });
        }

        return null;
    }
}
