<?php
/**
 * Dedicated Server Module
 *
 * @package blesta
 * @subpackage blesta.components.modules.Dsm
 * @copyright Copyright (c) 2015, EidolonHost
 * @license http://eidolonhost.com/license/ EidolonHost License Agreement
 * @link http://eidolonhost.com/ EidolonHost
 */
class Dsm extends Module {

	/**
	 * @var string The version of this module
	 */
	private static $version = "0.0.2";
	/**
	 * @var string The authors of this module
	 */
	private static $authors = array(array('name'=>"EidolonHost",'url'=>"http://eidolonhost.com"));

	/**
	 * Initializes the module
	 */
	public function __construct() {
		// Load components required by this module
		Loader::loadComponents($this, array("Input"));

		// Load the language required by this module
		Language::loadLang("dsm", null, dirname(__FILE__) . DS . "language" . DS);

		// Load config
		Configure::load("dsm", dirname(__FILE__) . DS . "config" . DS);
	}

	/**
	 * Performs any necessary bootstraping actions. Sets Input errors on
	 * failure, preventing the module from being added.
	 *
	 * @return array A numerically indexed array of meta data containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function install() {
		$errors = array();
		// Ensure the the system meets the requirements for this module
		if (!extension_loaded("simplexml"))
			$errors['simplexml'] = array('required' => Language::_("Dsm.!error.simplexml_required", true));

		if (!empty($errors)) {
			$this->Input->setErrors($errors);
			return;
		}
	}

	/**
	 * Returns the name of this module
	 *
	 * @return string The common name of this module
	 */
	public function getName() {
		return Language::_("Dsm.name", true);
	}

	/**
	 * Returns the version of this module
	 *
	 * @return string The current version of this module
	 */
	public function getVersion() {
		return self::$version;
	}

	/**
	 * Returns the name and URL for the authors of this module
	 *
	 * @return array A numerically indexed array that contains an array with key/value pairs for 'name' and 'url', representing the name and URL of the authors of this module
	 */
	public function getAuthors() {
		return self::$authors;
	}

	/**
	 * Returns the value used to identify a particular service
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @return string A value used to identify this service amongst other similar services
	 */
	public function getServiceName($service) {
		foreach ($service->fields as $field) {
			if ($field->key == "Dsm_hostname")
				return $field->value;
		}
		return null;
	}

	/**
	 * Returns a noun used to refer to a module row (e.g. "Server", "VPS", "Reseller Account", etc.)
	 *
	 * @return string The noun used to refer to a module row
	 */
	public function moduleRowName() {
		return Language::_("Dsm.module_row", true);
	}

	/**
	 * Returns a noun used to refer to a module row in plural form (e.g. "Servers", "VPSs", "Reseller Accounts", etc.)
	 *
	 * @return string The noun used to refer to a module row in plural form
	 */
	public function moduleRowNamePlural() {
		return Language::_("Dsm.module_row_plural", true);
	}

	/**
	 * Returns a noun used to refer to a module group (e.g. "Server Group", "Cloud", etc.)
	 *
	 * @return string The noun used to refer to a module group
	 */
	public function moduleGroupName() {
		return Language::_("Dsm.module_group", true);
	}

	/**
	 * Returns the key used to identify the primary field from the set of module row meta fields.
	 * This value can be any of the module row meta fields.
	 *
	 * @return string The key used to identify the primary field from the set of module row meta fields
	 */
	public function moduleRowMetaKey() {
		return "server_name";
	}

	/**
	 * Returns the value used to identify a particular package service which has
	 * not yet been made into a service. This may be used to uniquely identify
	 * an uncreated service of the same package (i.e. in an order form checkout)
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @return string The value used to identify this package service
	 * @see Module::getServiceName()
	 */
	public function getPackageServiceName($packages, array $vars=null) {
		if (isset($vars['Dsm_hostname']))
			return $vars['Dsm_hostname'];
		return null;
	}

	/**
	 * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @return boolean True if the service validates, false otherwise. Sets Input errors when false.
	 */
	public function validateService($package, array $vars=null, $edit=false) {
		// Set rules
		$rules = array(
			'Dsm_hostname' => array(
				'format' => array(
					'rule' => array(array($this, "validateHostName")),
					'message' => Language::_("Dsm.!error.Dsm_hostname.format", true)
				)
			),
			'Dsm_vserver_id' => array(
				'format' => array(
					'if_set' => true,
					'rule' => array("matches", "/^[0-9]+$/"),
					'message' => Language::_("Dsm.!error.Dsm_vserver_id.format", true)
				)
			)
		);

		// Template must be given if it can be set by the client
		if (isset($package->meta->set_template) && $package->meta->set_template == "client" &&
			isset($package->meta->type)) {

			$rules['Dsm_template'] = array(
				'valid' => array(
					'rule' => array(array($this, "validateTemplate"), $package->meta->type, $package->module_row, $package->module_group),
					'message' => Language::_("Dsm.!error.Dsm_template.valid", true)
				)
			);
		}

		// Virtual Server ID is not required on add
		if (empty($vars['Dsm_vserver_id']) && !$edit)
			unset($rules['Dsm_vserver_id']);

		// Set fields to optional
		if ($edit) {
			$rules['Dsm_hostname']['format']['if_set'] = true;
			if (isset($rules['Dsm_template']))
				$rules['Dsm_template']['valid']['if_set'] = true;
		}

		$this->Input->setRules($rules);
		return $this->Input->validates($vars);
	}

	/**
	 * Adds the service to the remote server. Sets Input errors on failure,
	 * preventing the service from being added.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being added (if the current service is an addon service and parent service has already been provisioned)
	 * @param string $status The status of the service being added. These include:
	 * 	- active
	 * 	- canceled
	 * 	- pending
	 * 	- suspended
	 * @return array A numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addService($package, array $vars=null, $parent_package=null, $parent_service=null, $status="pending") {
		// Load the API
		$row = $this->getModuleRow();
		$api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

		// Get the fields for the service
		$params = $this->getFieldsFromInput($vars, $package);

		// Validate the service-specific fields
		$this->validateService($package, $vars);

		if ($this->Input->errors())
			return;

		// Only provision the service if 'use_module' is true
		if ($vars['use_module'] == "true") {
			$client_id = (isset($vars['client_id']) ? $vars['client_id'] : "");

			// Create a new client (if one does not already exist)
			$client = $this->createClient($client_id, $params['username'], $row);

			if ($this->Input->errors())
				return;

			// Attempt to create the virtual server
			$api->loadCommand("Dsm_vserver");
			try {
				// Load up the Virtual Server API
				$vserver_api = new DsmVserver($api);
				$masked_params = $params;
				$masked_params['password'] = "***";

				// Create the Virtual Server
				$this->log($row->meta->host . "|vserver-create", serialize($masked_params), "input", true);
				$response = $this->parseResponse($vserver_api->create($params), $row);
			}
			catch (Exception $e) {
				// Internal Error
				$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
			}

			if ($this->Input->errors())
				return;
		}

		// Return service fields
		return array(
			array(
				'key' => "Dsm_vserver_id",
				'value' => (isset($response->vserverid) ? $response->vserverid : (!empty($vars['Dsm_vserver_id']) ? $vars['Dsm_vserver_id'] : null)),
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_main_ip_address",
				'value' => isset($response->mainipaddress) ? $response->mainipaddress : null,
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_extra_ip_addresses",
				'value' => isset($response->extraipaddress) ? $response->extraipaddress : null,
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_console_user",
				'value' => isset($response->consoleuser) ? $response->consoleuser : null,
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_console_password",
				'value' => isset($response->consolepassword) ? $response->consolepassword : null,
				'encrypted' => 1
			),
			array(
				'key' => "Dsm_virt_id",
				'value' => isset($response->virtid) ? $response->virtid : null,
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_internal_ip",
				'value' => isset($response->internalip) ? $response->internalip : null,
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_vnc_ip",
				'value' => isset($response->vncip) ? $response->vncip : null,
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_vnc_port",
				'value' => isset($response->vncport) ? $response->vncport : null,
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_vnc_password",
				'value' => isset($response->vncpassword) ? $response->vncpassword : null,
				'encrypted' => 1
			),
			array(
				'key' => "Dsm_root_password",
				'value' => isset($response->rootpassword) ? $response->rootpassword : $params['password'],
				'encrypted' => 1
			),
			array(
				'key' => "Dsm_hostname",
				'value' => $params['hostname'],
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_type",
				'value' => $params['type'],
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_username",
				'value' => $params['username'],
				'encrypted' => 0
			),
			array(
				'key' => "Dsm_password",
				'value' => (isset($client['password']) ? $client['password'] : null),
				'encrypted' => 1
			)
		);
	}

	/**
	 * Edits the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being edited.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being edited (if the current service is an addon service)
	 * @return array A numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editService($package, $service, array $vars=array(), $parent_package=null, $parent_service=null) {
		// Load the API
		$row = $this->getModuleRow();
		$api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

		// Validate the service-specific fields
		$this->validateService($package, $vars, true);

		if ($this->Input->errors())
			return;

		// Get the service fields
		$service_fields = $this->serviceFieldsToObject($service->fields);

		// Check for fields that changed
		$delta = array();
		foreach ($vars as $key => $value) {
			if (!array_key_exists($key, $service_fields) || $vars[$key] != $service_fields->$key)
				$delta[$key] = $value;
		}

		// Only provision the service if 'use_module' is true
		if ($vars['use_module'] == "true") {
			// Reinstall template (if changed)
			if (isset($delta['Dsm_template'])) {
				$data = array('template' => $delta['Dsm_template']);
				if (!$this->performAction("rebuild", $service_fields->Dsm_vserver_id, $row, $data))
					$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
				else
					$service_fields->Dsm_template = $delta['Dsm_template'];
			}

			// Update hostname (if changed)
			if (isset($delta['Dsm_hostname'])) {
				$data = array('hostname' => $delta['Dsm_hostname']);
				if (!$this->performAction("hostname", $service_fields->Dsm_vserver_id, $row, $data))
					$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
				else
					$service_fields->Dsm_hostname = $delta['Dsm_hostname'];
			}

			// Update root password (if changed)
			if (isset($delta['Dsm_root_password'])) {
				$data = array('rootpassword' => $delta['Dsm_root_password']);
				if (!$this->performAction("rootpassword", $service_fields->Dsm_vserver_id, $row, $data))
					$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
				else
					$service_fields->Dsm_root_password = $delta['Dsm_root_password'];
			}
		}

		// Set virtual server ID if changed
		if (array_key_exists("Dsm_vserver_id", $delta))
			$service_fields->Dsm_vserver_id = $delta['Dsm_vserver_id'];

		// Return all the service fields
		$fields = array();
		$encrypted_fields = array("Dsm_console_password", "Dsm_vnc_password", "Dsm_root_password", "Dsm_password");
		foreach ($service_fields as $key => $value)
			$fields[] = array('key' => $key, 'value' => $value, 'encrypted' => (in_array($key, $encrypted_fields) ? 1 : 0));

		return $fields;
	}

	/**
	 * Cancels the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being canceled.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being canceled (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function cancelService($package, $service, $parent_package=null, $parent_service=null) {

		if (($row = $this->getModuleRow())) {
			$api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);
			$api->loadCommand("Dsm_vserver");

			$service_fields = $this->serviceFieldsToObject($service->fields);

			// Attempt to terminate the virtual server
			try {
				// Load up the Virtual Server API
				$vserver_api = new DsmVserver($api);
				$params = array('vserverid' => $service_fields->Dsm_vserver_id, 'deleteclient' => "false");

				// Terminate the Virtual Server
				$this->log($row->meta->host . "|vserver-terminate", serialize($params), "input", true);
				$response = $this->parseResponse($vserver_api->terminate($params), $row);
			}
			catch (Exception $e) {
				// Internal Error
				$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
				return;
			}
		}
		return null;
	}

	/**
	 * Suspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being suspended.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being suspended (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function suspendService($package, $service, $parent_package=null, $parent_service=null) {
		// Suspend the service by shutting the server down
		$response = null;

		if (($row = $this->getModuleRow())) {
			$api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

			// Get the service fields
			$service_fields = $this->serviceFieldsToObject($service->fields);

			// Load the virtual server API
			$api->loadCommand("Dsm_vserver");

			try {
				$server_api = new DsmVserver($api);
				$params = array('vserverid' => $service_fields->Dsm_vserver_id);

				$this->log($row->meta->host . "|vserver-suspend", serialize($params), "input", true);
				$response = $this->parseResponse($server_api->suspend($params), $row);
			}
			catch (Exception $e) {
				// Nothing to do
				return;
			}
		}

		return null;
	}

	/**
	 * Unsuspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being unsuspended.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being unsuspended (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function unsuspendService($package, $service, $parent_package=null, $parent_service=null) {
		// Unsuspend the service by booting it up and releasing the suspension lock
		$response = null;

		if (($row = $this->getModuleRow())) {
			$api = $this->getApi($row->meta->user_id, $row->meta->key, $row->meta->host, $row->meta->port);

			// Get the service fields
			$service_fields = $this->serviceFieldsToObject($service->fields);

			// Load the virtual server API
			$api->loadCommand("Dsm_vserver");

			try {
				$server_api = new DsmVserver($api);
				$params = array('vserverid' => $service_fields->Dsm_vserver_id);

				$this->log($row->meta->host . "|vserver-unsuspend", serialize($params), "input", true);
				$response = $this->parseResponse($server_api->unsuspend($params), $row);
			}
			catch (Exception $e) {
				// Nothing to do
				return;
			}
		}

		return null;
	}

	/**
	 * Allows the module to perform an action when the service is ready to renew.
	 * Sets Input errors on failure, preventing the service from renewing.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being renewed (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function renewService($package, $service, $parent_package=null, $parent_service=null) {
		// Nothing to do
		return null;
	}

	/**
	 * Updates the package for the service on the remote server. Sets Input
	 * errors on failure, preventing the service's package from being changed.
	 *
	 * @param stdClass $package_from A stdClass object representing the current package
	 * @param stdClass $package_to A stdClass object representing the new package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being changed (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function changeServicePackage($package_from, $package_to, $service, $parent_package=null, $parent_service=null) {
		// Nothing to do
		return null;
	}

	/**
	 * Validates input data when attempting to add a package, returns the meta
	 * data to save when adding a package. Performs any action required to add
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being added.
	 *
	 * @param array An array of key/value pairs used to add the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addPackage(array $vars=null) {
		// Allow only nodes or a node group to be set

		if (isset($vars['meta']['set_node'])) {
			if ($vars['meta']['set_node'] == "0")
				unset($vars['meta']['nodes']);
			else
				unset($vars['meta']['node_group']);

			unset($vars['meta']['set_node']);
		}

		$this->Input->setRules($this->getPackageRules($vars));

		$meta = array();
		if ($this->Input->validates($vars)) {
			// Return all package meta fields
			foreach ($vars['meta'] as $key => $value) {
				$meta[] = array(
					'key' => $key,
					'value' => $value,
					'encrypted' => 0
				);
			}
		}

		return $meta;
	}

	/**
	 * Validates input data when attempting to edit a package, returns the meta
	 * data to save when editing a package. Performs any action required to edit
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being edited.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array An array of key/value pairs used to edit the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editPackage($package, array $vars=null) {
		// Allow only nodes or a node group to be set
		if (isset($vars['meta']['set_node'])) {
			if ($vars['meta']['set_node'] == "0")
				unset($vars['meta']['nodes']);
			else
				unset($vars['meta']['node_group']);

			unset($vars['meta']['set_node']);
		}

		$this->Input->setRules($this->getPackageRules($vars));

		$meta = array();
		if ($this->Input->validates($vars)) {
			// Return all package meta fields
			foreach ($vars['meta'] as $key => $value) {
				$meta[] = array(
					'key' => $key,
					'value' => $value,
					'encrypted' => 0
				);
			}
		}

		return $meta;
	}

	/**
	 * Deletes the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being deleted.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function deletePackage($package) {
		// Nothing to do
		return null;
	}

	/**
	 * Returns the rendered view of the manage module page
	 *
	 * @param mixed $module A stdClass object representing the module and its rows
	 * @param array $vars An array of post data submitted to or on the manage module page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the manager module page
	 */
	public function manageModule($module, array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("manage", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		$this->view->set("module", $module);

		return $this->view->fetch();
	}

	/**
	 * Returns the rendered view of the add module row page
	 *
	 * @param array $vars An array of post data submitted to or on the add module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the add module row page
	 */
	public function manageAddRow(array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("add_row", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		$this->view->set("vars", (object)$vars);
		return $this->view->fetch();
	}

	/**
	 * Returns the rendered view of the edit module row page
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of post data submitted to or on the edit module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the edit module row page
	 */
	public function manageEditRow($module_row, array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("edit_row", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		if (empty($vars))
			$vars = $module_row->meta;

		$this->view->set("vars", (object)$vars);
		return $this->view->fetch();
	}

	/**
	 * Adds the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being added.
	 *
	 * @param array $vars An array of module info to add
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function addModuleRow(array &$vars) {
		$meta_fields = array("server_name", "user_name", "password", "host", "port");
		$encrypted_fields = array("user_name", "password");

		$this->Input->setRules($this->getRowRules($vars));

		// Validate module row
		if ($this->Input->validates($vars)) {

			// Build the meta data for this row
			$meta = array();
			foreach ($vars as $key => $value) {

				if (in_array($key, $meta_fields)) {
					$meta[] = array(
						'key' => $key,
						'value' => $value,
						'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
					);
				}
			}

			return $meta;
		}
	}

	/**
	 * Edits the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being updated.
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of module info to update
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function editModuleRow($module_row, array &$vars) {
		// Same as adding
		return $this->addModuleRow($vars);
	}

	/**
	 * Deletes the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being deleted.
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 */
	public function deleteModuleRow($module_row) {
		return null; // Nothing to do
	}

	/**
	 * Returns an array of available service delegation order methods. The module
	 * will determine how each method is defined. For example, the method "first"
	 * may be implemented such that it returns the module row with the least number
	 * of services assigned to it.
	 *
	 * @return array An array of order methods in key/value pairs where the key is the type to be stored for the group and value is the name for that option
	 * @see Module::selectModuleRow()
	 */
	public function getGroupOrderOptions() {
		return array('first'=>Language::_("Dsm.order_options.first", true));
	}

	/**
	 * Determines which module row should be attempted when a service is provisioned
	 * for the given group based upon the order method set for that group.
	 *
	 * @return int The module row ID to attempt to add the service with
	 * @see Module::getGroupOrderOptions()
	 */
	public function selectModuleRow($module_group_id) {
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));

		$group = $this->ModuleManager->getGroup($module_group_id);

		if ($group) {
			switch ($group->add_order) {
				default:
				case "first":

					foreach ($group->rows as $row) {
						return $row->id;
					}

					break;
			}
		}
		return 0;
	}

	/**
	 * Returns all fields used when adding/editing a package, including any
	 * javascript to execute when the page is rendered with these fields.
	 *
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getPackageFields($vars=null) {
		Loader::loadHelpers($this, array("Form", "Html"));

		// Fetch all packages available for the given server or server group
		$module_row = $this->getModuleRowByServer((isset($vars->module_row) ? $vars->module_row : 0), (isset($vars->module_group) ? $vars->module_group : ""));

		// Load more server info when the type is set
		if ($module_row && !empty($vars->meta['type'])) {

		}

		// Remove nodes from 'available' if they are currently 'assigned'
		if (isset($vars->meta['nodes'])) {
			$this->assignGroups($nodes, $vars->meta['nodes']);

			// Set the node value as the node key
			$temp = array();
			foreach ($vars->meta['nodes'] as $key => $value)
				$temp[$value] = $value;
			$vars->meta['nodes'] = $temp;
			unset($temp, $key, $value);

			// Individual nodes are assigned
			if (!empty($vars->meta['nodes']))
				$vars->meta['set_node'] = 1;
		}

		$fields = new ModuleFields();

		// Set the Dsm type as a selectable option
		$types = array('' => Language::_("Dsm.please_select", true)) + $this->getTypes();
		$type = $fields->label(Language::_("Dsm.package_fields.type", true), "Dsm_type");
		$type->attach($fields->fieldSelect("meta[type]", $types,
			$this->Html->ifSet($vars->meta['type']), array('id' => "Dsm_type")));
		$fields->setField($type);
		unset($type);

		return $fields;
	}

	/**
	 * Returns an array of key values for fields stored for a module, package,
	 * and service under this module, used to substitute those keys with their
	 * actual module, package, or service meta values in related emails.
	 *
	 * @return array A multi-dimensional array of key/value pairs where each key is one of 'module', 'package', or 'service' and each value is a numerically indexed array of key values that match meta fields under that category.
	 * @see Modules::addModuleRow()
	 * @see Modules::editModuleRow()
	 * @see Modules::addPackage()
	 * @see Modules::editPackage()
	 * @see Modules::addService()
	 * @see Modules::editService()
	 */
	public function getEmailTags() {
		return array(
			'module' => array("host", "port"),
			'package' => array(),
			'service' => array("Dsm_vserver_id", "Dsm_console_user", "Dsm_console_password",
				"Dsm_hostname", "Dsm_main_ip_address", "Dsm_internal_ip", "Dsm_extra_ip_addresses",
				"Dsm_node", "Dsm_username", "Dsm_password", "Dsm_plan", "Dsm_root_password",
				"Dsm_template", "Dsm_type", "Dsm_virt_id", "Dsm_vnc_ip", "Dsm_vnc_port",
				"Dsm_vnc_password"
			)
		);
	}

	/**
	 * Returns all fields to display to an admin attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getAdminAddFields($package, $vars=null) {
		Loader::loadHelpers($this, array("Html"));

		// Fetch the module row available for this package
		$module_row = $this->getModuleRowByServer((isset($package->module_row) ? $package->module_row : 0), (isset($package->module_group) ? $package->module_group : ""));

		$fields = new ModuleFields();

		// Create hostname label
		$host_name = $fields->label(Language::_("Dsm.service_field.Dsm_hostname", true), "Dsm_hostname");
		// Create hostname field and attach to hostname label
		$host_name->attach($fields->fieldText("Dsm_hostname", $this->Html->ifSet($vars->Dsm_hostname), array('id'=>"Dsm_hostname")));
		// Set the label as a field
		$fields->setField($host_name);

		// Create virtual server label
		$vserver_id = $fields->label(Language::_("Dsm.service_field.Dsm_vserver_id", true), "Dsm_vserver_id");
		// Create virtual server field and attach to virtual server label
		$vserver_id->attach($fields->fieldText("Dsm_vserver_id", $this->Html->ifSet($vars->Dsm_vserver_id), array('id'=>"Dsm_vserver_id")));
		// Add tooltip
		$tooltip = $fields->tooltip(Language::_("Dsm.service_field.tooltip.Dsm_vserver_id", true));
		$vserver_id->attach($tooltip);
		// Set the label as a field
		$fields->setField($vserver_id);

		return $fields;
	}

	/**
	 * Returns all fields to display to a client attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getClientAddFields($package, $vars=null) {
		Loader::loadHelpers($this, array("Html"));

		// Fetch the module row available for this package
		$module_row = $this->getModuleRowByServer((isset($package->module_row) ? $package->module_row : 0), (isset($package->module_group) ? $package->module_group : ""));

		$fields = new ModuleFields();

		// Create hostname label
		$host_name = $fields->label(Language::_("Dsm.service_field.Dsm_hostname", true), "Dsm_hostname");
		// Create hostname field and attach to hostname label
		$host_name->attach($fields->fieldText("Dsm_hostname", $this->Html->ifSet($vars->Dsm_hostname, $this->Html->ifSet($vars->domain)), array('id'=>"Dsm_hostname")));
		// Set the label as a field
		$fields->setField($host_name);

		return $fields;
	}

	/**
	 * Returns all fields to display to an admin attempting to edit a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */
	public function getAdminEditFields($package, $vars=null) {
		Loader::loadHelpers($this, array("Html"));

		$fields = new ModuleFields();

		// Create virtual server label
		$vserver_id = $fields->label(Language::_("Dsm.service_field.Dsm_vserver_id", true), "Dsm_vserver_id");
		// Create virtual server field and attach to virtual server label
		$vserver_id->attach($fields->fieldText("Dsm_vserver_id", $this->Html->ifSet($vars->Dsm_vserver_id), array('id'=>"Dsm_vserver_id")));
		// Add tooltip
		$tooltip = $fields->tooltip(Language::_("Dsm.service_field.tooltip.Dsm_vserver_id", true));
		$vserver_id->attach($tooltip);
		// Set the label as a field
		$fields->setField($vserver_id);

		return $fields;
	}

	/**
	 * Fetches the HTML content to display when viewing the service info in the
	 * admin interface.
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @param stdClass $package A stdClass object representing the service's package
	 * @return string HTML content containing information to display when viewing the service info
	 */
	public function getAdminServiceInfo($service, $package) {
		$row = $this->getModuleRow();

		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("admin_service_info", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$this->view->set("module_row", $row);
		$this->view->set("package", $package);
		$this->view->set("service", $service);
		$this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

		return $this->view->fetch();
	}

	/**
	 * Fetches the HTML content to display when viewing the service info in the
	 * client interface.
	 *
	 * @param stdClass $service A stdClass object representing the service
	 * @param stdClass $package A stdClass object representing the service's package
	 * @return string HTML content containing information to display when viewing the service info
	 */
	public function getClientServiceInfo($service, $package) {
		$row = $this->getModuleRow();

		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("client_service_info", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		$this->view->set("module_row", $row);
		$this->view->set("package", $package);
		$this->view->set("service", $service);
		$this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));

		return $this->view->fetch();
	}

	/**
	 * Returns all tabs to display to an admin when managing a service whose
	 * package uses this module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
	 */
	public function getAdminTabs($package) {
		return array(
			'tabActions' => Language::_("Dsm.tab_actions", true),
			'tabStats' => Language::_("Dsm.tab_stats", true),
			'tabConsole' => Language::_("Dsm.tab_console", true),
		);
	}

	/**
	 * Returns all tabs to display to a client when managing a service whose
	 * package uses this module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
	 */
	public function getClientTabs($package) {
		return array(
			'tabClientActions' => Language::_("Dsm.tab_actions", true),
			'tabClientStats' => Language::_("Dsm.tab_stats", true),
			'tabClientConsole' => Language::_("Dsm.tab_console", true),
		);
	}

	/**
	 * Actions tab (boot, reboot, shutdown, etc.)
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabActions($package, $service, array $get=null, array $post=null, array $files=null) {
		$this->view = new View("tab_actions", "default");
		$this->view->base_uri = $this->base_uri;
		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		// Get the service fields
		$service_fields = $this->serviceFieldsToObject($service->fields);
		$module_row = $this->getModuleRow($package->module_row);

		// Get templates
		$templates = $this->getTemplates($service_fields->Dsm_type, $module_row);

		// Perform the actions
		$vars = $this->actionsTab($package, $service, $templates, false, $get, $post);

		// Set default vars
		if (empty($vars))
			$vars = array('template' => $service_fields->Dsm_template, 'hostname' => $service_fields->Dsm_hostname);

		// Fetch the server status and templates
		$this->view->set("server", $this->getServerState($service_fields->Dsm_vserver_id, $module_row));
		$this->view->set("templates", $templates);

		$this->view->set("vars", (object)$vars);
		$this->view->set("client_id", $service->client_id);
		$this->view->set("service_id", $service->id);

		$this->view->set("view", $this->view->view);
		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);
		return $this->view->fetch();
	}

	/**
	 * Client Actions tab (boot, reboot, shutdown, etc.)
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabClientActions($package, $service, array $get=null, array $post=null, array $files=null) {
		$this->view = new View("tab_client_actions", "default");
		$this->view->base_uri = $this->base_uri;
		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		// Get the service fields
		$service_fields = $this->serviceFieldsToObject($service->fields);
		$module_row = $this->getModuleRow($package->module_row);

		// Get templates
		$templates = $this->getTemplates($service_fields->Dsm_type, $module_row);

		// Perform the actions
		$vars = $this->actionsTab($package, $service, $templates, true, $get, $post);

		// Set default vars
		if (empty($vars))
			$vars = array('template' => $service_fields->Dsm_template, 'hostname' => $service_fields->Dsm_hostname);

		// Fetch the server status and templates
		$this->view->set("server", $this->getServerState($service_fields->Dsm_vserver_id, $module_row));
		$this->view->set("templates", $templates);

		$this->view->set("vars", (object)$vars);
		$this->view->set("client_id", $service->client_id);
		$this->view->set("service_id", $service->id);

		$this->view->set("view", $this->view->view);
		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);
		return $this->view->fetch();
	}

	/**
	 * Handles data for the actions tab in the client and admin interfaces
	 * @see Dsm::tabActions() and Dsm::tabClientActions()
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $templates An array of Dsm templates
	 * @param boolean $client True if the action is being performed by the client, false otherwise
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return array An array of vars for the template
	 */
	private function actionsTab($package, $service, $templates, $client=false, array $get=null, array $post=null) {
		$vars = array();

		// Get the service fields
		$service_fields = $this->serviceFieldsToObject($service->fields);
		$module_row = $this->getModuleRow($package->module_row);

		$get_key = "3";
		if ($client)
			$get_key = "2";

		// Perform actions
		if (array_key_exists($get_key, (array)$get)) {

			switch ($get[$get_key]) {
				case "boot":
					if (!$this->performAction("boot", $service_fields->Dsm_vserver_id, $module_row))
						$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
					break;
				case "reboot":
					if (!$this->performAction("reboot", $service_fields->Dsm_vserver_id, $module_row))
						$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
					break;
				case "shutdown":
					if (!$this->performAction("shutdown", $service_fields->Dsm_vserver_id, $module_row))
						$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
					break;
				case "password":
					// Show the root password section
					$this->view->set("password", true);

					if (!empty($post)) {
						$rules = array(
							'password' => array(
								'length' => array(
									'rule' => array("minLength", 6),
									'message' => Language::_("Dsm.!error.Dsm_root_password.length", true)
								),
								'matches' => array(
									'rule' => array("compares", "==", (isset($post['confirm_password']) ? $post['confirm_password'] : null)),
									'message' => Language::_("Dsm.!error.Dsm_root_password.matches", true)
								)
							)
						);

						// Validate the template and perform the reinstallation
						$this->Input->setRules($rules);
						if ($this->Input->validates($post)) {
							// Update the service hostname
							Loader::loadModels($this, array("Services"));
							$this->Services->edit($service->id, array('Dsm_root_password' => $post['password']));

                            if (($errors = $this->Services->errors()))
                                $this->Input->setErrors($errors);

							// Do not show the hostname section again
							$this->view->set("password", false);
						}

						$vars = $post;
					}
					break;
				case "hostname":
					// Show the hostname section
					$this->view->set("hostname", true);

					if (!empty($post)) {
						$rules = array(
							'hostname' => array(
								'format' => array(
									'rule' => array(array($this, "validateHostName")),
									'message' => Language::_("Dsm.!error.Dsm_hostname.format", true)
								)
							)
						);

						// Validate the template and perform the reinstallation
						$this->Input->setRules($rules);
						if ($this->Input->validates($post)) {
							// Update the service hostname
							Loader::loadModels($this, array("Services"));
							$this->Services->edit($service->id, array('Dsm_hostname' => $post['hostname']));

                            if (($errors = $this->Services->errors()))
                                $this->Input->setErrors($errors);

							// Do not show the hostname section again
							$this->view->set("hostname", false);
						}

						$vars = $post;
					}
					break;
				case "reinstall":
					// Show the reinstall section
					$this->view->set("reinstall", true);

					if (!empty($post)) {
						$rules = array(
							'template' => array(
								'valid' => array(
									'rule' => array("array_key_exists", $templates),
									'message' => Language::_("Dsm.!error.api.template.valid", true)
								)
							),
							'confirm' => array(
								'valid' => array(
									'rule' => array("compares", "==", "1"),
									'message' => Language::_("Dsm.!error.api.confirm.valid", true)
								)
							)
						);

						// Validate the template and perform the reinstallation
						$this->Input->setRules($rules);
						if ($this->Input->validates($post)) {
							// Update the service template
							Loader::loadModels($this, array("Services"));
							$this->Services->edit($service->id, array('Dsm_template' => $post['template']));

                            if (($errors = $this->Services->errors()))
                                $this->Input->setErrors($errors);

							// Do not show the reinstall section again
							$this->view->set("reinstall", false);
						}

						$vars = $post;
					}
					break;
				default:
					break;
			}
		}

		return $vars;
	}

	/**
	 * Performs an action on the virtual server.
	 *
	 * @param string $action The action to perform (i.e. "boot", "reboot", "shutdown")
	 * @param int $server_id The virtual server ID
	 * @param stdClass $module_row An stdClass object representing a single server
	 * @param array $data A key=>value list of data parameters to include with the action
	 * @return boolean True if the action was performed successfully, false otherwise
	 */
	private function performAction($action, $server_id, $module_row, array $data=array()) {
		$api = $this->getApi($module_row->meta->user_id, $module_row->meta->key, $module_row->meta->host, $module_row->meta->port);

		// Load the vserver API
		$api->loadCommand("Dsm_vserver");
		$result = false;

		try {
			$server_api = new DsmVserver($api);
			$params = array_merge($data, array('vserverid' => $server_id));
            $masked_params = $params;
            $mask_keys = array("password", "rootpassword", "vncpassword", "consolepassword");

            foreach ($mask_keys as $mask_key) {
                if (array_key_exists($mask_key, $masked_params))
                    $masked_params[$mask_key] = "***";
            }

			$this->log($module_row->meta->host . "|vserver-" . $action, serialize($masked_params), "input", true);
			$response = $this->parseResponse(call_user_func_array(array($server_api, $action), array($params)), $module_row);

			if ($response && $response->status == "success")
				return true;
		}
		catch (Exception $e) {
			// Nothing to do
		}

		return $result;
	}

	/**
	 * Statistics tab (bandwidth/disk usage)
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabStats($package, $service, array $get=null, array $post=null, array $files=null) {
		$view = $this->statsTab($package, $service);
		return $view->fetch();
	}

	/**
	 * Client Statistics tab (bandwidth/disk usage)
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabClientStats($package, $service, array $get=null, array $post=null, array $files=null) {
		$view = $this->statsTab($package, $service, true);
		return $view->fetch();
	}

	/**
	 * Builds the data for the admin/client stats tabs
	 * @see Dsm::tabStats() and Dsm::tabClientStats()
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @return View A template view to be rendered
	 */
	private function statsTab($package, $service, $client=false) {
		$template = ($client ? "tab_client_stats" : "tab_stats");

		$this->view = new View($template, "default");
		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		// Get the service fields
		$service_fields = $this->serviceFieldsToObject($service->fields);
		$module_row = $this->getModuleRow($package->module_row);

		$this->view->set("server", $this->getServerState($service_fields->Dsm_vserver_id, $module_row, true));

		$this->view->set("module_hostname", (isset($module_row->meta->host) && isset($module_row->meta->port) ? "https://" . $module_row->meta->host . ":" . $module_row->meta->port : ""));

		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);
		return $this->view;
	}

	/**
	 * Console tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabConsole($package, $service, array $get=null, array $post=null, array $files=null) {
		$view = $this->consoleTab($package, $service);
		return $view->fetch();
	}

	/**
	 * Client Console tab
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $get Any GET parameters
	 * @param array $post Any POST parameters
	 * @param array $files Any FILES parameters
	 * @return string The string representing the contents of this tab
	 */
	public function tabClientConsole($package, $service, array $get=null, array $post=null, array $files=null) {
		$view = $this->consoleTab($package, $service, true);
		return $view->fetch();
	}

	/**
	 * Builds the data for the admin/client console tabs
	 * @see Dsm::tabConsole() and Dsm::tabClientConsole()
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @return View A template view to be rendered
	 */
	private function consoleTab($package, $service, $client=false) {
		$template = ($client ? "tab_client_console" : "tab_console");
		$this->view = new View($template, "default");
		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html"));

		// Get the service fields
		$service_fields = $this->serviceFieldsToObject($service->fields);
		$module_row = $this->getModuleRow($package->module_row);
		$type = "console";

		// Determine whether to use the console or VNC
		if (in_array(strtolower($service_fields->Dsm_type), array("xen", "openvz"))) {
			// Console for OpenVZ/XEN
			$session = $this->setConsoleSession($service, $module_row);
		}
		else {
			// VNC for HVM/KVM
			$type = "vnc";
			$session = array(
				'vnc_ip' => $service_fields->Dsm_vnc_ip,
				'vnc_password' => $service_fields->Dsm_vnc_password,
				'vnc_port' => $service_fields->Dsm_vnc_port
			);

			// Check whether the VNC vendor code is available
			$this->view->set("vnc_applet_available", is_dir(VENDORDIR . "vnc"));
		}

		$this->view->set("node_statistics", $this->getNodeStatistics($service_fields->Dsm_node, $module_row));
		$this->view->set("console", (object)$session);
		$this->view->set("type", $type);

		$this->view->setDefaultView("components" . DS . "modules" . DS . "dsm" . DS);
		return $this->view;
	}

	/**
	 * Converts bytes to a string representation including the type
	 *
	 * @param int $bytes The number of bytes
	 * @return string A formatted amount including the type (B, KB, MB, GB)
	 */
	private function convertBytesToString($bytes) {
		$step = 1024;
		$unit = "B";

		if (($value = number_format($bytes/($step*$step*$step), 2)) >= 1)
			$unit = "GB";
		elseif (($value = number_format($bytes/($step*$step), 2)) >= 1)
			$unit = "MB";
		elseif (($value = number_format($bytes/($step), 2)) >= 1)
			$unit = "KB";
		else
			$value = $bytes;

		return Language::_("Dsm.!bytes.value", true, $value, $unit);
	}

	/**
	 * Initializes the API and returns an instance of that object with the given $host, $user, and $pass set
	 *
	 * @param string $user_id The ID of the Dsm user
	 * @param string $key The key to the Dsm server
	 * @param string $host The host to the Dsm server
	 * @param string $port The Dsm server port number
	 * @return DsmApi The DsmApi instance
	 */
	private function getApi($user_id, $key, $host, $port) {
		Loader::load(dirname(__FILE__) . DS . "apis" . DS . "Dsm_api.php");

		return new DsmApi($user_id, $key, $host, $port);
	}

	/**
	 * Sets up a new console session with the virtual server
	 *
	 * @param stdClass $service An stdClass object representing the service from which to start a console session
	 * @param stdClass $module_row An stdClass object representing the module row
	 * @param int $length The length of time (in hours) the session should be active for. Must be between 1 and 8.
	 * @return array An array containing the console username and password
	 */
	private function setConsoleSession($service, $module_row, $length=1) {
		$api = $this->getApi($module_row->meta->user_id, $module_row->meta->key, $module_row->meta->host, $module_row->meta->port);

		$service_fields = $this->serviceFieldsToObject($service->fields);

		// Load the server API
		$api->loadCommand("Dsm_vserver");
		$response = null;

		// Enable a new console session
		try {
			$server_api = new DsmVserver($api);
			$params = array('vserverid' => $service_fields->Dsm_vserver_id, 'access' => "enable", 'time' => (int)$length);

			$this->log($module_row->meta->host . "|vserver-console", serialize($params), "input", true);
			$response = $this->parseResponse($server_api->console($params), $module_row);
		}
		catch (Exception $e) {
			// Nothing to do
		}

		$session = array('username' => "", 'password' => "");

		// Return the console user
		if ($response && $response->status == "success") {
			$session['username'] = (property_exists($response, "consoleusername") ? $response->consoleusername : "");
			$session['password'] = (property_exists($response, "consolepassword") ? $response->consolepassword : "");
		}

		return $session;
	}

	/**
	 * Retrieves a list of the virtual server state fields, e.g. bandwidth, type, graphs
	 *
	 * @param int $server_id The virtual server ID
	 * @param stdClass $module_row A stdClass object representing a single server
	 * @param boolean $fetch_graphs True to fetch graphs, false otherwise
	 * @return stdClass An stdClass object representing the server state fields
	 */
	private function getServerState($server_id, $module_row, $fetch_graphs = false) {
		$api = $this->getApi($module_row->meta->user_id, $module_row->meta->key, $module_row->meta->host, $module_row->meta->port);

		// Load the nodes API
		$api->loadCommand("Dsm_vserver");
		$response = null;

		try {
			$server_api = new DsmVserver($api);
			$params = array('vserverid' => $server_id);

			if (!$fetch_graphs)
				$params['nographs'] = "true";

			$this->log($module_row->meta->host . "|vserver-infoall", serialize($params), "input", true);
			$response = $this->parseResponse($server_api->infoAll($params), $module_row);
		}
		catch (Exception $e) {
			// Nothing to do
		}

		// Set the CSV values to an array of values
		if ($response) {
			$fields = array('hdd' => "space", 'memory' => "memory", 'bandwidth' => "bandwidth");
			foreach ($fields as $field => $name) {
				if (!property_exists($response, $field))
					continue;

				$values = $this->csvToArray($response->{$field}, true);
				$response->{$field} = array(
					'total_' . $name => (isset($values[0]) ? $values[0] : ""),
					'used_' . $name => (isset($values[1]) ? $values[1] : ""),
					'free_' . $name => (isset($values[2]) ? $values[2] : ""),
					'percent_used_' . $name => (isset($values[3]) ? $values[3] : ""),
					'total_' . $name . "_formatted" => $this->convertBytesToString((isset($values[0]) ? $values[0] : "")),
					'used_' . $name . "_formatted" => $this->convertBytesToString((isset($values[1]) ? $values[1] : "")),
					'free_' . $name . "_formatted" => $this->convertBytesToString((isset($values[2]) ? $values[2] : "")),
					'percent_used_' . $name . "_formatted" => Language::_("Dsm.!percent.used", true, (isset($values[3]) ? $values[3] : "")),
				);
			}
		}
		return ($response ? $response : new stdClass());
	}

	/**
	 * Fetches the plans available for the Dsm server of the given type
	 *
	 * @param string $type The type of server (i.e. openvz, xen, xen hvm, kvm)
	 * @param stdClass $module_row A stdClass object representing a single server
	 * @return array A list of plans
	 */
	private function getPlans($type, $module_row) {
		$api = $this->getApi($module_row->meta->user_id, $module_row->meta->key, $module_row->meta->host, $module_row->meta->port);

		// Load the plans API
		$api->loadCommand("Dsm_plans");
		$response = null;

		try {
			$plans_api = new DsmPlans($api);
			$params = array('type' => $type);

			$this->log($module_row->meta->host . "|listplans", serialize($params), "input", true);
			$response = $this->parseResponse($plans_api->getList($params), $module_row);
		}
		catch (Exception $e) {
			// Nothing to do
			return array();
		}

		// Return the plans
		if ($response && $response->status == "success")
			return $this->csvToArray($response->plans);

		return array();
	}

	/**
	 * Fetches the templates available for the Dsm server of the given type
	 *
	 * @param string $type The type of server (i.e. openvz, xen, xen hvm, kvm)
	 * @param stdClass $module_row A stdClass object representing a single server
	 * @return array A list of templates
	 */
	private function getTemplates($type, $module_row) {
		$api = $this->getApi($module_row->meta->user_id, $module_row->meta->key, $module_row->meta->host, $module_row->meta->port);

		// Load the templates API
		$api->loadCommand("Dsm_templates");
		$response = null;

		try {
			$templates_api = new DsmTemplates($api);
			$params = array('type' => $type);

			$this->log($module_row->meta->host . "|listtemplates", serialize($params), "input", true);
			$response = $this->parseResponse($templates_api->getList($params), $module_row);
		}
		catch (Exception $e) {
			// Nothing to do
			return array();
		}

		// Return the templates
		if ($response && $response->status == "success") {
			// Fetch the templates
			$property = "templates";
			switch ($type) {
				case "xen hvm":
					$property .= "hvm";
					break;
				case "kvm":
					$property .= "kvm";
					break;
			}

			$templates = $this->csvToArray($response->{$property});

			// Remove the none option
			unset($templates['--none--']);
			return $templates;
		}

		return array();
	}

	/**
	 * Creates a new Dsm Client. May set Input::errors() on error.
	 *
	 * @param int $client_id The client ID
	 * @param string $username The client's username
	 * @param stdClass $module_row The server module row
	 * @return array An key/value array including the client's username and password. If the client already exists in Dsm, then the password returned is null
	 */
	private function createClient($client_id, $username, $module_row) {
		// Get the API
		$api = $this->getApi($module_row->meta->user_id, $module_row->meta->key, $module_row->meta->host, $module_row->meta->port);
		$api->loadCommand("Dsm_client");

		$client_fields = array('username' => $username, 'password' => null);
		$response = false;

		// Check if a client exists
		try {
			// Load up the Virtual Server API
			$client_api = new DsmClient($api);
			$params = array('username' => $client_fields['username']);

			// Provision the Virtual Server
			$this->log($module_row->meta->host . "|client-checkexists", serialize($params), "input", true);
			$response = $this->parseResponse($client_api->checkExists($params), $module_row, true);
		}
		catch (Exception $e) {
			// Internal Error
			$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
			return $client_fields;
		}

		// Client does not exist, attempt to create one
		if ($response && $response->status != "success") {
			$response = false;

			// Fetch the client to set additional client fields
			Loader::loadModels($this, array("Clients"));
			$client_params = array();
			if (($client = $this->Clients->get($client_id, false))) {
				$client_params = array(
					'email' => $client->email,
					'company' => $client->company,
					'firstname' => $client->first_name,
					'lastname' => $client->last_name
				);
			}

			try {
				// Generate a client password
				$client_fields['password'] = $this->generatePassword();

				$params = array_merge($client_fields, $client_params);
				$masked_params = $params;
				$masked_params['password'] = "***";

				// Create a client
				$this->log($module_row->meta->host . "|client-create", serialize($masked_params), "input", true);
				$response = $this->parseResponse($client_api->create($params), $module_row);
			}
			catch (Exception $e) {
				// Internal Error
				$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
			}

			// Error, client account could not be created
			if (!$response || $response->status != "success")
				$this->Input->setErrors(array('create_client' => array('failed' => Language::_("Dsm.!error.create_client.failed", true))));
		}

		return $client_fields;
	}

	/**
	 * Parses the response from Dsm into an stdClass object
	 *
	 * @param DsmResponse $response The response from the API
	 * @param stdClass $module_row A stdClass object representing a single server (optional, required when Module::getModuleRow() is unavailable)
	 * @param boolean $ignore_error Ignores any response error and returns the response anyway; useful when a response is expected to fail (e.g. check client exists) (optional, default false)
	 * @return stdClass A stdClass object representing the response, void if the response was an error
	 */
	private function parseResponse(DsmResponse $response, $module_row = null, $ignore_error = false) {
		Loader::loadHelpers($this, array("Html"));

		// Set the module row
		if (!$module_row)
			$module_row = $this->getModuleRow();

		$success = false;

		switch ($response->status()) {
			case "success":
				$success = true;
				break;
			case "error":
				$success = false;

				// Ignore generating the error
				if ($ignore_error)
					break;

				$errors = $response->errors();
				$error = isset($errors->statusmsg) ? $errors->statusmsg : "";
				$this->Input->setErrors(array('api' => array('response' => $this->Html->safe($error))));
				break;
			default:
				// Invalid response
				$success = false;

				// Ignore generating the error
				if ($ignore_error)
					break;

				$this->Input->setErrors(array('api' => array('internal' => Language::_("Dsm.!error.api.internal", true))));
				break;
		}

		// Replace sensitive fields
		$masked_params = array("password", "rootpassword", "vncpassword", "consolepassword");
		$output = $this->formatResponse($response->response());
		$raw_output = $response->raw();

		foreach ($masked_params as $masked_param) {
			if (property_exists($output, $masked_param))
				$raw_output = preg_replace("/<" . $masked_param . ">(.*)<\/" . $masked_param . ">/", "<" . $masked_param . ">***</" . $masked_param . ">", $raw_output);
		}

		// Log the response
		$this->log($module_row->meta->host, $raw_output, "output", $success);

		if (!$success && !$ignore_error)
			return;

		return $output;
	}

	/**
	 * Formats API response values to strings
	 *
	 * @param stdClass $response The API response
	 */
	private function formatResponse($response) {
		$temp_response = (array)$response;

		// Convert empty object values to empty string values
		foreach ($temp_response as $key => $value) {
			if (is_object($value))
				$response->{$key} = "";
		}

		return $response;
	}

	/**
	 * Builds a key/value array out of a CSV list
	 *
	 * @param string $csv A comma-separated list of strings
	 * @param boolean $indexed True to index the array numerically, false to set each CSV string as the key AND value; duplicates will be overwritten (optional, default false)
	 */
	private function csvToArray($csv, $indexed = false) {
		$data = explode(",", $csv);

		// Return numerically-indexed list
		if ($indexed)
			return $data;

		// Return identical key/value pairs
		$data = array_flip($data);
		foreach ($data as $key => &$value)
			$value = $key;
		return $data;
	}

	/**
	 * Sets the assigned and available groups. Manipulates the $available_groups by reference.
	 *
	 * @param array $available_groups A key/value list of available groups
	 * @param array $assigned_groups A numerically-indexed array of assigned groups
	 */
	private function assignGroups(&$available_groups, $assigned_groups) {
		// Remove available groups if they are assigned
		foreach ($assigned_groups as $key => $value) {
			if (isset($available_groups[$value]))
				unset($available_groups[$value]);
		}
	}

	/**
	 * Retrieves the module row given the server or server group
	 *
	 * @param string $module_row The module row ID
	 * @param string $module_group The module group (optional, default "")
	 * @return mixed An stdClass object representing the module row, or null if it could not be determined
	 */
	private function getModuleRowByServer($module_row, $module_group = "") {
		// Fetch the module row available for this package
		$row = null;
		if ($module_group == "") {
			if ($module_row > 0) {
				$row = $this->getModuleRow($module_row);
			}
			else {
				$rows = $this->getModuleRows();
				if (isset($rows[0]))
					$row = $rows[0];
				unset($rows);
			}
		}
		else {
			// Fetch the 1st server from the list of servers in the selected group
			$rows = $this->getModuleRows($module_group);

			if (isset($rows[0]))
				$row = $rows[0];
			unset($rows);
		}

		return $row;
	}

	/**
	 * Retrieves a list of server types and their language
	 *
	 * @return array A list of server types and their language
	 */
	private function getTypes() {
		return array(
			'Windows' => Language::_("Dsm.types.windows", true),
			'Linux' => Language::_("Dsm.types.linux", true),
			'Unix' => Language::_("Dsm.types.unix", true),
		);
	}

	/**
	 * Generates a password for Dsm client accounts
	 *
	 * @param int $min_chars The minimum number of characters to generate in the password (optional, default 12)
	 * @param int $max_chars The maximum number of characters to generate in the password (optional, default 12)
	 * @return string A randomly-generated password
	 */
	private function generatePassword($min_chars = 12, $max_chars = 12) {

		$password = "";
		$pool = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

		// Allow special characters
		if (Configure::get("Dsm.password.allow_special_characters"))
			$pool .= "!@#$%^&*()";

		$pool_size = strlen($pool);
		$length = (int)abs($min_chars == $max_chars ? $min_chars : mt_rand($min_chars, $max_chars));

		for ($i=0; $i<$length; $i++) {
			$password .= substr($pool, mt_rand(0, $pool_size-1), 1);
		}

		return $password;
	}

	/**
	 * Retrieves a list of rules for validating adding/editing a module row
	 *
	 * @param array $vars A list of input vars
	 * @return array A list of rules
	 */
	private function getRowRules(array &$vars) {
		return array(
			'server_name' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Dsm.!error.server_name.empty", true)
				)
			),
			'user_name' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Dsm.!error.user_id.empty", true)
				)
			),
			'password' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Dsm.!error.key.empty", true)
				)
			),
			'host' => array(
				'format' => array(
					'rule' => array(array($this, "validateHostName")),
					'message' => Language::_("Dsm.!error.host.format", true)
				)
			),
			'port' => array(
				'format' => array(
					'rule' => array("matches", "/^[0-9]+$/"),
					'message' => Language::_("Dsm.!error.port.format", true)
				)
			)
		);
	}

	/**
	 * Retrieves a list of rules for validating adding/editing a package
	 *
	 * @param array $vars A list of input vars
	 * @return array A list of rules
	 */
	private function getPackageRules(array $vars = null) {
		$rules = array(
			'meta[type]' => array(
				'valid' => array(
					'rule' => array("in_array", array_keys($this->getTypes())),
					'message' => Language::_("Dsm.!error.meta[type].valid", true)
				)
			),
			'meta[nodes]' => array(
				'empty' => array(
					'rule' => array(array($this, "validateNodeSet"), (isset($vars['meta']['node_group']) ? $vars['meta']['node_group'] : null)),
					'message' => Language::_("Dsm.!error.meta[nodes].empty", true),
				)
			),
			'meta[plan]' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Dsm.!error.meta[plan].empty", true)
				)
			),
			'meta[set_template]' => array(
				'format' => array(
					'rule' => array("in_array", array("admin", "client")),
					'message' => Language::_("Dsm.!error.meta[set_template].format", true)
				)
			)
		);

		// A template must be given for this package
		if (isset($vars['meta']['set_template']) && $vars['meta']['set_template'] == "admin") {
			$rules['meta[template]'] = array(
				'empty' => array(
					'rule' => array("in_array", array("", "--none--")),
					'negate' => true,
					'message' => Language::_("Dsm.!error.meta[template].empty", true)
				)
			);
		}

		return $rules;
	}

	/**
	 * Validates that at least one node was selected when adding a package
	 *
	 * @param array $nodes A list of node names
	 * @param string $node_groups A selected node group
	 * @return boolean True if at least one node was given, false otherwise
	 */
	public function validateNodeSet($nodes, $node_group=null) {
		// Require at least one node or node group
		if ($node_group === null)
			return (isset($nodes[0]) && !empty($nodes[0]));
		elseif ($node_group != "")
			return true;
		return false;
	}

	/**
	 * Validates that the given hostname is valid
	 *
	 * @param string $host_name The host name to validate
	 * @return boolean True if the hostname is valid, false otherwise
	 */
	public function validateHostName($host_name) {
		if (strlen($host_name) > 255)
			return false;

		return $this->Input->matches($host_name, "/^([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9])(\.([a-z0-9]|[a-z0-9][a-z0-9\-]{0,61}[a-z0-9]))+$/");
	}

	/**
	 * Validates whether the given template is a valid template for this server type
	 *
	 * @param string $template The IOS template
	 * @param string $type The type of server (i.e. "openvz", "xen hvm", "xen", "kvm")
	 * @param string $module_row The server module row
	 * @param string $module_group The server module group (optional, default "")
	 * @return boolean True if the template is valid, false otherwise
	 */
	public function validateTemplate($template, $type, $module_row, $module_group = "") {
		// Fetch the module row
		$row = $this->getModuleRowByServer($module_row, $module_group);
		$templates = $this->getTemplates($type, $row);

		return in_array($template, $templates);
	}
}
?>
