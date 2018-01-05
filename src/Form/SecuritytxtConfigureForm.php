<?php

namespace Drupal\securitytxt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 *
 *
 */
class SecuritytxtConfigureForm extends ConfigFormBase {

    /**
     * The path alias manager.
     *
     * @var \Drupal\Core\Path\AliasManagerInterface
     */
    protected $aliasManager;

    /**
     * The path validator.
     *
     * @var \Drupal\Core\Path\PathValidatorInterface
     */
    protected $pathValidator;

    /**
     * The request context.
     *
     * @var \Drupal\Core\Routing\RequestContext
     */
    protected $requestContext;

    /**
     * Constructs a SecuritytxtForm object.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The factory for configuration objects.
     * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
     *   The path alias manager.
     * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
     *   The path validator.
     * @param \Drupal\Core\Routing\RequestContext $request_context
     *   The request context.
     */
    public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
        parent::__construct($config_factory);

        $this->aliasManager = $alias_manager;
        $this->pathValidator = $path_validator;
        $this->requestContext = $request_context;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('config.factory'),
            $container->get('path.alias_manager'),
            $container->get('path.validator'),
            $container->get('router.request_context')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'securitytxt_configure';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['securitytxt.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $settings_config = $this->config('securitytxt.settings');

        $form['enabled'] = [
            '#type' => 'checkbox',
            '#title' => t('Enable the security.txt file for your site'),
            '#default_value' => $settings_config->get('enabled'),
            '#description' => t('When enabled the security.txt file will be accessible to all users with the "view securitytxt" permission, you will almost certinaly want to give this permission to everyone i.e. authenticated and anonymous users.'),
        ];

        $form['contact'] = [
            '#type' => 'details',
            '#title' => t('Contact'),
            '#open' => TRUE,
            '#description' => t('You must provide at least one means of contact: email, phone or contact page URL.'),
        ];
        $form['contact']['contact_email'] = [
            '#type' => 'email',
            '#title' => t('Email'),
            '#default_value' => $settings_config->get('contact_email'),
            '#description' => t('Typically this would be of the form <kbd>security@example.com</kbd>. Leave it blank if you do not want to provide an email address.'),
        ];
        $form['contact']['contact_phone'] = [
            '#type' => 'tel',
            '#title' => t('Phone'),
            '#default_value' => $settings_config->get('contact_phone'),
            '#description' => t('Use full international format e.g. <kbd>+1-201-555-0123</kbd>. Leave it blank if you do not want to provide a phone number.'),
        ];
        $form['contact']['contact_url'] = [
            '#type' => 'url',
            '#title' => t('URL'),
            '#default_value' => $settings_config->get('contact_url'),
            '#description' => t('The URL of a contact page which should be loaded over HTTPS. Leave it blank if you do not want to provide a contact page.'),
        ];

        $form['encryption'] = [
            '#type' => 'details',
            '#title' => t('Encryption'),
            '#open' => TRUE,
            '#description' => t('Allow people to send you encrypted messages by providing your public key.'),
        ];
        $form['encryption']['encryption_key_url'] = [
            '#type' => 'url',
            '#title' => t('Public key URL'),
            '#default_value' => $settings_config->get('encryption_key_url'),
            '#description' => t('The URL of page which contains your public key. The page should be loaded over HTTPS.'),
        ];

        $form['policy'] = [
            '#type' => 'details',
            '#title' => t('Policy'),
            '#open' => TRUE,
            '#description' => t('A security and/or disclosure policy can help security researchers understand what you are looking for and how to report security vulnerabilities.'),
        ];
        $form['policy']['policy_url'] = [
            '#type' => 'url',
            '#title' => t('Security policy URL'),
            '#default_value' => $settings_config->get('policy_url'),
            '#description' => t('The URL of a page which provides details of your security and/or disclosure policy. Leave it blank if you do not have such a page.'),
        ];

        $form['acknowledgement'] = [
            '#type' => 'details',
            '#title' => t('Acknowledgement'),
            '#open' => TRUE,
            '#description' => t('A security acknowldgements page should list the individuals or companies that have disclosed security vulnerabilities and worked with you to fix them.'),
        ];
        $form['acknowledgement']['acknowledgement_url'] = [
            '#type' => 'url',
            '#title' => t('Acknowledgements page URL'),
            '#default_value' => $settings_config->get('acknowledgement_url'),
            '#description' => t('The URL of your security acknowledgements page. Leave it blank if you do not have such a page.'),
        ];


        return parent::buildForm($form, $form_state);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        $enabled = $form_state->getValue('enabled');
        $contact_email = $form_state->getValue('contact_email');
        $contact_phone = $form_state->getValue('contact_phone');
        $contact_url = $form_state->getValue('contact_url');

        /* When enabled, check that at least one contact field is specified. */
        if ($enabled && $contact_email == '' && $contact_phone == '' && $contact_url == '') {
            $form_state->setErrorByName('contact', $this->t('You must specify at least one method of contact.'));
        }        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        /* Warn if contact or encryption URLs are not loaded over HTTPS */
        $contact_url = $form_state->getValue('contact_url');
        if ($contact_url != '' && substr($contact_url, 0, 8) !== 'https://') {
            drupal_set_message('Your contact URL should really be loaded over HTTPS.', 'warning');
        }

        $encryption_key_url = $form_state->getValue('encryption_key_url');
        if ($encryption_key_url != '' && substr($encryption_key_url, 0, 8) !== 'https://') {
            drupal_set_message('Your public key URL should really be loaded over HTTPS.', 'warning');
        }

        /* Save the configuration */
        $this->config('securitytxt.settings')
            ->set('enabled', $form_state->getValue('enabled'))
            ->set('contact_email', $form_state->getValue('contact_email'))
            ->set('contact_phone', $form_state->getValue('contact_phone'))
            ->set('contact_url', $form_state->getValue('contact_url'))
            ->set('encryption_key_url', $form_state->getValue('encryption_key_url'))
            ->set('policy_url', $form_state->getValue('policy_url'))
            ->set('acknowledgement_url', $form_state->getValue('acknowledgement_url'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}