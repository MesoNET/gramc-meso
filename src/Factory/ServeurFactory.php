<?php

namespace App\Factory;

use App\Entity\Serveur;
use App\Repository\ServeurRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Serveur>
 *
 * @method        Serveur|Proxy                     create(array|callable $attributes = [])
 * @method static Serveur|Proxy                     createOne(array $attributes = [])
 * @method static Serveur|Proxy                     find(object|array|mixed $criteria)
 * @method static Serveur|Proxy                     findOrCreate(array $attributes)
 * @method static Serveur|Proxy                     first(string $sortedField = 'id')
 * @method static Serveur|Proxy                     last(string $sortedField = 'id')
 * @method static Serveur|Proxy                     random(array $attributes = [])
 * @method static Serveur|Proxy                     randomOrCreate(array $attributes = [])
 * @method static ServeurRepository|RepositoryProxy repository()
 * @method static Serveur[]|Proxy[]                 all()
 * @method static Serveur[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Serveur[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static Serveur[]|Proxy[]                 findBy(array $attributes)
 * @method static Serveur[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static Serveur[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class ServeurFactory extends ModelFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function getDefaults(): array
    {
        return [
            'roles' => [],
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            ->afterInstantiate(function (Serveur $serveur) {
                $serveur->setPassword($this->passwordHasher->hashPassword($serveur, $serveur->getPassword()));
            })
        ;
    }

    protected static function getClass(): string
    {
        return Serveur::class;
    }
}
