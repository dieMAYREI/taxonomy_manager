<?php

namespace Drupal\taxonomy_manager;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the taxonomy module.
 *
 * @see taxonomy.permissions.yml
 */
class TaxonomyManagerPermissions implements ContainerInjectionInterface {

    use StringTranslationTrait;

    /**
     * The entity manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityManager;

    /**
     * Constructs a TaxonomyPermissions instance.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
     *   The entity manager.
     */
    public function __construct(EntityTypeManagerInterface $entity_manager) {
        $this->entityManager = $entity_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static($container->get('entity_type.manager'));
    }

    /**
     * Get taxonomy permissions.
     *
     * @return array
     *   Permissions array.
     */
    public function permissions() {
        $permissions = [];
        foreach ($this->entityManager->getStorage('taxonomy_vocabulary')->loadMultiple() as $vocabulary) {
            $permissions += [
                'manage terms in ' . $vocabulary->id() => [
                    'title' => $this->t('Manage terms in %vocabulary', ['%vocabulary' => $vocabulary->label()]),
                ],
            ];
        }
        return $permissions;
    }

}
