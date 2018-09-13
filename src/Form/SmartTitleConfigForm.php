<?php

namespace Drupal\smart_title\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SmartTitleConfigForm.
 */
class SmartTitleConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundeInfo;

  /**
   * Constructs a new UpdateSettingsForm.
   *
   * @param Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param Drupal\Core\Entity\EntityTypeBundleInfo $entityTypeBundeInfo
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager, EntityTypeBundleInfo $entityTypeBundeInfo) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundeInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['smart_title.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smart_title_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('smart_title.settings');
    $entityTypeDefinitions = $this->entityTypeManager->getDefinitions();

    // Collecting content entity types which have canonical link template.
    $contentEntityTypeFilter = function (EntityTypeInterface $entityTypeDefinition) {
      return ($entityTypeDefinition instanceof ContentEntityTypeInterface)
        && $entityTypeDefinition->entityClassImplements(FieldableEntityInterface::class)
        && $entityTypeDefinition->get('field_ui_base_route');
    };
    $validContentEntityTypes = array_filter($entityTypeDefinitions, $contentEntityTypeFilter);
    $entityBundles = [];

    foreach (array_keys($validContentEntityTypes) as $entity_type_id) {
      $labelKey = $validContentEntityTypes[$entity_type_id]->getKey('label');

      if ($labelKey) {
        $baseFieldDefs = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);

        if (!$baseFieldDefs[$labelKey]->isDisplayConfigurable('view')) {
          $entityBundles[$entity_type_id]['label'] = $validContentEntityTypes[$entity_type_id]->getLabel();
          $entityBundles[$entity_type_id]['bundles'] = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
        }
      }
    }

    $defaults = $config->get('smart_title') ?: [];

    foreach ($entityBundles as $type => $definitions) {
      $options = [];
      $default = [];
      foreach ($definitions['bundles'] as $key => $info) {
        $options["$type:$key"] = $info['label'];
        if (in_array("$type:$key", $defaults)) {
          $default["$type:$key"] = "$type:$key";
        }
      }

      $form[$type . '_bundles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Smart Title for @entity-type', ['@entity-type' => $definitions['label']]),
        '#options' => $options,
        '#default_value' => $default,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {
    parent::submitForm($form, $formState);

    $values = $formState->getValues();
    $smartTitleBundlesSetting = $smartTitleBundles = [];

    foreach ($values as $key => $bundleValues) {
      if (strpos($key, '_bundles')) {
        foreach ($bundleValues as $bundleKey => $bundleValue) {
          if ($bundleValue) {
            $smartTitleBundlesSetting[] = $bundleKey;
          }
          $smartTitleBundles[] = $bundleKey;
        }
      }
    }

    // Updating entity view displays:
    // Remove smart title where it's not available anymore.
    $evdStorage = $this->entityTypeManager->getStorage('entity_view_display');
    $evds = $evdStorage->loadMultiple();
    $noSmartTitleCapableBundles = array_diff($smartTitleBundles, $smartTitleBundlesSetting);

    foreach ($evds as $evdId => $evd) {
      list($targetEntityTypeId, $targetBundle) = explode('.', $evdId);
      if (in_array("$targetEntityTypeId:$targetBundle", $noSmartTitleCapableBundles)) {
        $evd->unsetThirdPartySetting('smart_title', 'enabled')
          ->unsetThirdPartySetting('smart_title', 'settings')
          ->save();
      }
    }

    Cache::invalidateTags(['entity_field_info']);
    $this->config('smart_title.settings')->set('smart_title', $smartTitleBundlesSetting)->save();
  }

}
