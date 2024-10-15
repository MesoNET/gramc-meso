<?php

/**
 * This file is part of GRAMC (Computing Ressource Granting Software)
 * GRAMC stands for : Gestion des Ressources et de leurs Attributions pour Mésocentre de Calcul.
 *
 * GRAMC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 *  GRAMC is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with GRAMC.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  authors : Emmanuel Courcelle - C.N.R.S. - UMS 3667 - CALMIP
 *            Nicolas Renon - Université Paul Sabatier - CALMIP
 **/

namespace App\GramcServices;

use App\Entity\CollaborateurVersion;
use App\Entity\Dac;
use App\Entity\Formation;
use App\Entity\FormationVersion;
use App\Entity\Individu;
use App\Entity\Projet;
use App\Entity\Ressource;
use App\Entity\Serveur;
use App\Entity\Session;
use App\Entity\User;
use App\Entity\Version;
use App\Form\DacType;
use App\Form\FormationVersionType;
use App\Form\IndividuForm\IndividuForm;
use App\Form\IndividuFormType;
use App\Utils\Functions;
use App\Validator\Constraints\PagesNumber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ServiceVersions
{
    public function __construct(
        private $prj_prefix,
        private $rapport_directory,
        private $fig_directory,
        private $signature_directory,
        private $max_fig_width,
        private $max_fig_height,
        private $max_size_doc,
        private $resp_peut_modif_collabs,
        private ServiceJournal $sj,
        private ServiceServeurs $sr,
        private ServiceRessources $sroc,
        private ServiceUsers $su,
        private ServiceInvitations $sid,
        private ValidatorInterface $vl,
        private ServiceForms $sf,
        private FormFactoryInterface $ff,
        private TokenStorageInterface $tok,
        private GramcDate $grdt,
        private EntityManagerInterface $em
    ) {
    }

    /****************
     * Création d'une nouvelle version liée à un projet existant, c'est-à-dire:
     *    - Création de la version:
     *       * Si c'est la première version, création ex-nihilo
     *       * Sinon, clonage de la dernière version du projet
     *       * Choix du numéro de version
     *    - Création des Dac associés
     *    - Si nécessaire, création des User associés
     *
     * Params: $projet le projet associé
     *
     * Retourne: La nouvelle version
     *
     ************************************************/

    public function creerVersion(Projet $projet): Version
    {
        $su = $this->su;
        $sr = $this->sr;
        $sroc = $this->sroc;
        $token = $this->tok->getToken();
        $em = $this->em;

        $versions = $em->getRepository(Version::class)->findVersions($projet);

        // Si première version du projet
        if (0 === count($versions)) {
            $version = new Version();
            $version->setEtatVersion(Etat::EDITION_DEMANDE);
            // On fixe aussi le type de la version (cf getVersionType())
            // important car le type du projet peut changer (en théorie))
            // En pratique PROJET_DYN est le SEUL TYPE supporté
            $version->setProjet($projet);
            $version->setTypeVersion($projet->getTypeProjet());

            $version->setNbVersion('01');
            $version->setIdVersion('01'.$projet->getIdProjet());

            // Le laboratoire associé est celui du responsable
            $moi = $token->getUser();
            $this->setLaboResponsable($version, $moi);

            // Affectation de l'utilisateur connecté en tant que responsable
            $cv = new CollaborateurVersion($moi);
            $cv->setVersion($version);
            $cv->setResponsable(true);
            $cv->setDeleted(false);

            // Ecriture de collaborateurVersion dans la BD
            $em->persist($cv);

            // Ecriture de la version dans la BD
            $em->persist($version);
            $em->flush();

            // La dernière version est fixée par l'EventListener
            // TODO - mais ici cela ne fonctionne pas car lors du persist de la version de projet n'est pas dans la BD
            $projet->setVersionDerniere($version);
            $em->persist($projet);
            $em->flush($projet);

            // Création du répertoire pour les images
            $dir = $this->imageDir($version);

            // Création de nouveaux User pour le responsable (1 User par serveur)
            // NOTE - ils seront créés seulement lors de la première version
            //        car pour un utilisateur donné il n'y a qu'un user/serveur, même avec plusieurs versions
            $serveurs = $sr->getServeurs();
            foreach ($serveurs as $s) {
                $su->getUser($moi, $projet, $s);
            }
        }

        // Sinon, c'est un renouvellement
        else {
            $verder = $projet->getVersionDerniere();

            $old_dir = $this->imageDir($verder);

            // Clonage de la version à renouveler
            $version = clone $verder;

            // Changement du numéro de version et de l'Id - Tout le reste est identique
            $nb = $this->__incrNbVersion($version->getNbVersion());
            $version->setNbVersion($nb);
            $version->setIdVersion($nb.$projet->getIdProjet());

            // Ecriture de la version dans la BD
            $em->persist($version);
            $em->flush();

            // images: On reprend les images "img_expose" de la version précédente
            //         On ne REPREND PAS les images "img_justif_renou" !!!
            $new_dir = $this->imageDir($version);
            for ($id = 1; $id < 4; ++$id) {
                $f = 'img_expose_'.$id;
                $old_f = $old_dir.'/'.$f;
                $new_f = $new_dir.'/'.$f;
                if (is_file($old_f)) {
                    $rvl = copy($old_f, $new_f);
                    if (false == $rvl) {
                        $sj->errorMessage("VersionController:erreur dans la fonction copy $old_f => $new_f");
                    }
                }
            }

            // Nouveaux collaborateurVersion
            $collaborateurVersions = $verder->getCollaborateurVersion();
            foreach ($collaborateurVersions as $collaborateurVersion) {
                // ne pas reprendre un collaborateur marqué comme supprimé
                if ($collaborateurVersion->getDeleted()) {
                    continue;
                }

                $newCollaborateurVersion = clone $collaborateurVersion;
                $newCollaborateurVersion->setVersion($version);
                $em->persist($newCollaborateurVersion);
            }
            $em->flush();
        }

        // Création de nouveaux Dac (1 Dac par ressource)
        $ressources = $sroc->getRessources();
        foreach ($ressources as $r) {
            $dac = new Dac();
            $dac->setVersion($version);
            $dac->setRessource($r);
            $em->persist($dac);
            $version->addDac($dac);
        }
        $em->flush();

        return $version;
    }

    /******
     * Incrémentation du numéro de version lors d'un renouvellement
     *********************************************/
    private function __incrNbVersion(string $nbVersion): string
    {
        $n = intval($nbVersion);
        ++$n;

        return sprintf('%02d', $n);
    }

    /************************************
     *
     * informations à propos d'une image liée à une version
     *
     * params: $filename Nom du fichier image
     *         $displayName Nom pouvant être affiché (ex: figure 1)
     *         $version  Version associée
     *
     * return: Plein d'informations
     *
     ***************************/
    public function imageProperties(string $filename, string $displayName, Version $version): array
    {
        $full_filename = $this->imagePath($filename, $version);
        if (file_exists($full_filename) && is_file($full_filename)) {
            $imageinfo = [];
            $imgSize = getimagesize($full_filename, $imageinfo);

            return [
                'contents' => base64_encode(file_get_contents($full_filename)),
                'width' => $imgSize[0],
                'height' => $imgSize[1],
                'balise' => $imgSize[2],
                'mime' => $imgSize['mime'],
                'name' => $filename,
                'displayName' => $displayName,
            ];
        } else {
            return [
                'name' => $filename,
                'displayName' => $displayName,
            ];
        }
    }

    /************************************
     *
     * Renvoie le nom du fichier attaché, s'il existe, null sinon
     * params: $version  Version associée
     *
     * return: chemin vers fichier ou null
     *
     ***************************/
    public function getDocument(Version $version): ?string
    {
        $document = $this->imageDir($version).'/document.pdf';
        if (file_exists($document) && is_file($document)) {
            return $document;
        } else {
            return null;
        }
    }

    /**************************************************
     * Crée un formulaire qui permettra de téléverser un fichier pdf
     * Gère le mécanisme de soumission et validation
     * Renvoie un json: soit OK soit les erreurs.
     *
     * Fonctionne aussi bien en ajax avec jquery-upload-file-master
     * que de manière "normale"
     *
     * Appelé par VersionController::televerserAction
     *
     * params = request
     *          dirname : répertoire de destination
     *          filename: nom définitif du fichier
     *
     * return = la form si pas encore soumise
     *          ou une string: "OK"
     *          ou un message d'erreur
     *
     ********************************/
    public function televerserFichier(
        Request $request,
        Version $version,
        string $dirname,
        string $filename,
        string $type
    ): Form|string {
        $sj = $this->sj;
        $ff = $this->ff;
        $sf = $this->sf;
        $max_size_doc = intval($this->max_size_doc);
        $maxSize = strval(1024 * $max_size_doc).'k';

        // Les contraintes ne sont pas les mêmes suivant le type de fichier
        $format_fichier = '';
        $constraints = [];
        switch ($type) {
            case 'pdf':
                $format_fichier = new \Symfony\Component\Validator\Constraints\File(
                    [
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => ' Le fichier doit être un fichier pdf. ',
                        'maxSize' => $maxSize,
                        'uploadIniSizeErrorMessage' => ' Le fichier doit avoir moins de {{ limit }} {{ suffix }}. ',
                        'maxSizeMessage' => ' Le fichier est trop grand ({{ size }} {{ suffix }}), il doit avoir moins de {{ limit }} {{ suffix }}. ',
                    ]
                );
                $constraints = [$format_fichier, new PagesNumber()];
                break;

            case 'jpg':
                $format_fichier = new \Symfony\Component\Validator\Constraints\File(
                    [
                        'mimeTypes' => ['image/jpeg'],
                        'mimeTypesMessage' => " L'image doit être au format jpeg.",
                        'maxSize' => $maxSize,
                        'uploadIniSizeErrorMessage' => ' Le fichier doit avoir moins de {{ limit }} {{ suffix }}. ',
                        'maxSizeMessage' => ' Le fichier est trop grand ({{ size }} {{ suffix }}), il doit avoir moins de {{ limit }} {{ suffix }}. ',
                    ]
                );
                $constraints = [$format_fichier];
                break;

            default:
                $sj->errorMessage(__METHOD__.':'.__LINE__." Erreur interne - type $type pas supporté");
                break;
        }

        $form = $ff
        ->createNamedBuilder('fichier', FormType::class, [], ['csrf_protection' => false])
        ->add(
            'fichier',
            FileType::class,
            [
                'required' => true,
                'label' => 'Fichier à téléverser',
                'constraints' => $constraints,
            ]
        )
        ->getForm();

        $form->handleRequest($request);

        // form soumise et valide = On met le fichier à sa place et on retourne OK
        $rvl = [];
        $rvl['OK'] = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $tempFilename = $form->getData()['fichier'];

            if (is_file($tempFilename) && !is_dir($tempFilename)) {
                $file = new File($tempFilename);
            } elseif (is_dir($tempFilename)) {
                return 'Erreur interne : Le nom  '.$tempFilename.' correspond à un répertoire';
            } else {
                return 'Erreur interne : Le fichier '.$tempFilename." n'existe pas";
            }

            $file->move($dirname, $filename);

            $sj->debugMessage(__METHOD__.':'.__LINE__.' Fichier -> '.$filename);
            $rvl['OK'] = true;

            // Si le type est 'jpg', c'est une image: on la renvoie !
            if ('jpg' == $type) {
                $rvl['properties'] = $this->imageProperties($filename, 'image au format jpeg', $version);
            }

            return json_encode($rvl);
        }

        // formulaire non valide ou autres cas d'erreur = On retourne un message d'erreur
        elseif ($form->isSubmitted() && !$form->isValid()) {
            if (isset($form->getData()['fichier'])) {
                $rvl['message'] = $sf->formError($form->getData()['fichier'], $constraints);

                return json_encode($rvl);
            } else {
                $rvl['message'] = '<strong>Erreurs :</strong>Fichier trop gros ou autre problème';

                return json_encode($rvl);
            }
        } elseif ($request->isXMLHttpRequest()) {
            $rvl['message'] = "Le formulaire n'a pas été soumis";

            return json_encode($rvl);
        }

        // formulaire non soumis = On retourne le formulaire
        else {
            return $form;
        }
    }

    /*************************************************************
     *
     * IMAGES ET DOC ATTACHE
     *
     *************************************************************/
    /*************************
     * Calcule le chemin de fichier de l'image
     *
     * param = $filename Nom du fichier, sans le répertoire ni l'extension
     *         $version  Version associée
     *
     * return = Le chemin complet (si le fichier existe)
     *          Le chemin avec répertoire mais sans extension sinon
     *          TODO - Pas clair du tout !
     *
     ************************************/
    public function imagePath(string $filename, Version $version): string
    {
        $full_filename = $this->imageDir($version).'/'.$filename;

        if (file_exists($full_filename.'.jpeg') && is_file($full_filename.'.jpeg')) {
            $full_filename = $full_filename.'.jpeg';
        }

        return $full_filename;
    }

    /*******************************
     * Crée si besoin le répertoire pour les fichiers d'image
     *
     * param = $version  La version associée
     *
     * return = Le chemin complet vers le répertoire
     *
     *******************************************/
    public function imageDir(Version $version): string
    {
        $dir = $this->fig_directory;
        if (!is_dir($dir)) {
            if (file_exists($dir) && is_file($dir)) {
                unlink($dir);
            }
            mkdir($dir);
            $this->sj->warningMessage('fig_directory '.$dir.' créé !');
        }

        $dir .= '/'.$version->getProjet()->getIdProjet();
        if (!is_dir($dir)) {
            if (file_exists($dir) && is_file($dir)) {
                unlink($dir);
            }
            mkdir($dir);
        }

        $dir .= '/'.$version->getIdVersion();
        if (!is_dir($dir)) {
            if (file_exists($dir) && is_file($dir)) {
                unlink($dir);
            }
            mkdir($dir);
        }

        return $dir;
    }

    /***********************************************************************
     *
     * SIGNATURES
     *
     *************************************************************/

    /***************************************************
     * Renvoie true si le fichier pdf de signature est présent
     * TODO - Le champ prjFicheVal de l'entité Version n'est pas utilisé !
     *        On devrait pouvoir le supprimer ?
     *********************************************************/
    public function isSigne(Version $version): bool
    {
        $file = $this->getSignePath($version);
        if (file_exists($file) && !is_dir($file)) {
            return true;
        } else {
            return false;
        }
    }

    /*****************
     * Retourne le chemin vers le fichier de signature correspondant à cette version
     *          null si pas de fichier de signature
     ****************/
    public function getSigne(Version $version): ?string
    {
        if ($this->isSigne($version)) {
            return $this->getSignePath($version);
        } else {
            return null;
        }
    }

    /*****************************
     * Retourne la taille du fichier de signature arrondi au Ko inférieur
     *****************************/
    public function getSizeSigne(Version $version): int
    {
        $signe = $this->getSigne($version);
        if (null == $signe) {
            return 0;
        } else {
            return intdiv(filesize($signe), 1024);
        }
    }

    /*************************
     * Calcule le chemin de fichier de la fiche signée
     *
     * param = $version  Version
     *
     * return = Le chemin complet du fichier (que le fichier existe ou non)
     *
     ************************************/
    public function getSignePath(Version $version): string
    {
        return $this->getSigneDir($version).'/'.$version.'.pdf';
    }

    /*******************************
    * Crée si besoin le répertoire pour les fiches signées
    *
    * param = $version  La version associée
    *
    * return = Le chemin complet vers le répertoire
    *
    *******************************************/
    public function getSigneDir(Version $version): string
    {
        $dir = $this->signature_directory.'/'.$version->getProjet();
        if (!is_dir($dir)) {
            if (file_exists($dir) && is_file($dir)) {
                unlink($dir);
            }
            mkdir($dir);
            $this->sj->warningMessage('Répertoire pour les fiches signées '.$dir.' créé !');
        }

        return $dir;
    }

    /***********************************************************************
     *
     * RAPPORTS D'ACTIVITE
     *
     *************************************************************/

    /*******************************
     * Crée si besoin le répertoire pour les rapports d'activité
     *
     * param = $version  La version associée
     *
     * return = Le chemin complet vers le répertoire
     *
     *******************************************/
    public function rapportDir(Version $version): string
    {
        $annee = $version->anneeRapport();
        $dir = $this->rapport_directory.'/'.$annee;
        if (!is_dir($dir)) {
            if (file_exists($dir) && is_file($dir)) {
                unlink($dir);
            }
            mkdir($dir);
            $this->sj->warningMessage('rapport_directory '.$dir.' créé !');
        }

        return $dir;
    }

    public function rapportDir1(Projet $projet, string $annee): string
    {
        $dir = $this->rapport_directory.'/'.$annee;
        if (!is_dir($dir)) {
            if (file_exists($dir) && is_file($dir)) {
                unlink($dir);
            }
            mkdir($dir);
            $this->sj->warningMessage('rapport_directory '.$dir.' créé !');
        }

        return $dir;
    }

    /**************************************
     * Changer le responsable d'une version
     **********************************************/
    public function changerResponsable(Version $version, Individu $new): void
    {
        foreach ($version->getCollaborateurVersion() as $item) {
            $collaborateur = $item->getCollaborateur();
            if (null == $collaborateur) {
                $this->sj->errorMessage(__METHOD__.':'.__LINE__.' collaborateur null pour CollaborateurVersion '.$item->getId());
                continue;
            }

            if ($collaborateur->isEqualTo($new)) {
                $item->setResponsable(true);
                $this->em->persist($item);
                $labo = $item->getLabo();
                if (null != $labo) {
                    $version->setPrjLLabo(Functions::string_conversion($labo->getAcroLabo()));
                } else {
                    $this->sj->errorMessage(__METHOD__.':'.__LINE__.' Le nouveau responsable '.$new." ne fait partie d'aucun laboratoire");
                }
                $this->setLaboResponsable($version, $new);
                $this->em->persist($version);
            } elseif (true == $item->getResponsable()) {
                $item->setResponsable(false);
                $this->em->persist($item);
            }
        }
        $this->em->flush();
    }

    /********************************************
     * Trouver un collaborateur d'une version
     *
     * Renvoie soit null, soit le $cv correspondant à $individu
     *
     **********************************************************/
    private function TrouverCollaborateur(Version $version, Individu $individu): ?CollaborateurVersion
    {
        $filteredCollection = $version
                                ->getCollaborateurVersion()
                                ->filter(function ($cv) use ($individu) {
                                    return $cv
                                            ->getCollaborateur()
                                            ->isEqualTo($individu);
                                });

        // Normalement 0 ou 1 !
        if (count($filteredCollection) >= 1) {
            return $filteredCollection->first();
        } else {
            return null;
        }
    }

    /********************************************
     * Supprimer un collaborateur d'une version
     **********************************************************/
    private function supprimerCollaborateur(Version $version, Individu $individu): void
    {
        $em = $this->em;
        $sj = $this->sj;

        $cv = $this->TrouverCollaborateur($version, $individu);
        var_dump($cv);
        $sj->debugMessage("ServiceVersion:supprimerCollaborateur $cv -> $individu supprimé");
        $em->remove($cv);
        $em->flush();
    }

    /*********************************************************
     * modifier les logins d'un collaborateur d'une version
     ***********************************************************/
    private function modifierLogins(Projet $projet, Individu $individu, array $logins): void
    {
        $em = $this->em;
        $su = $this->su;

        foreach ($em->getRepository(Serveur::class)->findAll() as $s) {
            $u = $su->getUser($individu, $projet, $s);
            $k = $s->getnom();
            if (isset($logins[$k])) {
                $u->setLogin($logins[$k]);
                // dd("coucou1", $k, $logins[$k], $s);
            } else {
                $u->setLogin(false);
                // dd("coucou2", $k, $logins[$k], $s);
            }
            $this->em->persist($u);
            $this->em->flush();

        }
    }

    /*******
    * Retourne true si la version correspond à un Nouveau projet
    *
    *      On vérifie que le numéro est 1 !
    *
    **************************************************************/
    public function isNouvelle(Version $version): bool
    {
        if (1 === $version->getNbVersion()) {
            return true;
        } else {
            return false;
        }
    }

    /***********************
     * Renvoie true si la version était active l'année passée en paramètre,
     * c'est-à-dire s'il y a au moins 1 jour de l'année durant lequel
     * la version est active
     *
     * Pour un projet dynamique on utilise la startDate et la endDate
     * Pour un autre projet on utilise les informations de session
     *
     **************************************************************/
    public function isAnnee(Version $version, int $annee): bool
    {
        // Fonction désactivée pour l'instant
        return true;
        $grdt = $this->grdt;

        if (Projet::PROJET_DYN == $version->getTypeVersion()) {
            $annee_courante = intval($grdt->showYear());

            // Si pas de date de début, la version n'a pas démarré
            if (null === $version->getStartDate()) {
                return false;
            } else {
                $s = $version->getStartDate();
            }

            // Si pas de date de fin, la version est en cours
            // Le résultat est le même que si elle s'arrêtait aujourd'hui
            if (null === $version->getEndDate()) {
                $e = $grdt->getNew();
            } else {
                $e = $version->getEndDate();
            }

            // Si les deux sont spécifiés, on vérifie s'il y a chevauchement avec l'année
            $j1 = new \DateTime($grdt->showYear().'-01-01');
            $d31 = new \DateTime($grdt->showYear().'-12-31');

            // Si $s ou $e sont dans l'intervalle on renvoie true
            if ($s >= $j1 && $s <= $d31) {
                return true;
            }

            // if ($version->getIdVersion() === '02M23017') {dd($version->getIdVersion(),$s,$e,$j1,$d31);};
            // Sinon on renvoie false
            return $e >= $j1 && $e <= $d31;
        } else {
            return $version->getFullAnnee() == strval($annee);
        }
    }

    // //////////////////////////////////////////////////
    public function setLaboResponsable(Version $version, Individu $individu): void
    {
        if (null == $individu) {
            return;
        }

        $labo = $individu->getLabo();
        if (null != $labo) {
            $version->setPrjLLabo(Functions::string_conversion($labo));
        } else {
            $this->sj->errorMessage(__METHOD__.':'.__LINE__.' Le nouveau responsable '.$individu." ne fait partie d'aucun laboratoire");
        }
    }

    /*********************************************
     *
     * LES IMAGES
     *
     ********************************************/

    /*************************************************************
     * Efface un seul fichier lié à une version de projet
     *
     *  - Les fichiers img_* et *.pdf du répertoire des figures
     *  - Le fichier de signatures s'il existe
     *  - N'EFFACE PAS LE RAPPORT D'ACTIVITE !
     *    cf. ServiceProjets pour cela
     *************************************************************/
    public function effacerFichier(Version $version, string $filename): void
    {
        // Les figures et les doc attachés
        $img_dir = $this->imageDir($version);

        $fichiers = ['img_expose_1',
            'img_expose_2',
            'img_expose_3',
            'img_justif_renou_1',
            'img_justif_renou_2',
            'img_justif_renou_3',
        ];

        if (in_array($filename, $fichiers)) {
            $path = $img_dir.'/'.$filename;
            unlink($path);
        }
    }

    /*************************************************************
     * Lit un fichier image et renvoie la version base64 pour affichage
     * dans le html
     *************************************************************/
    public function image2Base64(string $filename, Version $version): ?string
    {
        $full_filename = $this->imagePath($filename, $version);

        if (file_exists($full_filename) && is_file($full_filename)) {
            // dd($full_filename);
            // $sj->debugMessage('ServiceVersion image  ' .$filename . ' : ' . base64_encode( file_get_contents( $full_filename ) )  );
            return base64_encode(file_get_contents($full_filename));
        } else {
            return null;
        }
    }

    /***
     * Redimensionne une image aux valeurs fixées dans le fichier de paramètres
     *
     * Utilise convert (d'imagemagick)
     *
     *  params $image, le chemin vers un fichier image
     *
     */
    private function imageRedim(string $image): void
    {
        $cmd = "identify -format '%w %h' $image";
        // $sj->debugMessage('imageRedim cmd identify = ' . $cmd);
        $format = shell_exec($cmd);
        list($width, $height) = explode(' ', $format);
        $width = intval($width);
        $height = intval($height);
        $rap_w = 0;
        $rap_h = 0;
        $rapport = 0;      // Le rapport de redimensionnement

        $max_fig_width = $this->max_fig_width;
        if ($width > $max_fig_width && $max_fig_width > 0) {
            $rap_w = (1.0 * $width) / $max_fig_width;
        }

        $max_fig_height = $this->max_fig_height;
        if ($height > $max_fig_height && $max_fig_height > 0) {
            $rap_h = (1.0 * $height) / $max_fig_height;
        }

        // Si l'un des deux rapports est > 0, on prend le plus grand
        if ($rap_w + $rap_h > 0) {
            $rapport = ($rap_w > $rap_h) ? 1 / $rap_w : 1 / $rap_h;
            $rapport = 100 * $rapport;
        }

        // Si un rapport a été calculé, on redimensionne
        if ($rapport > 1) {
            $cmd = "convert $image -resize $rapport% $image";
            // $sj->debugMessage('imageRedim cmd convert = ' . $cmd);
            shell_exec($cmd);
        }
    }

    /*********************************************
     *
     * LES DEMANDES DE FORMATIONS
     *
     ********************************************/

    /**************************
     * préparation de la liste des formations proposées
     * Récupère dans la base la liste des formations proposées,
     * c'est-à-dire les formations pour lesquelles startDate <= date courante <= endDate
     * Les met dans l'ordre en fonction du champ numeroForm
     * Pour chaque formation, crée un enregistrement de type FormationVersion s'il n'existe pas
     *
     * params = $version
     *
     * return = Un tableau d'objets de type FormationVersion
     *
     *****************************************************************************/

    public function prepareFormations(Version $version): array
    {
        $em = $this->em;
        $sj = $this->sj;

        if (null == $version) {
            $sj->throwException('ServiceVersion:prepareFormations : version null');
        }

        $formations = $em->getRepository(Formation::class)->findAllCurrentDate();

        // Un array indexé par l'identifiant de formation
        $formationVersions = [];
        foreach ($version->getFormationVersion() as $fv) {
            $k = $fv->getFormation()->getId();
            $formationVersions[$k] = $fv;
        }
        // dd($formations);

        $data = [];
        foreach ($formations as $f) {
            // $formationForm = new formationForm($f);

            if (array_key_exists($f->getId(), $formationVersions)) {
                $fv = $formationVersions[$f->getId()];
                // $formationForm->setNombre($fv->getNombre());
            } else {
                $fv = new FormationVersion($f, $version);
            }
            $data[] = $fv;
            // $data[] = $formationForm;
        }

        return $data;
    }

    /********************************************************************
     * Génère et renvoie un form pour modifier les demandes de formation
     ********************************************************************/
    public function getFormationForm(Version $version): FormInterface
    {
        $text_fields = true;
        if ($this->resp_peut_modif_collabs) {
            $text_fields = false;
        }

        return $this->ff
                   ->createNamedBuilder('form_formation', FormType::class, ['formation' => $this->prepareFormations($version)])
                   ->add('formation', CollectionType::class, [
                       'entry_type' => FormationVersionType::class,
                       'label' => true,
                   ])
                    ->getForm();
    }

    /*********************************
     *
     * Validation du formulaire des formations - Retourne true/false
     * Si pas valide (nombre < 0), rend valide en mettant à zéro !
     *
     * params = Tableau de formulaires
     ***********************************************************************/
    public function validateFormationForms(array &$formation_forms): bool
    {
        $val = true;
        foreach ($formation_forms as $iform) {
            if ($iform->getNombre() < 0) {
                $iform->setNombre(0);
                $val = false;
            }
        }

        return $val;
    }

    /***************************************
     * Traitement des formulaires des formations
     *
     * $formation_forms = Tableau contenant un formulaire par formation
     * $version        = La version considérée
     ****************************************************************/
    public function handleFormationForms(array $formation_forms, Version $version): void
    {
        $em = $this->em;

        // dd($formation_forms);
        // On fait la modification sur la version passée en paramètre
        foreach ($formation_forms as $iform) {
            $version->addFormationVersion($iform);
        }
        $em->persist($version);
        $em->flush();
    }

    /*********************************************
     *
     * LES DEMANDES DE RESSOURCES
     *
     ********************************************/

    // TODO - Copié-presque-collé depuis DEMANDES DE FORMATION
    //        Il faudrait rendre tout ça générique !

    /**************************
     * préparation de la liste des ressources disponibles
     * Récupère dans la base la liste des ressources
     * c'est-à-dire toutes les ressources (TODO - Ajouter un champ "disponible")
     * Pour chaque ressource, crée un enregistrement de type Dac s'il n'existe pas
     *
     * params = $version
     *
     * return = Un tableau d'objets de type Dac
     *
     *****************************************************************************/

    public function prepareRessources(Version $version): array
    {
        $em = $this->em;
        $sj = $this->sj;

        if (null == $version) {
            $sj->throwException('ServiceVersion:prepareRessources : version null');
        }

        $ressources = $em->getRepository(Ressource::class)->findAll();

        // Un array indexé par l'identifiant de ressource
        $dacs = [];
        foreach ($version->getDac() as $dac) {
            $k = $dac->getRessource()->getId();
            $dacs[$k] = $dac;
        }
        // dd($formations);

        $data = [];
        foreach ($ressources as $r) {
            if (array_key_exists($r->getId(), $dacs)) {
                $dac = $dacs[$r->getId()];
            } else {
                $dac = new Dac($r, $version);
            }
            $data[] = $dac;
        }

        return $data;
    }

    /********************************************************************
     * Génère et renvoie un form pour modifier les demandes ou attributions de ressources
     *
     * $version = La version associée aux Dac
     * $attribution = Si true les formulaires présentent l'attribution, sinon la demande (défaut)
     ********************************************************************************************/
    public function getRessourceForm(Version $version, bool $attribution = false): FormInterface
    {
        return $this->ff
                   ->createNamedBuilder('form_ressource', FormType::class, ['ressource' => $this->prepareRessources($version)])
                   ->add('ressource', CollectionType::class, [
                       'entry_type' => DacType::class,
                       'entry_options' => ['attribution' => $attribution],
                       'label' => true,
                   ])
                   ->getForm();
    }

    /*********************************
     *
     * Validation du formulaire des ressources
     *
     * params = Tableau de formulaires
     ***********************************************************************/
    public function validateRessourceForms(array &$ressource_forms): bool
    {
        $val = true;
        foreach ($ressource_forms as &$dac) {
            if ($dac->getDemande() < 0) {
                $val = false;
                $dac->setDemande(0);
                break;
            }
            if ($dac->getAttribution() < 0) {
                $val = false;
                $dac->setAttribution(0);
                break;
            }
        }

        return $val;
    }

    /*********************************************
     *
     * LES COLLABORATEURS
     *
     ********************************************/

    /**************************
     * préparation de la liste des collaborateurs
     *
     * params = $version
     *
     * return = Un tableau d'objets de type IndividuForm (cf Form/IndividuForm)
     *          Le responsable est dans la cellule 0 du tableau
     *
     *****************************************************************************/
    public function prepareCollaborateurs(Version $version): array
    {
        $sj = $this->sj;
        $sr = $this->sr;
        $su = $this->su;

        if (null == $version) {
            $sj->throwException('ServiceVersion:modifierCollaborateurs : version null');
        }

        $dataR = [];    // Le responsable est seul dans ce tableau
        $dataNR = [];    // Les autres collaborateurs
        foreach ($version->getCollaborateurVersion() as $cv) {
            $individu = $cv->getCollaborateur();
            if (null == $individu) {
                $sj->errorMessage('ServiceVersion:modifierCollaborateurs : collaborateur null pour CollaborateurVersion '.
                         $cv->getId());
                continue;
            } else {
                // $individuForm = new IndividuForm($individu, $this->resp_peut_modif_collabs);
                // $users = $cv->getUser();
                // dd($users);
                $individuForm = new IndividuForm($sr->getNoms(), $individu);
                // $logins = $individuForm->getLogins();
                $logins = [];
                foreach ($sr->getServeurs() as $s) {
                    $u = $su->getUser($cv->getCollaborateur(), $version->getProjet(), $s);
                    $k = $s->getNom();
                    $logins[$k] = $u->getLogin();
                }
                $individuForm->setLogins($logins);
                $individuForm->setResponsable($cv->getResponsable());
                $individuForm->setDeleted($cv->getDeleted());

                if (true == $individuForm->getResponsable()) {
                    $dataR[] = $individuForm;
                } else {
                    $dataNR[] = $individuForm;
                }
            }
        }

        // On merge les deux, afin de revoyer un seul tableau, le responsable en premier
        return array_merge($dataR, $dataNR);
    }

    /*********************************
     *
     * Validation du formulaire des collaborateurs - Retourne true/false
     *
     * params = $individu_forms: Retour de $sv->prepareCollaborateurs
     *          $definitif = Si false, on fait une validation minimale
     ***********************************************************************/
    public function validateIndividuForms(array $individu_forms, $definitif = false): bool
    {
        $resp_peut_modif_collabs = $this->resp_peut_modif_collabs;
        $one_login = false;

        // dd($individu_forms);
        foreach ($individu_forms as $individu_form) {
            // On ne teste pas la validité des collaborateurs supprimés !
            if ($individu_form->getDeleted()) {
                continue;
            }

            // teste s'il y a au moins un login
            if (false == $one_login) {
                $logins = $individu_form->getLogins();
                $one_login = in_array(true, $logins);
            }

            // nom, prénom vides sur un nouveau collaborateur !
            if (null != $individu_form->getMail()
                && (null == $individu_form->getPrenom() || null == $individu_form->getNom())) {
                // dd($individu_form);
                return false;
            }

            // Si le resp ne peut pas modifier les profils des collabs, on ne teste pas ça
            if (true == $definitif && true == $resp_peut_modif_collabs
                && (
                    null == $individu_form->getEtablissement()
                    || null == $individu_form->getLaboratoire()
                    || null == $individu_form->getStatut()
                )
            ) {
                return false;
            }
        }

        // Personne n'a de login !
        if (true == $definitif && false == $one_login) {
            return false;
        }

        if ([] != $individu_forms) {
            return true;
        } else {
            return false;
        }
    }

    /***************************************
     * Traitement des formulaires des individus individuellement
     *
     * $individu_forms = Tableau contenant un formulaire par individu
     *                   c-à-d un objet de type IndividuForm
     * $version        = La version considérée
     ****************************************************************/
    public function handleIndividuForms(array $individu_forms, Version $vers): void
    {
        $em = $this->em;
        $sj = $this->sj;
        $su = $this->su;
        $sval = $this->vl;

        // On fait la modification sur 1 ou 2 versions suivant les cas:
        //    - Version active
        //    - Dernière version
        $projet = $vers->getProjet();
        $projet->getVersionDerniere();
        $versions = [];
        if (null != $projet->getVersionActive()) {
            $versions[] = $projet->getVersionActive();
        }
        if (null != $projet->getVersionDerniere() && $projet->getVersionDerniere() != $projet->getVersionActive()) {
            $versions[] = $projet->getVersionDerniere();
        }

        foreach ($versions as $version) {
            // dd($version,$individu_forms);

            foreach ($individu_forms as $individu_form) {

                $id = $individu_form->getId();
                $srv_logins = $individu_form->getLogins();
                // dd($id, $individu_form);

                // Le formulaire correspond à un utilisateur existant
                if (null != $id) {
                    $individu = $em->getRepository(Individu::class)->find($id);
                }

                // On a renseigné le mail de l'utilisateur mais on n'a pas encore l'id: on recherche l'utilisateur !
                // Si $utilisateur == null, il faudra le créer (voir plus loin)
                elseif (null != $individu_form->getMail()) {
                    $individu = $em->getRepository(Individu::class)->findOneBy(['mail' => $individu_form->getMail()]);
                    if (null != $individu) {
                        $sj->debugMessage(__METHOD__.':'.__LINE__.' mail='.$individu_form->getMail().' => trouvé '.$individu);
                    } else {
                        $sj->debugMessage(__METHOD__.':'.__LINE__.' mail='.$individu_form->getMail().' => Individu à créer !');
                    }
                }

                // Pas de mail -> pas d'utilisateur !
                else {
                    $individu = null;
                }

                // Cas d'erreur qui ne devraient jamais se produire
                if (null == $individu && null != $id) {
                    $sj->errorMessage(__METHOD__.':'.__LINE__.' idIndividu '.$id.'du formulaire ne correspond pas à un utilisateur');
                } elseif (is_array($individu_form)) {
                    // TODO je ne vois pas le rapport
                    $sj->errorMessage(__METHOD__.':'.__LINE__.' individu_form est array '.Functions::show($individu_form));
                } elseif (is_array($individu)) {
                    // TODO pareil un peu nawak
                    $sj->errorMessage(__METHOD__.':'.__LINE__.' individu est array '.Functions::show($individu));
                } elseif (null != $individu && null != $individu_form->getMail() && $individu_form->getMail() != $individu->getMail()) {
                    $sj->errorMessage(__METHOD__.':'.__LINE__." l'adresse mail de l'utilisateur ".
                        $individu.' est incorrecte dans le formulaire :'.$individu_form->getMail().' != '.$individu->getMail());
                }

                // --------------> Maintenant des cas réalistes !
                // L'individu existe déjà
                elseif (null != $individu) {
                    // On modifie le profil de l'individu si on en a le droit
                    if ($this->resp_peut_modif_collabs) {
                        $individu = $individu_form->modifyIndividu($individu, $sj, true);
                        $em->persist($individu);
                    }


                    // Il devient collaborateur: création d'un collaborateurVersion et peut-être de plusieurs User
                    if (!$version->isCollaborateur($individu)) {
                        $sj->infoMessage(__METHOD__.':'.__LINE__.' individu '.
                            $individu.' ajouté à la version '.$version);
                        $collaborateurVersion = new CollaborateurVersion($individu);
                        $collaborateurVersion->setVersion($version);
                        $em->persist($collaborateurVersion);
                        $em->flush();

                        // Création des User si nécessaire
                        foreach ($em->getRepository(Serveur::class)->findAll() as $s) {
                            $u = $su->getUser($individu, $projet, $s);
                        }

                        // Synchronisation des flags de login
                        $this->modifierLogins($projet, $individu, $individu_form->getLogins());
                    }

                    // il était déjà collaborateur
                    else {
                        $sj->debugMessage(__METHOD__.':'.__LINE__.' individu '.
                               $individu.' confirmé pour la version '.$version);

                        // Modif éventuelle des cases de login
                        $this->modifierLogins($projet, $individu, $individu_form->getLogins());

                        // modification éventuelle du labo du projet
                        if ($version->isResponsable($individu)) {
                            $this->setLaboResponsable($version, $individu);
                            $em->persist($version);
                            $em->flush();
                        }

                        // Synchronisation des flags de login
                        $this->modifierLogins($projet, $individu, $individu_form->getLogins());
                    }
                    var_dump($individu_form);
                    if ($individu_form->getDeleted()) {
                        //do nothing for now
                    }
                    $em->flush(); // sans doute inutile

                }

                // Le formulaire correspond à un nouvel utilisateur (adresse mail pas trouvée dans la base)
                elseif (null != $individu_form->getMail() && false == $individu_form->getDeleted()) {
                    // Création d'un individu à partir du formulaire
                    // Renvoie null si la validation est négative
                    $individu = $individu_form->nouvelIndividu($sval);
                    if (null != $individu) {
                        $collaborateurVersion = new CollaborateurVersion($individu);
                        $collaborateurVersion->setVersion($version);

                        $sj->infoMessage(__METHOD__.':'.__LINE__.' nouvel utilisateur '.$individu.
                            ' créé et ajouté comme collaborateur à la version '.$version);

                        $em->persist($individu);
                        $em->persist($collaborateurVersion);
                        $em->persist($version);
                        $em->flush();

                        // Envoie une invitation à ce nouvel utilisateur
                        $connected = $this->tok->getToken()->getUser();
                        if (null != $connected) {
                            $this->sid->sendInvitation($connected, $individu);
                        }
                    }
                }
            } // foreach $individu_form
        } // foreach $versions
    }

    /*************************************************************
     * Génère et renvoie un form pour modifier un collaborateur
     *************************************************************/
    public function getCollaborateurForm(Version $version): FormInterface
    {
        $sj = $this->sj;
        $sr = $this->sr;
        $sval = $this->vl;

        $text_fields = true;
        if ($this->resp_peut_modif_collabs) {
            $text_fields = false;
        }

        // TODO - mettre dans un objet Form ?
        // ceci est "presque" un copié-collé de VersionController:modifierCollaborateursAction !

        $collaborateur_form = $this->ff
                                   ->createNamedBuilder('form_projet', FormType::class, [
                                       'individus' => $this->prepareCollaborateurs($version, $sj, $sval),
                                   ])
                                   ->add('individus', CollectionType::class, [
                                       'entry_type' => IndividuFormType::class,
                                       'label' => false,
                                       'allow_add' => true,
                                       'allow_delete' => true,
                                       'prototype' => true,
                                       'required' => true,
                                       'by_reference' => false,
                                       'delete_empty' => true,
                                       'attr' => ['class' => 'profil-horiz'],
                                       'entry_options' => ['text_fields' => $text_fields, 'srv_noms' => $sr->getNoms()],
                                   ])
                                   ->getForm();

        return $collaborateur_form;
    }

    /**
     * Validation du formulaire de version.
     *
     *    param = Version
     *
     *    return= Un array contenant la "todo liste", ie la liste de choses à faire pour que le formulaire soit validé
     *            Un array vide [] signifie: "Formulaire validé"
     *
     **/
    public function validateVersion(Version $version): array
    {
        $todo = [];
        if (null == $version->getPrjTitre()) {
            $todo[] = 'prj_titre';
        }
        // Il faut qu'au moins une ressource ait une demande non nulle
        $dacs = $version->getDac();
        $dem = false;
        foreach ($dacs as $d) {
            if (0 != $d->getDemande()) {
                $dem = true;
                break;
            }
        }
        if (false == $dem) {
            $todo[] = 'ressources';
        }

        if (null == $version->getPrjThematique()) {
            $todo[] = 'prj_id_thematique';
        }
        if (null == $version->getCodeNom()) {
            $todo[] = 'code_nom';
        }
        if (null == $version->getCodeLicence()) {
            $todo[] = 'code_licence';
        }

        // TODO - Automatiser cela avec le formulaire !
        if (Projet::PROJET_DYN == $version->getProjet()->getTypeProjet()) {
            if (null == $version->getPrjExpose()) {
                $todo[] = 'prj_expose';
            }

            // s'il s'agit d'un renouvellement
            if (count($version->getProjet()->getVersion()) > 1 && null == $version->getPrjJustifRenouv()) {
                $todo[] = 'prj_justif_renouv';
            }

            // Centres nationaux
            if (null == $version->getPrjGenciCentre()
                || null == $version->getPrjGenciMachines()
                || null == $version->getPrjGenciHeures()
                || null == $version->getPrjGenciDari()) {
                $todo[] = 'genci';
            }
        }

        if (Projet::PROJET_SESS == $version->getProjet()->getTypeProjet()) {
            if (null == $version->getPrjExpose()) {
                $todo[] = 'prj_expose';
            }

            // s'il s'agit d'un renouvellement
            if (count($version->getProjet()->getVersion()) > 1 && null == $version->getPrjJustifRenouv()) {
                $todo[] = 'prj_justif_renouv';
            }

            // Centres nationaux
            if (null == $version->getPrjGenciCentre()
                || null == $version->getPrjGenciMachines()
                || null == $version->getPrjGenciHeures()
                || null == $version->getPrjGenciDari()) {
                $todo[] = 'genci';
            }
        }

        // Validation des formulaires des collaborateurs
        if (!$this->validateIndividuForms($this->prepareCollaborateurs($version), true)) {
            $todo[] = 'collabs';
        }

        return $todo;
    }
}
