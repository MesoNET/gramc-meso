<?php

namespace App\Factory;

use App\Entity\ServiceExterne;
use App\Repository\ServiceExterneRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<ServiceExterne>
 *
 * @method        ServiceExterne|Proxy                     create(array|callable $attributes = [])
 * @method static ServiceExterne|Proxy                     createOne(array $attributes = [])
 * @method static ServiceExterne|Proxy                     find(object|array|mixed $criteria)
 * @method static ServiceExterne|Proxy                     findOrCreate(array $attributes)
 * @method static ServiceExterne|Proxy                     first(string $sortedField = 'id')
 * @method static ServiceExterne|Proxy                     last(string $sortedField = 'id')
 * @method static ServiceExterne|Proxy                     random(array $attributes = [])
 * @method static ServiceExterne|Proxy                     randomOrCreate(array $attributes = [])
 * @method static ServiceExterneRepository|RepositoryProxy repository()
 * @method static ServiceExterne[]|Proxy[]                 all()
 * @method static ServiceExterne[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static ServiceExterne[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static ServiceExterne[]|Proxy[]                 findBy(array $attributes)
 * @method static ServiceExterne[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static ServiceExterne[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<ServiceExterne> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<ServiceExterne> createOne(array $attributes = [])
 * @phpstan-method static Proxy<ServiceExterne> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<ServiceExterne> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<ServiceExterne> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<ServiceExterne> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<ServiceExterne> random(array $attributes = [])
 * @phpstan-method static Proxy<ServiceExterne> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<ServiceExterne> repository()
 * @phpstan-method static list<Proxy<ServiceExterne>> all()
 * @phpstan-method static list<Proxy<ServiceExterne>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<ServiceExterne>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<ServiceExterne>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<ServiceExterne>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<ServiceExterne>> randomSet(int $number, array $attributes = [])
 */
final class ServiceExterneFactory extends ModelFactory
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
            'password' => self::faker()->text(),
            'roles' => [],
            'username' => self::faker()->text(180),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): self
    {
        return $this
            ->afterInstantiate(function (ServiceExterne $serviceExterne) {
                $serviceExterne->setPassword($this->passwordHasher->hashPassword($serviceExterne, $serviceExterne->getPassword()));
            })
        ;
    }

    protected static function getClass(): string
    {
        return ServiceExterne::class;
    }
}
