<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Serveur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class UtilisateurProvider implements ProviderInterface
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
        $individu = $this->itemProvider->provide($operation, $uriVariables, $context);
        $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);
        if (array_intersect($individu->getUser()->toArray(), $serveur->getUser()->toArray())) {
            return $individu;
        } else {
            return null;
        }
    }
}
