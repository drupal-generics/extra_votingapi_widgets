<?php

namespace Drupal\extra_votingapi_widgets\Plugin\Block;

use Drupal\apsys_color_scheme\Service\SectionBackgroundColorsConfigInterface;
use Drupal\google_charts\Block\GoogleChartBlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'VotingapiSummaryPieChartBlock' block.
 *
 * @Block(
 *  id = "extra_votingapi_widgets_summary_pie_chart_block",
 *  admin_label = @Translation("Votingapi summary pie chart block"),
 * )
 */
class VotingapiSummaryPieChartBlock extends GoogleChartBlockBase implements ContainerFactoryPluginInterface {

  /**
   * The section background colors config.
   *
   * @var \Drupal\apsys_color_scheme\Service\SectionBackgroundColorsConfigInterface
   */
  protected $colorsConfig;

  /**
   * VotingapiSummaryPieChartBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\apsys_color_scheme\Service\SectionBackgroundColorsConfigInterface $colors_config
   *   The section background colors config.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SectionBackgroundColorsConfigInterface $colors_config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->colorsConfig = $colors_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('apsys_color_scheme.section_background_colors_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    $data = [];

    if (isset($this->configuration['results']['field_value_count'])) {
      foreach ($this->configuration['results']['field_value_count'] as $result) {
        $data[$result['label']] = $result['count'];
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $options = [
      'legend' => [
        'position' => 'top',
        'alignment' => 'end',
        'textStyle' => [
          'color' => '#000000',
          'fontName' => 'Arial',
          'bold' => FALSE,
          'maxLines' => 3,
        ],
      ],
      'width' => 210,
      'height' => 260,
      'chartArea' => [
        'top' => 60,
        'left' => 10,
        'width' => 210,
        'backgroundColor' => 'transparent',
      ],
      'pieHole' => 0.8,
      'pieSliceText' => 'percentage',
      'pieSliceTextStyle' => [
        'color' => '#000000',
      ],
      'pieStartAngle' => 120,
    ];

    $data_count = count($this->getData());
    $data_deltas = range(0, $data_count - 1);

    foreach ($data_deltas as $delta) {
      $options['slices'][$delta] = [
        'offset' => 0.1,
      ];
    }

    $colors = $this->getColors($data_count);
    if ($colors) {
      foreach ($data_deltas as $delta) {
        $options['slices'][$delta]['color'] = $colors[$delta];
      }
    }

    return $options + parent::getOptions();
  }

  /**
   * Get the colors array with the specified number of items.
   *
   * Returns the specified number of colors, even if there are more/less colors
   * available.
   *
   * @param int $count
   *   The number of colors to return.
   *
   * @return array
   *   Array of hex color codes, with $count number of items, or an empty array
   *   if there are no colors configured at all.
   */
  protected function getColors(int $count) {
    $colors = $this->colorsConfig->getColors();

    if ($colors && is_array($colors)) {
      $colors_count = count($colors);

      // If there are more colors than requested, then return the first $count
      // number of colors.
      if ($colors_count > $count) {
        $colors = array_slice($colors, 0, $count);
        return $colors;
      }
      // If there are less colors than requested, then repeat the colors until
      // $count number of items reached and return.
      elseif ($colors_count < $count) {
        $colors_copy = [];

        $n = intdiv($count, $colors_count);
        for ($i = 0; $i < $n; $i++) {
          $colors_copy = array_merge($colors_copy, $colors);
        }

        $n = $count % $colors_count;
        if ($n) {
          $colors_copy = array_merge($colors_copy, array_slice($colors, 0, $n));
        }

        return $colors_copy;
      }

      // Just return when we have exactly as many colors as requested.
      return $colors;
    }

    return [];
  }

}
