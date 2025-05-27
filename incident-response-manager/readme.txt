=== Incident Response Manager ===
Contributors: (Your Name or Username)
Tags: incident management, emergency response, personnel management, vehicle tracking, equipment inventory, reporting, dashboard, API, roster, shifts, maintenance
Requires at least: 5.8
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress plugin for managing incident response operations, personnel, resources, and generating insightful reports.

== Description ==

The Incident Response Manager (IRM) plugin provides a robust platform for emergency response teams, fire departments, or any organization needing to manage incidents and associated resources efficiently. It allows for detailed tracking of incidents, management of personnel with shift scheduling, inventory control for vehicles and equipment (including maintenance), and offers a dashboard for at-a-glance operational overview.

**Key Features:**

*   **Incident Management**: Create, track, and update incidents with details like type, location, date/time, status (Reported, Dispatched, On Scene, Under Control, Closed), assigned personnel, and responding vehicles. Includes a detailed logging system for each incident.
*   **Personnel Management**: Manage a roster of personnel, including their rank, qualifications, contact information, and assign them to predefined shifts.
*   **Vehicle Inventory**: Keep track of all vehicles, their type, call sign, status (Available, Out of Service, In Maintenance, Deployed), assigned personnel, and maintenance schedules. Includes a maintenance logging feature.
*   **Equipment Inventory**: Manage equipment items, their type, ID, location, status, and maintenance schedules, complete with maintenance logs.
*   **Shift Management**: Define standard operational shifts (e.g., A Shift, B Shift) with start/end times and rotation descriptions.
*   **Duty Roster**: View a duty roster page showing personnel assigned to shifts and a basic approximation of who is currently on duty.
*   **Dashboard**: A central dashboard provides an overview of active incidents, personnel on duty (approximation), vehicle and equipment status summaries with charts, and a chart of incidents over the last 7 days.
*   **Reporting**: Generate reports on incidents, resource utilization (vehicles/equipment), and personnel activity, with filtering options and CSV export capabilities.
*   **User Roles & Capabilities**: Predefined roles (Firefighter, Captain, Chief) with specific permissions tailored to their responsibilities. Administrators have full access.
*   **REST API**: A comprehensive REST API allows for integration with external systems and third-party automation services.
*   **Integrations**: Supports polling-based triggers for services like Zapier, Make, and n8n. Basic webhook for new incident creation.
*   **Maintenance Tracking**: Log maintenance activities for vehicles and equipment, and view maintenance alerts for items due or overdue.

This plugin aims to streamline operations, improve resource allocation, and provide valuable data insights for incident response organizations.

== Installation ==

1.  **Upload the Plugin**:
    *   Download the `incident-response-manager.zip` file.
    *   In your WordPress admin panel, go to "Plugins" > "Add New".
    *   Click on "Upload Plugin" at the top of the page.
    *   Click "Choose File", select the downloaded zip file, and click "Install Now".
    *   Alternatively, unzip the `incident-response-manager.zip` file and upload the `incident-response-manager` folder to the `/wp-content/plugins/` directory on your server.
2.  **Activate the Plugin**:
    *   Once uploaded/installed, go to "Plugins" in your WordPress admin panel.
    *   Find "Incident Response Manager" in the list and click "Activate".
3.  **Initial Setup**:
    *   **User Roles**: The plugin automatically creates the 'Firefighter', 'Captain', and 'Chief' roles with predefined capabilities upon activation. Assign these roles to your users as needed via the "Users" menu.
    *   **Shifts**: Navigate to "Shifts" in the admin menu to define your operational shifts (e.g., A Shift, B Shift) with their start/end times and rotation patterns.
    *   **Personnel**: Go to "Personnel" to add your team members, assign them ranks, and link them to the shifts you've created.
    *   **Vehicles & Equipment**: Add your vehicles and equipment under their respective menu items, setting their initial statuses and maintenance details.
    *   **Webhook URL (Optional)**: If you plan to use the "new incident created" webhook, you'll need to set the webhook URL. Currently, this is done by updating a WordPress option (see `INTEGRATIONS.MD` for details). A settings page for this may be added in future versions.
4.  **Review Settings & Start Using**:
    *   Explore the "IRM Dashboard" for an overview.
    *   Start managing incidents, resources, and generating reports.

== Frequently Asked Questions ==

= How do I assign personnel or vehicles to an incident? =

When you are creating or editing an Incident, there will be meta boxes available labeled "Assign Personnel" and "Assign Vehicles". These are typically multi-select boxes where you can choose from your existing personnel and vehicle records.

= How does the "Personnel On Duty" widget on the dashboard work? =

The "Personnel On Duty" widget provides an *approximation*. It checks the current server time against the defined start and end times for each "Shift" post. If the current time falls within a shift's active hours, it counts the personnel assigned to that shift. This basic version does not account for complex multi-day rotation patterns (like 24/48 or Kelly schedules) or individual days off. For a more detailed view, refer to the "Duty Roster" page.

= What are the available user roles and their main permissions? =

*   **Firefighter**: Can view most data (incidents, personnel, vehicles, equipment, shifts). Can add logs to incidents (assuming `add_incident_logs` capability is correctly configured for their role and potentially based on assignment).
*   **Captain**: Can manage incidents (create, edit, update status, assign resources), view all data, and view reports. Can manage incident statuses. Can assign vehicle/equipment statuses.
*   **Chief**: Has full management capabilities over all plugin features including incidents, personnel, vehicles, equipment, shifts, all statuses, and reports. Can manage users.
*   **Administrator**: Has all capabilities of the Chief role plus standard WordPress admin privileges.

= How do I use the REST API for integrations (e.g., with Zapier, Make, n8n)? =

Please refer to the `INTEGRATIONS.MD` file included with the plugin for detailed instructions on:
*   Authenticating with the API using Application Passwords.
*   Setting up polling triggers for new or updated items (e.g., new incidents).
*   Creating actions (e.g., creating a new incident via the API).

= How do I set up the "New Incident Created" webhook? =

The plugin can send a webhook when a new incident is published. You need to configure the destination URL for this webhook. Currently, this is done by setting a WordPress option named `irm_webhook_url_new_incident`. Detailed instructions, including WP-CLI or PHP methods, are in `INTEGRATIONS.MD`.

= Where can I find detailed API endpoint documentation? =

Refer to the `API_DOCUMENTATION.md` file for a complete list of API endpoints, request/response formats, parameters, and permission requirements.

= How is maintenance tracked for vehicles and equipment? =

Each Vehicle and Equipment item has fields for "Last Maintenance Date" and "Next Scheduled Maintenance Date". Additionally, there's a "Maintenance Log" section (similar to incident logs) where you can add timestamped entries about specific maintenance activities. The "Maintenance Alerts" page (under the main "Incidents" menu for now, or accessible via Dashboard links) lists items due or overdue for maintenance.

== Screenshots ==

1.  **IRM Dashboard**: Depicting the overview widgets: Active Incidents chart, Personnel On Duty summary, Vehicle Status chart, Equipment Status chart, and Incidents Last 7 Days chart.
2.  **Incident Edit Screen**: Showing the main incident details, custom fields (type, location, datetime), status taxonomy, assigned personnel/vehicles meta boxes, and the incident log meta box.
3.  **Personnel Roster View**: The admin list table for Personnel, showing columns like Name, Rank, Assigned Shift.
4.  **Vehicle Inventory View**: The admin list table for Vehicles, showing key details and status.
5.  **Equipment Inventory View**: The admin list table for Equipment.
6.  **Shift Management**: The admin list table for Shifts, showing defined shifts.
7.  **Reports Page**: Showing the tabbed interface for different reports (Incident, Resource Utilization, Personnel Activity) and an example of a filter form and results table.
8.  **Maintenance Alerts Page**: Displaying vehicles/equipment due or overdue for maintenance.

== Changelog ==

= 1.0.0 - 2023-10-27 =
*   Initial release.
*   Includes core features for managing incidents, personnel, vehicles, equipment, and shifts.
*   Dashboard with data visualization.
*   Reporting module with CSV export.
*   Comprehensive REST API for all CPTs and functionalities.
*   Basic webhook support for new incident creation.
*   Role-based access control.

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin. Ensure all users are assigned appropriate IRM roles (Firefighter, Captain, Chief) after activation for correct access to features.
