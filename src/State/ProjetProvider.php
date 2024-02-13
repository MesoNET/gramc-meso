<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Projet;
use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class ProjetProvider implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $itemProvider,
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Fournit le projet ayant des users sur le serveur connecté.
     *
     * @return array|object|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|object|null
    {
        $projet = $this->itemProvider->provide($operation, $uriVariables, $context);
        $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);
        if (array_intersect($projet->getUser()->toArray(), $serveur->getUser()->toArray())) {
            return $projet;
        } else {
            return null;
        }
    }
}
