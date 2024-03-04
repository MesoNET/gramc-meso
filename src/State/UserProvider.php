<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Serveur;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class UserProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Renvoie le user associé à l'individu et au projet passés dans l'uritemplate et au serveur actuellement identifié.
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);

        return $this->entityManager->getRepository(User::class)->findOneBy(['individu' => $uriVariables['individu'], 'projet' => $uriVariables['projet'], 'serveur' => $serveur]);
    }
}
