<?php

namespace Drupal\pokeapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\pokeapi\Service\PaginationService;
use Drupal\pokeapi\Service\PokemonService;
use \Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GeneralController extends ControllerBase
{

  private $pokemon_service;
  private $pagination_service;

  public function __construct(PokemonService $pokemon_service, PaginationService $pagination_service)
  {
    $this->pokemon_service = $pokemon_service;
    $this->pagination_service = $pagination_service;
  }

  public static function create(ContainerInterface $container)
  {
    return new static (
      $container->get('pokeapi.pokemon_service'),
      $container->get('pokeapi.pagination_service')
    );
  }

  /**
   * Affichage de la page principale
   * @return array
   */
  public function general(){

    $current_page = $this->pagination_service->getCurrentPage();
    $perpage = 10;
    $allpokemons = $this->pokemon_service->setPokemonsNodesinArray($this->pokemon_service->loadAllPokemons());
    $pokemon_table = [];

    $first = ($current_page - 1) * $perpage;
    $last = $first + ($perpage - 1);

    for ($i = $first; $i <= $last; $i++) {
      if ($allpokemons[$i] != NULL) {
        $pokemon_table[] = $allpokemons[$i];
      }
    }

    $form = \Drupal::formBuilder()->getForm('Drupal\pokeapi\Form\RecherchePokemonForm');

    return [
      '#theme' => 'pokemon_table',
      '#pokemons' => $pokemon_table,
      '#pagination' => $this->pagination_service->getPagination($perpage, $allpokemons, $current_page),
      '#form' => $form,
    ];

  }

  /**
   * Fonction appelée qui va lancer le batch de récupération et création des noeuds Pokemon
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   */
  public function getCreateDatas(){
    $allpokemons = $this->pokemon_service->getAllApiPokemons();
    $batch = [
      'title' => 'Récupération des données de l\'API et création des Pokémons',
      'operations' => [],
      'finished' => 'Drupal\pokeapi\Service\PokemonService::getCreatePokemonsApiDetailsFinished'
    ];

    foreach ($allpokemons as $pokemon) {
      if (!$this->pokemon_service->pokemonExists($pokemon->name)) {
        $url = $pokemon->url;
        $batch['operations'][] = ['Drupal\pokeapi\Service\PokemonService::getCreatePokemonApiDetails', [$url]];
      }
    }

    batch_set($batch);

    $url = Url::fromRoute('pokeapi.general');
    return batch_process($url);
  }

  /**
   * Supprimer la totalité des noeuds de type Pokemon
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function delete(){
    $pokemons = $this->pokemon_service->loadAllPokemons();
    foreach ($pokemons as $pokemon) {
      $pokemon->delete();
    }

    $this->messenger()->addWarning('Tous les Pokémons ont bien été relachés');
    return $this->redirect('pokeapi.general');
  }

  /**
   * Affiche les détails d'un Pokemon
   * @param $nid
   * @return array
   */
  public function details($nid){
    $pokemon = $this->pokemon_service->loadOnePokemon($nid);

    return [
      '#theme' => 'details_pokemon',
      '#pokemon' => $pokemon,
    ];
  }
}
