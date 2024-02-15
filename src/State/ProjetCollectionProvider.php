<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Projet;
use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class ProjetCollectionProvider implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $itemProvider,
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Fournit les projets qui ont des users sur le serveur connecté.
     *
     * @return array|object|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            $projets = $this->itemProvider->provide($operation, $uriVariables, $context);
            $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);

            return array_filter($projets, function (Projet $projet) use ($serveur) {
                return array_intersect($projet->getUser()->toArray(), $serveur->getUser()->toArray());
            });
        }

        return null;
    }
}
