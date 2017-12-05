<?php

namespace Drupal\extra_votingapi_widgets\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BaseRatingForm.
 *
 * @package Drupal\votingapi_widgets\Form
 *
 * @see \Drupal\votingapi_widgets\Form\BaseRatingForm
 *   The contrib implementation this logic is based on.
 * @see \Drupal\votingapi_widgets\Plugin\VotingApiWidgetBase
 *   The contrib implementation this logic is based on.
 */
class VotingApiSurveyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\votingapi_widgets\Form\BaseRatingForm::buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $vote = $this->getEntity();
    $options = $form_state->get('options');

    $form = parent::buildForm($form, $form_state);
    /* $result_function = $this->getResultFunction($form_state); */

    $form['#cache']['contexts'][] = 'user.permissions';
    $form['#cache']['contexts'][] = 'user.roles:authenticated';

    $form['value'] = [
      '#type' => 'radios',
      '#options' => $options,
      '#attributes' => [
        'autocomplete' => 'off',
        /* 'data-default-value' => ($this->getResults($result_function)) ? $this->getResults($result_function) : -1, */
        'data-style' => ($form_state->get('style')) ? $form_state->get('style') : 'default',
      ],
      /* '#default_value' => $this->getResults($result_function), */
      '#required' => TRUE,
    ];

    if ($form_state->get('read_only')) {
      $form['value']['#attributes']['disabled'] = 'disabled';
    }

    $form['submit'] = $form['actions']['submit'];
    $form['actions']['#access'] = FALSE;

    $form['submit'] += [
      '#type' => 'button',
      '#ajax' => [
        'callback' => $form_state->get('submit_ajax_callback'),
        'wrapper' => $form_state->get('submit_ajax_wrapper'),
        'progress' => [],
      ],
    ];

    return $form;
  }

  /**
   * Get result function.
   *
   * @see \Drupal\votingapi_widgets\Form\BaseRatingForm::getResultFunction()
   */
  protected function getResultFunction(FormStateInterface $form_state) {
    $entity = $this->getEntity();
    return ($form_state->get('resultfunction')) ? $form_state->get('resultfunction') : 'vote_field_average:' . $entity->getVotedEntityType() . '.' . $entity->field_name->value;
  }

}
