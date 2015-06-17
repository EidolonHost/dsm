<?php
// Actions
$lang['Dsm.!actions.boot'] = "Boot";
$lang['Dsm.!actions.reboot'] = "Reboot";
$lang['Dsm.!actions.shutdown'] = "Shutdown";
$lang['Dsm.!actions.reinstall'] = "Reinstall";
$lang['Dsm.!actions.hostname'] = "Change Hostname";
$lang['Dsm.!actions.password'] = "Change Password";


// Errors
$lang['Dsm.!error.simplexml_required'] = "The simplexml extension is required for this module.";

$lang['Dsm.!error.server_name.empty'] = "Please enter a server label.";
$lang['Dsm.!error.user_id.empty'] = "Please enter a user ID.";
$lang['Dsm.!error.key.empty'] = "Please enter a key.";
$lang['Dsm.!error.host.format'] = "The hostname appears to be invalid.";
$lang['Dsm.!error.port.format'] = "Please enter a valid port number.";
$lang['Dsm.!error.device_id.empty'] = "The Device ID appears to be missing.";
$lang['Dsm.!error.device_ip.empty'] = "The Device IP appears to be missing.";

$lang['Dsm.!error.meta[type].valid'] = "Please select a valid server type.";
$lang['Dsm.!error.meta[datacenter].empty'] = "Please set a datacenter for the server.";
$lang['Dsm.!error.meta[location].empty'] = "Please set a location for the server.";

$lang['Dsm.!error.api.internal'] = "An internal error occurred, or the server did not respond to the request.";

$lang['Dsm.!error.Dsm_hostname.format'] = "The hostname appears to be invalid.";
$lang['Dsm.!error.Dsm_template.valid'] = "Please select a valid template.";

$lang['Dsm.!error.create_client.failed'] = "An internal error occurred and the client account could not be created.";

$lang['Dsm.!error.api.template.valid'] = "The selected template is invalid.";
$lang['Dsm.!error.api.confirm.valid'] = "You must acknowledge that you understand the reinstall action in order to perform the template reinstallation.";

$lang['Dsm.!error.Dsm_root_password.length'] = "The root password must be at least 6 characters in length.";
$lang['Dsm.!error.Dsm_root_password.matches'] = "The root passwords do not match.";

$lang['Dsm.!error.Dsm_vserver_id.format'] = "The Virtual Server ID must be an integer.";


// Common
$lang['Dsm.please_select'] = "-- Please Select --";
$lang['Dsm.!bytes.value'] = "%1\$s%2\$s"; // %1$s is a number value, %2$s is the unit of that value (i.e., one of B, KB, MB, GB)
$lang['Dsm.!percent.used'] = "%1\$s%"; // %1$s is a percentage value

// Basics
$lang['Dsm.name'] = "Dedicated Server Module";
$lang['Dsm.module_row'] = "Dedicated/Colocation Servers";
$lang['Dsm.module_row_plural'] = "Servers";
$lang['Dsm.module_group'] = "Server Groups";

// Module management
$lang['Dsm.add_module_row'] = "Add Server";
$lang['Dsm.add_module_group'] = "Add Server Group";
$lang['Dsm.manage.module_rows_title'] = "Dedicated/Colocation Servers";
$lang['Dsm.manage.module_groups_title'] = "DSM Server Groups";
$lang['Dsm.manage.module_rows_heading.server_label'] = "Server Label";
$lang['Dsm.manage.module_rows_heading.host'] = "Hostname";
$lang['Dsm.manage.module_rows_heading.options'] = "Options";
$lang['Dsm.manage.module_groups_heading.name'] = "Group Name";
$lang['Dsm.manage.module_groups_heading.servers'] = "Server Count";
$lang['Dsm.manage.module_groups_heading.options'] = "Options";
$lang['Dsm.manage.module_rows.edit'] = "Edit";
$lang['Dsm.manage.module_groups.edit'] = "Edit";
$lang['Dsm.manage.module_rows.delete'] = "Delete";
$lang['Dsm.manage.module_groups.delete'] = "Delete";
$lang['Dsm.manage.module_rows.confirm_delete'] = "Are you sure you want to delete this server?";
$lang['Dsm.manage.module_groups.confirm_delete'] = "Are you sure you want to delete this server group?";
$lang['Dsm.manage.module_rows_no_results'] = "There are no servers.";
$lang['Dsm.manage.module_groups_no_results'] = "There are no server groups.";

$lang['Dsm.order_options.first'] = "First unassigned server";


// Module row meta data
$lang['Dsm.row_meta.server_name'] = "Server Label";
$lang['Dsm.row_meta.host'] = "Hostname";
$lang['Dsm.row_meta.datacenter'] = "Datacenter (e.g., SingleHop)";
$lang['Dsm.row_meta.server_loc'] = "Location (e.g., Dallas, TX.)";
$lang['Dsm.row_meta.conpanel'] = "Control Panel";
$lang['Dsm.row_meta.device_id'] = "Device ID (e.g., server's ID is 123456.)";
$lang['Dsm.row_meta.device_ip'] = "Device IP (e.g., server's main IP is 1.2.3.4)";


// Server types
$lang['Dsm.types.windows'] = "Windows";
$lang['Dsm.types.linux'] = "Linux";
$lang['Dsm.types.unix'] = "Unix";

// Add module row
$lang['Dsm.add_row.box_title'] = "Add Server";
$lang['Dsm.add_row.basic_title'] = "Basic Settings";
$lang['Dsm.add_row.conpanel_title'] = "Control Panel";
$lang['Dsm.add_row.notes_title'] = "Notes";
$lang['Dsm.add_row.add_btn'] = "Add Server";


// Edit module row
$lang['Dsm.edit_row.box_title'] = "Edit Server";
$lang['Dsm.edit_row.basic_title'] = "Basic Settings";
$lang['Dsm.edit_row.conpanel_title'] = "Control Panel";
$lang['Dsm.edit_row.notes_title'] = "Notes";
$lang['Dsm.edit_row.add_btn'] = "Update Server";


// Package fields
$lang['Dsm.package_fields.type'] = "Type";
$lang['Dsm.package_fields.template'] = "Template";
$lang['Dsm.package_fields.admin_set_template'] = "Select a template";
$lang['Dsm.package_fields.client_set_template'] = "Let client set template";
$lang['Dsm.package_fields.plan'] = "Plan";

$lang['Dsm.package_fields.assigned_nodes'] = "Assigned Nodes";
$lang['Dsm.package_fields.available_nodes'] = "Available Nodes";

$lang['Dsm.package_fields.set_node'] = "Assign a set of nodes";
$lang['Dsm.package_fields.set_node_group'] = "Assign a node group";
$lang['Dsm.package_fields.node_group'] = "Node Group";


// Service fields
$lang['Dsm.service_field.Dsm_hostname'] = "Hostname";
$lang['Dsm.service_field.Dsm_template'] = "Template";
$lang['Dsm.service_field.Dsm_vserver_id'] = "Virtual Server ID";
$lang['Dsm.service_field.tooltip.Dsm_vserver_id'] = "The Virtual Server ID specifies the VPS from Dsm to which this service will be attached. Changing this value will only affect this service locally.";


// Service Info fields
$lang['Dsm.service_info.Dsm_main_ip_address'] = "Primary IP Address";


// Tabs
$lang['Dsm.tab_actions'] = "Server Actions";
$lang['Dsm.tab_stats'] = "Stats";
$lang['Dsm.tab_console'] = "Console";


// Actions Tab
$lang['Dsm.tab_actions.heading_actions'] = "Actions";

$lang['Dsm.tab_actions.status_online'] = "Online";
$lang['Dsm.tab_actions.status_offline'] = "Offline";
$lang['Dsm.tab_actions.status_disabled'] = "Disabled";
$lang['Dsm.tab_actions.server_status'] = "Server Status";

$lang['Dsm.tab_actions.heading_reinstall'] = "Reinstall";
$lang['Dsm.tab_actions.field_template'] = "Template";
$lang['Dsm.tab_actions.field_confirm'] = "I understand that by reinstalling, all data on the server will be permanently deleted, and the selected template will be installed.";
$lang['Dsm.tab_actions.field_reinstall_submit'] = "Reinstall";

$lang['Dsm.tab_actions.heading_hostname'] = "Change Hostname";
$lang['Dsm.tab_actions.text_hostname_reboot'] = "A change to the hostname will only take effect after the server has been rebooted.";
$lang['Dsm.tab_actions.field_hostname'] = "Hostname";
$lang['Dsm.tab_actions.field_hostname_submit'] = "Change Hostname";

$lang['Dsm.tab_actions.heading_password'] = "Change Password";
$lang['Dsm.tab_actions.field_password'] = "New Root Password";
$lang['Dsm.tab_actions.field_confirm_password'] = "Confirm Password";
$lang['Dsm.tab_actions.field_password_submit'] = "Change Password";


// Client Actions Tab
$lang['Dsm.tab_client_actions.heading_actions'] = "Actions";
$lang['Dsm.tab_client_actions.heading_server_status'] = "Server Status";

$lang['Dsm.tab_client_actions.status_online'] = "Online";
$lang['Dsm.tab_client_actions.status_offline'] = "Offline";
$lang['Dsm.tab_client_actions.status_disabled'] = "Disabled";

$lang['Dsm.tab_client_actions.heading_reinstall'] = "Reinstall";
$lang['Dsm.tab_client_actions.field_template'] = "Template";
$lang['Dsm.tab_client_actions.field_confirm'] = "I understand that by reinstalling, all data on the server will be permanently deleted, and the selected template will be installed.";
$lang['Dsm.tab_client_actions.field_reinstall_submit'] = "Reinstall";

$lang['Dsm.tab_client_actions.heading_hostname'] = "Change Hostname";
$lang['Dsm.tab_client_actions.text_hostname_reboot'] = "A change to the hostname will only take effect after the server has been rebooted.";
$lang['Dsm.tab_client_actions.field_hostname'] = "Hostname";
$lang['Dsm.tab_client_actions.field_hostname_submit'] = "Change Hostname";

$lang['Dsm.tab_client_actions.heading_password'] = "Change Password";
$lang['Dsm.tab_client_actions.field_password'] = "New Root Password";
$lang['Dsm.tab_client_actions.field_confirm_password'] = "Confirm Password";
$lang['Dsm.tab_client_actions.field_password_submit'] = "Change Password";


// Stats Tab
$lang['Dsm.tab_stats.heading_stats'] = "Statistics";

$lang['Dsm.tab_stats.bandwidth'] = "Bandwidth:";
$lang['Dsm.tab_stats.bandwidth_stats'] = "%1\$s/%2\$s"; // %1$s is the bandwidth used, %2$s is the total bandwidth available
$lang['Dsm.tab_stats.bandwidth_percent_available'] = "(%1\$s%%)"; // %1$s is the percentage of bandwidth used. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['Dsm.tab_stats.memory'] = "Memory:";
$lang['Dsm.tab_stats.memory_stats'] = "%1\$s/%2\$s"; // %1$s is the memory used, %2$s is the total memory available
$lang['Dsm.tab_stats.memory_percent_available'] = "(%1\$s%%)"; // %1$s is the percentage of memory used. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['Dsm.tab_stats.space'] = "Disk Space:";
$lang['Dsm.tab_stats.space_stats'] = "%1\$s/%2\$s"; // %1$s is the hard disk space used, %2$s is the total hard disk space available
$lang['Dsm.tab_stats.space_percent_available'] = "(%1\$s%%)"; // %1$s is the percentage of hard disk space used. You MUST use two % signs to represent a single percent (i.e. %%)
$lang['Dsm.tab_status.no_results'] = "Statistics are not currently available.";

$lang['Dsm.tab_stats.heading_graphs'] = "Graphs";


// Client Stats Tab
$lang['Dsm.tab_client_stats.heading_stats'] = "Statistics";

$lang['Dsm.tab_client_stats.bandwidth'] = "Bandwidth";
$lang['Dsm.tab_client_stats.memory'] = "Memory";
$lang['Dsm.tab_client_stats.space'] = "Disk Space";

$lang['Dsm.tab_client_stats.usage'] = "(%1\$s/%2\$s)"; // %1$s is the amount of resources used, %2$s is the amount of total resources available

$lang['Dsm.tab_client_stats.heading_graphs'] = "Graphs";


// Console Tab
$lang['Dsm.tab_console.heading_console'] = "Console";
$lang['Dsm.tab_console.console_username'] = "Console Username:";
$lang['Dsm.tab_console.console_password'] = "Console Password:";

$lang['Dsm.tab_console.vnc_ip'] = "VNC Host:";
$lang['Dsm.tab_console.vnc_port'] = "VNC Port:";
$lang['Dsm.tab_console.vnc_password'] = "VNC Password:";


// Client Console Tab
$lang['Dsm.tab_client_console.heading_console'] = "Console";
$lang['Dsm.tab_client_console.console_username'] = "Console Username";
$lang['Dsm.tab_client_console.console_password'] = "Console Password";

$lang['Dsm.tab_client_console.vnc_password'] = "VNC Password";
?>
