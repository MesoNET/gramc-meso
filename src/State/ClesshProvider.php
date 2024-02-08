<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class IndividuProvider implements ProviderInterface
{
    public function __construct(
        private ProviderInterface $itemProvider,
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Fournit l'individu ayant des users sur le serveur connecté.
     *
     * @return array|object|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): null|array|object
    {
        $individu = $this->itemProvider->provide($operation, $uriVariables, $context);
        $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);
        if (array_intersect($individu->getUser()->toArray(), $serveur->getUser()->toArray())) {
            return $individu;
        } else {
            return null;
        }
    }
}
