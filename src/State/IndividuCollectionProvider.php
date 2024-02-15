<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Individu;
use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class IndividuCollectionProvider implements ProviderInterface
{
    public function __construct(
        // #[Autowire('api_platform.doctrine.orm.state.collection_provider')]
        private ProviderInterface $itemProvider,
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Fournit les individus ayant des users sur le serveur connecté.
     *
     * @return array|object|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $individus = $this->itemProvider->provide($operation, $uriVariables, $context);
            $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);

            return array_filter($individus, function (Individu $individu) use ($serveur) {
                return array_intersect($individu->getUser()->toArray(), $serveur->getUser()->toArray());
            });
        }

        return null;
    }
}
