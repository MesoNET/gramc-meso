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
        //$admin = $I->grabEntityFromRepository(Individu::class, ['mail' => 'admin@exemple.com']);
        //$I->amLoggedInAs($admin);
        $I->amOnPage('connexion_dbg');
        $I->click('Connexion');
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
            ['url' => 'individu/mail_autocomplete'],
            ['url' => 'individu/gerer'],
            ['url' => 'individu/liste'],
            ['url' => 'journal/list'],
            ['url' => 'journal/1'],
            ['url' => 'laboratoire/gerer'],
            ['url' => 'laboratoire/ajouter'],
            ['url' => 'laboratoire'],
            ['url' => 'laboratoire/1/modifier'],
            ['url' => '/deconnexion'],
            ['url' => 'param/new'],
            ['url' => 'param/1/show'],
            ['url' => 'param/1/edit'],
            ['url' => 'param/avancer'],
            ['url' => 'projet/rgpd'],
            ['url' => 'projet/M22001/fermer'],
            ['url' => 'projet/M22001/back'],
            ['url' => 'projet/M22001/fwd'],
            ['url' => 'projet/dynamiques'],
            ['url' => 'projet/gerer'],
            ['url' => 'projet/avant_nouveau/4'],
            ['url' => 'projet/nouveau/'],
            ['url' => 'projet/accueil'],
            ['url' => 'projet/M22001/consulter/1'],
            ['url' => 'publication/autocomplete'],
            ['url' => 'publication/new'],
            ['url' => 'rallonge'],
            ['url' => 'rallonge/dynamiques'],
            ['url' => '/ressource/gerer'],
            ['url' => '/ressource/gere'],
            ['url' => 'serveur/gerer'],
            ['url' => 'serveur/ajouter'],
            ['url' => 'serveur/BOREALE/modifier'],
            ['url' => 'serveur/BOREALE/supprimer'],
            ['url' => 'thematique/'],
            ['url' => 'user/3/modif'],
            ['url' => 'version/'],
            ['url' => 'version/new'],
            ['url' => 'version/01M22001/avant_supprimer'],
            ['url' => 'version/01M22001/supprimer'],
            ['url' => 'version/01M22001/televerser_fiche'],
            ['url' => 'version/01M22001/televerser_fiche_admin'],
            ['url' => 'version'],
            ['url' => 'version/01M22001/responsable'],
            ['url' => 'version/01M22001/collaborateurs'],
            ['url' => 'version/01M22001/envoyer'],
            ['url' => 'version/televersement'],
            ['url' => 'version/01M22001/avant_modifier'],
            ['url' => 'version/01M22001/modifier'],
           ];
    }
}
