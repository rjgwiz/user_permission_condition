<?php

namespace Drupal\user_permission_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User Permission' condition.
 *
 * @Condition(
 *   id = "user_permission",
 *   label = @Translation("User Permission"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user", label = @Translation("User"))
 *   }
 * )
 */
class UserPermission extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a User Permission condition plugin.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The domain negotiator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('user.permissions'),
      $container->get('module_handler'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get list of permissions the same way
    // user/src/Plugin/views/access/Permission.php does.
    $permission_list = [];
    $permissions = $this->permissionHandler->getPermissions();
    foreach ($permissions as $perm => $perm_item) {
      $provider = $perm_item['provider'];
      $display_name = $this->moduleHandler->getName($provider);
      $permission_list[$display_name][$perm] = strip_tags($perm_item['title']);
    }

    $form['permission'] = [
      '#type' => 'select',
      '#options' => $permission_list,
      '#title' => $this->t('Permission'),
      '#default_value' => isset($this->options['permission']) ? isset($this->options['permission']) : '',
      '#description' => $this->t('Only users with the selected permission flag will be able to access this display.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'permission' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['permission'] = $form_state->getValue('permission');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $permission = $this->configuration['permission'];
    if (!empty($this->configuration['negate'])) {
      return $this->t('The user does not have the permission "@permission"', ['@permission' => $permission]);
    }
    else {
      return $this->t('The user has the permission "@permission"', ['@permission' => $permission]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['permission']) && !$this->isNegated()) {
      return TRUE;
    }
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getContextValue('user');
    return $user->hasPermission($this->configuration['permission']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Optimize cache context, if a user cache context is provided, only use
    // user.permissions, since that's the only part this condition cares about.
    $contexts = [];
    foreach (parent::getCacheContexts() as $context) {
      $contexts[] = $context == 'user' ? 'user.permissions' : $context;
    }
    return $contexts;
  }

}
