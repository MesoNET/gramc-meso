<?php

namespace App\Tests\Application;

use App\Entity\Individu;
use App\Tests\Support\ApplicationTester;
use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Codeception\Example;

class AvailabilityCest
{
    public function _before()
    {
    }

    #[Group('available')]
    #[DataProvider('pageProvider')]
    public function pageIsAvailable(ApplicationTester $I, Example $example)
    {
        $admin = $I->grabEntityFromRepository(Individu::class, ['mail' => 'admin@exemple.com']);
        $I->amLoggedInAs($admin);
        $I->amOnPage($example['url']);
        $I->seeResponseCodeIsSuccessful();
    }

    protected function pageProvider(): array  // to make it public use `_` prefix
    {
        return [
            ['url' => '/'],
            ['url' => 'clessh/gerer'],
            ['url' => 'clessh/gerer_all'],
            ['url' => '/clessh/26}/supprimer'],
            ['url' => '/clessh/ajouter'],
            ['url' => 'etablissement'],
            ['url' => 'etablissement/new'],
            ['url' => 'etablissement/1'],
            ['url' => 'etablissement/1/edit'],
            ['url' => 'expertise'],
            ['url' => 'expertise/consulter/1'],
            ['url' => 'expertise/listedyn/1//modifier'],
            ['url' => 'expertise/1/valider'],
            ['url' => 'formation'],
            ['url' => 'formation/gerer'],
            ['url' => 'formation/ajouter'],
            ['url' => 'formation/1/modifier'],
            ['url' => 'formation/1/supprimer'],
            ['url' => 'admin/accueil'],
            ['url' => 'aide'],
            ['url' => '/profil'],
            ['url' => '/nouveau_compte'],
            ['url' => '/nouveau_profil'],
            ['url' => '/connexions'],
            ['url' => '/admin_red'],
            ['url' => 'individu'],
            ['url' => 'individu/4/supprimer'],
            ['url' => 'individu/1/remplacer'],
            ['url' => 'individu/new'],
            ['url' => 'individu/1/show'],
            ['url' => 'individu/1/edit'],
            ['url' => 'individu/ajouter'],
            ['url' => 'individu/^1/invitation'],
            ['url' => 'individu/invitations/1/supprimer_invitation'],
            ['url' => 'individu/1/devenir_admin'],
            ['url' => 'individu/1/plus_admin'],
            ['url' => 'individu/1/devenir_obs'],
            ['url' => 'individu/1/plus_obs'],
            ['url' => 'individu/devenir_sysadmin'],
            ['url' => 'individu/plus_sysadmin'],
            ['url' => 'individu/devenir_president'],
            ['url' => 'individu/plus_president'],
            ['url' => 'individu/devenir_expert'],
            ['url' => 'individu/plus_expert'],
            ['url' => 'individu/devenir_valideur'],
            ['url' => 'individu/plus_valideur'],
            ['url' => 'individu/activer'],
            ['url' => 'individu/4/desactiver'],
            ['url' => 'individu/1/thematique'],
            ['url' => 'individu/1/eppn'],
            ['url' => 'individu/mail_autocomplete''],
            ['url' => 'individu/gerer'],
            ['url' => 'individu/liste'],
            ['url' => 'journal/list'],
            ['url' => 'journal/1'],
            ['url' => 'laboratoire/gerer'],
            ['url' => 'laboratoire/ajouter'],
            ['url' => 'laboratoire'],
            ['url' => 'laboratoire/1/modifier'],
            ['url' => '/login'],
            ['url' => 'laboratoire'],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
            ['url' => ''],
        ];
    }
}
