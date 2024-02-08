<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Projet;
use App\Entity\Serveur;
use App\GramcServices\Etat;
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): null|array|object
    {
        if ($operation instanceof CollectionOperationInterface) {
            $projets = $this->itemProvider->provide($operation, $uriVariables, $context);

            return array_filter($projets, function (Projet $projet) {
                if (Etat::RENOUVELABLE == $projet->getEtatProjet()) {
                    $serveur = $this->entityManager->getRepository(Serveur::class)->findOneBy(['admname' => $this->security->getUser()->getUserIdentifier()]);

                    return array_intersect($projet->getUser()->toArray(), $serveur->getUser()->toArray());
                } else {
                    return false;
                }
            });
        }

        return null;
    }
}
