<?php

namespace Drupal\pokeapi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pokeapi\Service\PokemonService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RecherchePokemonForm extends FormBase{

  private $pokemon_service;

  public function __construct(PokemonService $pokemon_service){
    $this->pokemon_service = $pokemon_service;
  }

  public static function create(ContainerInterface $container){
    return new static (
      $container->get('pokeapi.pokemon_service')
    );
  }

  public function getFormId(){
    return 'recherche_pokemon';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
    $form['#attributes']['class'] = ['form-inline'];

    $form['nom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rechercher un Pokémon'),
      '#placeholder' => $this->t('Name'),
      '#attributes' => [
        'class' => ['form-group']
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => ['btn', 'btn-success'],
      ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state){
    $pokemon_service = $this->pokemon_service;
    $name = strtolower($form_state->getValue('nom'));

    $pokemon_nid = $pokemon_service->getNidByName('pokemon', $name);

    if ($pokemon_nid != NULL) {
      $url = Url::fromRoute('pokeapi.details', ['nid' => $pokemon_nid])->toString();
      $reponse = new RedirectResponse($url);
      return $reponse->send();
    }
    else {
      return $this->messenger()->addError($this->t('Le pokemon %s n\'existe pas', ['%s' => $name]));
    }
  }
}
