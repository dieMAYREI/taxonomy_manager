<?php

namespace Drupal\taxonomy_manager\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\taxonomy_manager\Service\TaxonomyManagerService;
use Drupal\taxonomy_manager\Service\TaxonomyManagerMergeService;

/**
 * The start form of the taxonomy manager.
 * Build the searchfield, the buttons and the table with the values.
 *
 * Class TaxonomyManagerForm
 *
 * @package Drupal\taxonomy_manager\Form
 */
abstract class TaxonomyManagerAbstractForm extends FormBase
{

    /** @var array $tids */
    protected $tids;

    /**
     * @var TaxonomyManagerService
     */
    protected $service;

    /**
     * @var TaxonomyManagerMergeService
     */
    protected $merge_service;

    /**
     * AbstractTaxonomyManagerForm constructor.
     *
     * @param $service
     */
    public function __construct($service, $mergeService)
    {
        $this->service       = $service;
        $this->merge_service = $mergeService;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @return static
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public static function create(ContainerInterface $container)
    {
        $service      = $container->get('taxonomy_manager.manager_service');
        $mergeService = $container->get('taxonomy_manager.merge_service');

        return new static($service, $mergeService);
    }
}