<?php

namespace Drupal\pokeapi\Service;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

class PokemonService
{

  /**
   * Récupére tous les Pokémons de premiére génération et l'url de chaque Pokemon
   * @return mixed
   */
  public function getAllApiPokemons(){
    $url = 'https://pokeapi.co/api/v2/pokemon?offset=0&limit=151';
    $curl = curl_init();
    $opts = [
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CONNECTTIMEOUT => 30
    ];

    curl_setopt_array($curl, $opts);

    $result = curl_exec($curl);
    return json_decode($result)->results;
  }

  /**
   * Récupére les détails d'un Pokémon via l'API et créé le noeud associé
   * @param $url
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getCreatePokemonApiDetails($url){
    $curl = curl_init();
    $opts = [
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CONNECTTIMEOUT => 30
    ];

    curl_setopt_array($curl, $opts);

    $result = json_decode(curl_exec($curl));

    if (!empty($result) || $result != NULL) {

      foreach ($result->types as $type) {
        $types[] = ['value' => $type->type->name];
      }


      $node = Node::create([
        'type' => 'pokemon',
        'title' => (string)$result->name,
        'field_id' => (string)$result->id,
        'field_nom' => (string)$result->name,
        'field_types' => $types,
        'field_taille' => (string)$result->height,
        'field_poids' => (string)$result->weight,
        'field_visuel' => (string)$result->sprites->front_default,
      ]);

      $node->save();
    }

  }

  public function getCreatePokemonsApiDetailsFinished($success, $results, $operations){
    if ($success) {
      \Drupal::messenger()->addMessage(t('Tous les Pokémons ont été capturés !'));
    } else {
      \Drupal::messenger()->addError(t('Le processus s\'est terminé avec une erreur'));
    }

    return Url::fromRoute('pokeapi.general');
  }


  public function pokemonExists($name){
    $nid = $this->getNidByName('pokemon', $name);
    $node = Node::load($nid);

    if ($node != NULL) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  public function loadAllPokemons(){
    $nids = \Drupal::entityQuery('node')->condition('type', 'pokemon')->execute();
    $nodes = Node::loadMultiple($nids);

    return $nodes;
  }

  public function loadOnePokemon($nid){
    $node = Node::load($nid);
    $types = $node->get('field_types')->getValue();

    $pokemon = [
      'nid' => $node->id(),
      'pokedex' => $node->get('field_id')->value,
      'nom' => $node->get('field_nom')->value,
      'types' => $this->setTypesInline($types),
      'taille' => $node->get('field_taille')->value / 10,
      'poids' => $node->get('field_poids')->value / 10,
      'visuel' => $node->get('field_visuel')->value,
    ];

    return $pokemon;
  }

  public function setPokemonsNodesinArray($nodes){
    $pokemons = [];

    foreach ($nodes as $node) {
      $types = $node->get('field_types')->getValue();

      $pokemons[] = [
        'nid' => $node->id(),
        'pokedex' => $node->get('field_id')->value,
        'nom' => $node->get('field_nom')->value,
        'types' => $this->setTypesInline($types),
        'taille' => $node->get('field_taille')->value / 10,
        'poids' => $node->get('field_poids')->value / 10,
        'visuel' => $node->get('field_visuel')->value,
      ];
    }

    return $pokemons;
  }

  public function setTypesInline($types){
    foreach ($types as $type) {
      $inline_types[] = $type['value'];
    }

    $inline_types = implode(', ', $inline_types);
    return $inline_types;
  }

  public function getNidByName($type, $name){
    $query = \Drupal::entityQuery('node')
      ->condition('field_nom', $name)
      ->condition('type', $type)
      ->execute();

    $nid = array_keys($query)[0];
    return $nid;
  }

}
