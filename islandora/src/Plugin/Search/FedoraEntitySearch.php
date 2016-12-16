<?php

namespace Drupal\islandora\Plugin\Search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Render\RendererInterface;
use Drupal\islandora\FedoraResourceInterface;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\Plugin\SearchIndexingInterface;
use Drupal\search\SearchQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for fedora_resource entities.
 *
 * Demonstrates integration with the search index using
 * \Drupal\search\SearchQuery.
 * \Drupal\search\SearchQuery adds some search
 * features such as automatically including {search_dataset} in the
 * query, weighting the the result scores based on the importance of individual
 * words, etc.
 * If these "extra features" aren't approprite for your custom content entity,
 * see \Drupal\user\Plugin\Search\UserSearch as an example
 * of not using \Drupal\search\SearchQuery.
 *
 * USAGE / INSTALLATION:
 *
 * # Place this plugin in the directory
 *    fedora_resource/Plugin/Search.
 * # Clear caches (drush cr).
 * # Add a "Content Entity Example Contacts" search page at
 *   /admin/config/search/pages and configure it as desired.
 * # Confirm that the index status at dmin/config/search/pages
 *   accurately reflects the number of
 *   content_entity_example_contact items (the number of "contact"
 *   entities that you have created, which should be listed at
 *   /content_entity_example_contact/list
 * # Run cron to add your entities to the search index.
 *
 * @see \Drupal\node\Plugin\Search\NodeSearch
 * @see \Drupal\user\Plugin\Search\UserSearch
 *
 * Annotation for discovery by Search module.
 * - 'id': unique machine name for this search plugin.
 *    Will be used for the `type` field in {search_index} and {search_dataset}.
 * - 'title': Translatable title for the search page & navigation tab.
 *
 * @SearchPlugin(
 *   id = "fedora_resource_search",
 *   title = @Translation("fedora resource content search")
 * )
 */
class FedoraResourceSearch extends ConfigurableSearchPluginBase implements AccessibleInterface, SearchIndexingInterface {

  /**
   * A database connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * An entity manager object.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * A module manager object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * A config object for 'search.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $searchSettings;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Drupal account to use for checking for access to advanced search.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The Renderer service to format the username and entity.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * An array of additional rankings from hook_ranking().
   *
   * @var array
   */
  protected $rankings;

  /**
   * The list of options and info for advanced search filters.
   *
   * Each entry in the array has the option as the key and for its value, an
   * array that determines how the value is matched in the database query. The
   * possible keys in that array are:
   * - column: (required) Name of the database column to match against.
   * - join: (optional) Information on a table to join. By default the data is
   *   matched against any tables joined in the $query declaration in
   *   findResults().
   * - operator: (optional) OR or AND, defaults to OR.
   *
   * For advanced search to work, probably also must modify:
   * - buildSearchUrlQuery() to build the redirect URL.
   * - searchFormAlter() to add fields to advanced search form.
   *
   * Note: In our case joins aren't needed because the {contact} table is
   * joined in findResults().
   *
   * @var array
   */
  protected $advanced = array(
    'name' => array(
      'column' => 'c.name',
    ),
  );

  /**
   * A constant for setting and checking the query string.
   */
  const ADVANCED_FORM = 'advanced-form';

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('search.settings'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * Constructs \Drupal\islandora\Plugin\Search\FedoraResourceSearch.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   An entity manager object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module manager object.
   * @param \Drupal\Core\Config\Config $search_settings
   *   A config object for 'search.settings'.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The $account object to use for checking for access to advanced search.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, Config $search_settings, LanguageManagerInterface $language_manager, RendererInterface $renderer, AccountInterface $account = NULL) {
    $this->database = $database;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->searchSettings = $search_settings;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
    $this->account = $account;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->addCacheTags(['fedora_resource_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowedIfHasPermission($account, 'access content');
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function isSearchExecutable() {
    // Contact search is executable if we have keywords or an advanced
    // parameter.
    // At least, we should parse out the parameters and see if there are any
    // keyword matches in that case, rather than just printing out the
    // "Please enter keywords" message.
    return !empty($this->keywords) || (isset($this->searchParameters['f']) && count($this->searchParameters['f']));
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if ($this->isSearchExecutable()) {
      $results = $this->findResults();

      if ($results) {
        return $this->prepareResults($results);
      }
    }

    return array();
  }

  /**
   * Queries to find search results, and sets status messages.
   *
   * This method can assume that $this->isSearchExecutable() has already been
   * checked and returned TRUE.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Results from search query execute() method, or NULL if the search
   *   failed.
   */
  protected function findResults() {

    $keys = $this->keywords;

    // Build matching conditions.
    $query = $this->database
      ->select('search_index', 'i', array('target' => 'replica'))
      ->extend('Drupal\search\SearchQuery')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    // Join on {contact} and {users_field_data} to get user's published status.
    // This join on {contact} also serves the advanced search items
    // (in $this->advanced) that are in {contact}.
    $query->join('contact', 'c', 'c.id = i.sid');
    $query->join('users_field_data', 'ufd', 'ufd.uid = c.user_id');
    $query->condition('ufd.status', 1);

    $query
      ->searchExpression($keys, $this->getPluginId());

    // @todo ===============
    // Handle advanced search filters in the f query string.
    // \Drupal::request()->query->get('f') is an array that looks like this in
    // the URL: ?f[]=first_name:Jane&f[]=gender:f
    // So $parameters['f'] looks like:
    // array('first_name:Jane', 'gender:f');
    // We need to parse this out into query conditions, some of which go into
    // the keywords string, and some of which are separate conditions.
    //
    // Note: advanced form fields are added in searchFormAlter(),
    // and the URL containing the "f" parameter is built in
    // buildSearchUrlQuery().
    $parameters = $this->getParameters();
    if (!empty($parameters['f']) && is_array($parameters['f'])) {
      // @todo This loop should probably be moved to a helper function.
      $filters = array();
      // Match any query value that is an expected option and a value
      // separated by ':' like 'first_name:Jane'.
      $pattern = '/^(' . implode('|', array_keys($this->advanced)) . '):([^ ]*)/i';
      foreach ($parameters['f'] as $item) {
        if (preg_match($pattern, $item, $m)) {
          // Use the matched value as the array key to eliminate duplicates.
          $filters[$m[1]][$m[2]] = $m[2];
        }
      }

      // Now turn these into query conditions. This assumes that everything in
      // $filters is a known type of advanced search as defined in
      // $this->advanced.
      foreach ($filters as $option => $matched) {
        $info = $this->advanced[$option];
        // Insert additional conditions. By default, all use the OR operator.
        $operator = empty($info['operator']) ? 'OR' : $info['operator'];
        $where = new Condition($operator);
        foreach ($matched as $value) {
          $where->condition($info['column'], $value);
        }
        $query->condition($where);
        if (!empty($info['join'])) {
          $query->join($info['join']['table'], $info['join']['alias'], $info['join']['condition']);
        }
      }
    }
    // ===============
    // Add the ranking expressions.
    $this->addFedoraResourceRankings($query);

    // Run the query.
    $find = $query
      // Add the language code of the indexed item to the result of the query,
      // since the entity will be rendered using the respective language.
      ->fields('i', array('langcode'))
      // And since SearchQuery makes these into GROUP BY queries, if we add
      // a field, for PostgreSQL we also need to make it an aggregate or a
      // GROUP BY. In this case, we want GROUP BY.
      ->groupBy('i.langcode')
      ->limit(10)
      ->execute();

    // Check query status and set messages if needed.
    $status = $query->getStatus();

    if ($status & SearchQuery::EXPRESSIONS_IGNORED) {
      drupal_set_message($this->t('Your search used too many AND/OR expressions. Only the first @count terms were included in this search.', array('@count' => $this->searchSettings->get('and_or_limit'))), 'warning');
    }

    if ($status & SearchQuery::LOWER_CASE_OR) {
      drupal_set_message($this->t('Search for either of the two terms with uppercase OR. For example, cats OR dogs.'), 'warning');
    }

    if ($status & SearchQuery::NO_POSITIVE_KEYWORDS) {
      drupal_set_message($this->formatPlural($this->searchSettings->get('index.minimum_word_size'), 'You must include at least one keyword to match in the content, and punctuation is ignored.', 'You must include at least one keyword to match in the content. Keywords must be at least @count characters, and punctuation is ignored.'), 'warning');
    }

    return $find;
  }

  /**
   * Prepares search results for rendering.
   *
   * @param \Drupal\Core\Database\StatementInterface $found
   *   Results found from a successful search query execute() method.
   *
   * @return array
   *   Array of search result item render arrays (empty array if no results).
   */
  protected function prepareResults(StatementInterface $found) {
    $results = array();

    // 'fedora_resource' comes from the entity type id declared
    // in the annotation for \Drupal\islandora\Entity\FedoraResource.
    // Replace this with your entity's type id.
    $entity_storage = $this->entityManager->getStorage('fedora_resource');
    $entity_render = $this->entityManager->getViewBuilder('fedora_resource');
    $keys = $this->keywords;

    foreach ($found as $item) {
      // Render the contact.
      /** @var \Drupal\content_entity_example\ContactInterface $entity */
      $entity = $entity_storage->load($item->sid)->getTranslation($item->langcode);
      $build = $entity_render->view($entity, 'search_result', $item->langcode);

      unset($build['#theme']);
      // Uncomment to use removeFromSnippet() for excluding data from snippet.
      /* $build['#pre_render'][] = array($this, 'removeFromSnippet'); */

      // Build the snippet.
      $rendered = $this->renderer->renderPlain($build);
      $this->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
      // Allow other modules to add to snippet.
      $rendered .= ' ' . $this->moduleHandler->invokeAll('fedora_resource_update_index', [$entity]);

      $extra = $this->moduleHandler->invokeAll('fedora_resource_search_result', [$entity]);

      $language = $this->languageManager->getLanguage($item->langcode);

      $result = array(
        'link' => $entity->url(
            'canonical',
            array(
              'absolute' => TRUE,
              'language' => $language,
            )
        ),
        'type' => 'Fedora Resource',
        'title' => $entity->label(),
        'contact' => $entity,
        'extra' => $extra,
        'score' => $item->calculated_score,
        'snippet' => search_excerpt($keys, $rendered, $item->langcode),
        'langcode' => $entity->language()->getId(),
      );

      $this->addCacheableDependency($entity);

      // We have to separately add the contact owner's cache tags because search
      // module doesn't use the rendering system, it does its own rendering
      // without taking cacheability metadata into account. So we have to do it
      // explicitly here.
      $this->addCacheableDependency($entity->getOwner());

      // @codingStandardsIgnoreStart
      // Uncomment this to display owner name and last changed time.
      // $username = array(
      //   '#theme' => 'username',
      //   '#account' => $entity->getOwner(),
      // );
      // $result += array(
      //   'user' => $this->renderer->renderPlain($username),
      //   'date' => $entity->getChangedTime(),
      // );
      // @codingStandardsIgnoreEnd
      $results[] = $result;

    }
    return $results;
  }

  /**
   * Removes results data from the build array.
   *
   * This information is being removed from the rendered entity that is used to
   * build the search result snippet.
   *
   * @param array $build
   *   The build array.
   *
   * @return array
   *   The modified build array.
   */
  public function removeFromSnippet(array $build) {
    // Code to remove arbitrary data from $build goes here.
    // Examples:
    // - unset($build['created']);
    // - unset($build['uid']);.
    return $build;
  }

  /**
   * Adds the configured rankings to the search query.
   *
   * @param SelectExtender $query
   *   A query object that has been extended with the Search DB Extender.
   */
  protected function addFedoraResourceRankings(SelectExtender $query) {
    if ($ranking = $this->getRankings()) {
      $tables = &$query->getTables();
      foreach ($ranking as $rank => $values) {
        if (isset($this->configuration['rankings'][$rank]) && !empty($this->configuration['rankings'][$rank])) {
          $entity_rank = $this->configuration['rankings'][$rank];
          // If the table defined in the ranking isn't already joined, add it.
          if (isset($values['join']) && !isset($tables[$values['join']['alias']])) {
            $query->addJoin($values['join']['type'], $values['join']['table'], $values['join']['alias'], $values['join']['on']);
          }
          $arguments = isset($values['arguments']) ? $values['arguments'] : array();
          $query->addScore($values['score'], $arguments, $entity_rank);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function updateIndex() {
    // Interpret the cron limit setting as the maximum number of entities to
    // index per cron run.
    $limit = (int) $this->searchSettings->get('index.cron_limit');

    $result = $this->database->queryRange("SELECT c.id, MAX(sd.reindex) FROM {fedora_resource} c LEFT JOIN {search_dataset} sd ON sd.sid = c.id AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0 GROUP BY c.id ORDER BY MAX(sd.reindex) is null DESC, MAX(sd.reindex) ASC, c.id ASC", 0, $limit, array(':type' => $this->getPluginId()), array('target' => 'replica'));

    $rids = $result->fetchCol();
    if (!$rids) {
      return;
    }

    // 'fedora_resource' comes from the entity type id declared
    // in the annotation for \Drupal\islandora\Entity\FedoraResource.
    // Replace this with your entity's type id.
    $entity_storage = $this->entityManager->getStorage('fedora_resource');

    foreach ($entity_storage->loadMultiple($rids) as $entity) {
      $this->indexFedoraResource($entity);
    }
  }

  /**
   * Indexes a single contact.
   *
   * @param \Drupal\islandora\FedoraResourceInterface $entity
   *   The contact to index.
   */
  protected function indexFedoraResource(FedoraResourceInterface $entity) {
    $languages = $entity->getTranslationLanguages();
    // 'content_entity_example_contact' comes from the entity type id declared
    // in the annotation for \Drupal\content_entity_example\Entity\Contact.
    // Replace this with your entity's type id.
    $entity_render = $this->entityManager->getViewBuilder('fedora_resource');

    foreach ($languages as $language) {
      $entity = $entity->getTranslation($language->getId());
      // Render the contact.
      $build = $entity_render->view($entity, 'search_index', $language->getId());

      unset($build['#theme']);

      // Add the title to text so it is searchable.
      $build['search_title'] = [
        '#prefix' => '',
        '#plain_text' => $entity->label(),
        '#suffix' => '',
        '#weight' => -1000,
      ];
      $text = $this->renderer->renderPlain($build);

      // Fetch extra data normally not visible.
      $extra = $this->moduleHandler->invokeAll('fedora_resource_update_index', [$entity]);
      foreach ($extra as $t) {
        $text .= $t;
      }

      // Update index, using search index "type" equal to the plugin ID.
      search_index($this->getPluginId(), $entity->id(), $language->getId(), $text);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexClear() {
    // All ContactSearch pages share a common search index "type" equal to
    // the plugin ID.
    search_index_clear($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function markForReindex() {
    // All ContactSearch pages share a common search index "type" equal to
    // the plugin ID.
    search_mark_for_reindex($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function indexStatus() {

    $total = $this->database->query('SELECT COUNT(*) FROM {fedora_resource}')->fetchField();
    $remaining = $this->database->query("SELECT COUNT(DISTINCT c.id) FROM {contact} c LEFT JOIN {search_dataset} sd ON sd.sid = c.id AND sd.type = :type WHERE sd.sid IS NULL OR sd.reindex <> 0", array(':type' => $this->getPluginId()))->fetchField();

    return array('remaining' => $remaining, 'total' => $total);
  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getParameters();
    $keys = $this->getKeywords();
    $used_advanced = !empty($parameters[self::ADVANCED_FORM]);
    if ($used_advanced) {
      $f = isset($parameters['f']) ? (array) $parameters['f'] : array();
      $defaults = $this->parseAdvancedDefaults($f, $keys);
    }
    else {
      $defaults = array('keys' => $keys);
    }

    $form['basic']['keys']['#default_value'] = $defaults['keys'];

    // Add advanced search keyword-related boxes.
    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced search'),
      '#attributes' => array('class' => array('search-advanced')),
      '#access' => $this->account && $this->account->hasPermission('use advanced search'),
      '#open' => $used_advanced,
    );
    $form['advanced']['keywords-fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => t('Keywords'),
    );

    $form['advanced']['keywords-fieldset']['keywords']['or'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing any of the words'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => isset($defaults['or']) ? $defaults['or'] : '',
    );

    $form['advanced']['keywords-fieldset']['keywords']['phrase'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing the phrase'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => isset($defaults['phrase']) ? $defaults['phrase'] : '',
    );

    $form['advanced']['keywords-fieldset']['keywords']['negative'] = array(
      '#type' => 'textfield',
      '#title' => t('Containing none of the words'),
      '#size' => 30,
      '#maxlength' => 255,
      '#default_value' => isset($defaults['negative']) ? $defaults['negative'] : '',
    );

    $form['advanced']['misc-fieldset'] = array(
      '#type' => 'fieldset',
    );

    // \Drupal\search\SearchQuery requires that there be valid keywords
    // submitted in the standard fields.
    $form['advanced']['misc-fieldset']['note'] = array(
      '#markup' => t('You must still enter keyword(s) above when using these fields.'),
      '#weight' => -10,
    );

    $form['advanced']['misc-fieldset']['name'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Search %field field for exact matches.', array('%field' => 'Name')),
      '#default_value' => isset($defaults['name']) ? $defaults['name'] : array(),
    );

    $form['advanced']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Advanced search'),
      '#prefix' => '',
      '#suffix' => '',
      '#weight' => 100,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {
    // Read keyword and advanced search information from the form values,
    // and put these into the GET parameters.
    $keys = trim($form_state->getValue('keys'));
    $advanced = FALSE;

    // Collect extra filters.
    $filters = array();

    // Advanced form, custom_content_entity_example_contact fields.
    if ($form_state->hasValue('name') && !empty(($value = trim($form_state->getValue('name'))))) {
      $filters[] = 'name:' . $value;
      $advanced = TRUE;
    }

    // Advanced form, keywords fields.
    if ($form_state->getValue('or') != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('or'), $matches)) {
        $keys .= ' ' . implode(' OR ', $matches[1]);
        $advanced = TRUE;
      }
    }
    if ($form_state->getValue('negative') != '') {
      if (preg_match_all('/ ("[^"]+"|[^" ]+)/i', ' ' . $form_state->getValue('negative'), $matches)) {
        $keys .= ' -' . implode(' -', $matches[1]);
        $advanced = TRUE;
      }
    }
    if ($form_state->getValue('phrase') != '') {
      $keys .= ' "' . str_replace('"', ' ', $form_state->getValue('phrase')) . '"';
      $advanced = TRUE;
    }
    $keys = trim($keys);

    // Put the keywords and advanced parameters into GET parameters. Make sure
    // to put keywords into the query even if it is empty, because the page
    // controller uses that to decide it's time to check for search results.
    $query = array('keys' => $keys);
    if ($filters) {
      $query['f'] = $filters;
    }
    // Record that the person used the advanced search form, if they did.
    if ($advanced) {
      $query[self::ADVANCED_FORM] = '1';
    }

    return $query;
  }

  /**
   * Parses the advanced search form default values.
   *
   * @param array $f
   *   The 'f' query parameter set up in self::buildUrlSearchQuery(), which
   *   contains the advanced query values.
   * @param string $keys
   *   The search keywords string, which contains some information from the
   *   advanced search form.
   *
   * @return array
   *   Array of default form values for the advanced search form, including
   *   a modified 'keys' element for the bare search keywords.
   */
  protected function parseAdvancedDefaults(array $f, $keys) {
    $defaults = array();

    // Split out the advanced search parameters.
    foreach ($f as $advanced) {
      list($key, $value) = explode(':', $advanced, 2);
      if (!isset($defaults[$key])) {
        $defaults[$key] = array();
      }
      $defaults[$key][] = $value;
    }

    // Split out the negative, phrase, and OR parts of keywords.
    // For phrases, the form only supports one phrase.
    $matches = array();
    $keys = ' ' . $keys . ' ';
    if (preg_match('/ "([^"]+)" /', $keys, $matches)) {
      $keys = str_replace($matches[0], ' ', $keys);
      $defaults['phrase'] = $matches[1];
    }

    // Negative keywords: pull all of them out.
    if (preg_match_all('/ -([^ ]+)/', $keys, $matches)) {
      $keys = str_replace($matches[0], ' ', $keys);
      $defaults['negative'] = implode(' ', $matches[1]);
    }

    // OR keywords: pull up to one set of them out of the query.
    if (preg_match('/ [^ ]+( OR [^ ]+)+ /', $keys, $matches)) {
      $keys = str_replace($matches[0], ' ', $keys);
      $words = explode(' OR ', trim($matches[0]));
      $defaults['or'] = implode(' ', $words);
    }

    // Put remaining keywords string back into keywords.
    $defaults['keys'] = trim($keys);

    return $defaults;
  }

  /**
   * Gathers ranking definitions from hook_ranking().
   *
   * @return array
   *   An array of ranking definitions.
   */
  protected function getRankings() {
    if (!$this->rankings) {
      $this->rankings = $this->moduleHandler->invokeAll('ranking');
    }
    return $this->rankings;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = array(
      'rankings' => array(),
    );
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Output form for defining rank factor weights.
    $form['content_ranking'] = array(
      '#type' => 'details',
      '#title' => t('Content ranking'),
      '#open' => TRUE,
    );
    $form['content_ranking']['info'] = array(
      '#markup' => '' . $this->t('Influence is a numeric multiplier used in ordering search results. A higher number means the corresponding factor has more influence on search results; zero means the factor is ignored. Changing these numbers does not require the search index to be rebuilt. Changes take effect immediately.') . '',
    );
    // Prepare table.
    $header = [$this->t('Factor'), $this->t('Influence')];
    $form['content_ranking']['rankings'] = array(
      '#type' => 'table',
      '#header' => $header,
    );

    // Note: reversed to reflect that higher number = higher ranking.
    $range = range(0, 10);
    $options = array_combine($range, $range);
    foreach ($this->getRankings() as $var => $values) {
      $form['content_ranking']['rankings'][$var]['name'] = array(
        '#markup' => $values['title'],
      );
      $form['content_ranking']['rankings'][$var]['value'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#attributes' => ['aria-label' => $this->t("Influence of '@title'", ['@title' => $values['title']])],
        '#default_value' => isset($this->configuration['rankings'][$var]) ? $this->configuration['rankings'][$var] : 0,
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->getRankings() as $var => $values) {
      if (!$form_state->isValueEmpty(['rankings', $var, 'value'])) {
        $this->configuration['rankings'][$var]
          = $form_state->getValue(['rankings', $var, 'value']);
      }
      else {
        unset($this->configuration['rankings'][$var]);
      }
    }
  }

}
