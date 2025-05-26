# Incident Response Manager API Documentation

## Table of Contents
1.  [Base URL and Authentication](#base-url-and-authentication)
2.  [Versioning](#versioning)
3.  [Available Endpoints](#available-endpoints)
    *   [Incidents](#incidents)
    *   [Personnel](#personnel)
    *   [Vehicles](#vehicles)
    *   [Equipment](#equipment)
    *   [Shifts](#shifts)
    *   [Duty Roster](#duty-roster)
4.  [Data Models / Schemas](#data-models--schemas)
    *   [Incident Object](#incident-object)
    *   [Personnel Object](#personnel-object)
    *   [Vehicle Object](#vehicle-object)
    *   [Equipment Object](#equipment-object)
    *   [Shift Object](#shift-object)
    *   [Log Entry Object](#log-entry-object)
5.  [Common Error Responses](#common-error-responses)

---

## 1. Base URL and Authentication

### Base URL
The base URL for accessing the API is:
`https://yourdomain.com/wp-json/irm/v1/`

Replace `yourdomain.com` with your actual WordPress site domain.

### Authentication
*   **Cookie Authentication**: For users logged into your WordPress site, cookie authentication is used by default. Ensure the user has the necessary capabilities for the endpoint they are trying to access. WordPress REST API will automatically handle nonce verification for state-changing requests (POST, PUT, DELETE) if the request is made from a logged-in browser session or if the `X-WP-Nonce` header is correctly sent.
*   **External Services**: For external services or applications to interact with this API, more robust authentication methods like Application Passwords (available in WordPress core) or a JWT (JSON Web Tokens) plugin would be required. These methods are not implemented by this plugin itself but are standard ways to authenticate with the WordPress REST API.

---

## 2. Versioning
The API uses versioning to ensure that future updates do not break existing integrations. The current version is `v1`, indicated by `/v1/` in the URL path. If breaking changes are introduced in the future, the version number will be incremented (e.g., `/v2/`).

---

## 3. Available Endpoints

### Incidents

#### List Incidents
*   **Endpoint**: `GET /incidents`
*   **Description**: Retrieves a list of incidents.
*   **Permissions**: Requires `edit_incidents` capability (typically Captains, Chiefs, Administrators).
*   **Parameters (Query String)**:
    *   `per_page` (integer, optional, default: 10): Number of items to return per page.
    *   `page` (integer, optional, default: 1): Current page number.
    *   `orderby` (string, optional, default: 'date'): Field to order by (e.g., 'title', 'date', 'id').
    *   `order` (string, optional, default: 'DESC'): Order direction ('ASC' or 'DESC').
    *   `status` (string, optional): Filter by post status (e.g., 'publish', 'draft', 'trash').
    *   `incident_status` (string, optional): Filter by incident status taxonomy term slug (e.g., 'reported', 'closed').
    *   `date_before` (string, optional, format: YYYY-MM-DD): Retrieve incidents published before this date.
    *   `date_after` (string, optional, format: YYYY-MM-DD): Retrieve incidents published after this date.
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: Array of [Incident Objects](#incident-object). Includes `X-WP-Total` and `X-WP-TotalPages` headers for pagination.
*   **Example Request**: `GET /wp-json/irm/v1/incidents?incident_status=active&per_page=5`

#### Create an Incident
*   **Endpoint**: `POST /incidents`
*   **Description**: Creates a new incident.
*   **Permissions**: Requires `publish_incidents` capability (typically Captains, Chiefs, Administrators).
*   **Request Body (JSON)**: See [Incident Object](#incident-object) for field details.
    *   `title` (string, required): Title of the incident.
    *   `content` (string, optional): Detailed description.
    *   `status` (string, optional, default: 'publish'): Post status (e.g., 'publish', 'draft').
    *   `incident_type` (string, optional): Meta field for incident type.
    *   `location` (string, optional): Meta field for location.
    *   `datetime` (string, optional, format: YYYY-MM-DD HH:MM:SS): Meta field for date/time.
    *   `responding_units` (string, optional): Meta field for responding units text.
    *   `incident_status_id` (integer, optional): Term ID for the incident status taxonomy.
    *   `incident_status_slug` (string, optional): Slug for the incident status taxonomy (used if ID not provided, defaults to 'reported').
*   **Success Response**:
    *   **Status Code**: `201 Created`
    *   **Body**: The created [Incident Object](#incident-object).
*   **Example Request Body**:
    ```json
    {
        "title": "Structure Fire Elm Street",
        "content": "Reported structure fire at 123 Elm Street.",
        "incident_type": "Fire",
        "location": "123 Elm Street",
        "datetime": "2023-10-26 14:30:00",
        "incident_status_slug": "reported"
    }
    ```

#### Get a Single Incident
*   **Endpoint**: `GET /incidents/{id}`
*   **Description**: Retrieves a specific incident by its ID.
*   **Permissions**: Requires `read_incident` capability for the given incident ID.
*   **Parameters**:
    *   `id` (integer, required): The ID of the incident.
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: The [Incident Object](#incident-object).

#### Update an Incident
*   **Endpoint**: `PUT /incidents/{id}` or `PATCH /incidents/{id}`
*   **Description**: Updates an existing incident. `PUT` should send all fields, `PATCH` can send partial.
*   **Permissions**: Requires `edit_incident` capability for the given incident ID.
*   **Parameters**:
    *   `id` (integer, required): The ID of the incident.
*   **Request Body (JSON)**: Fields from the [Incident Object](#incident-object) to update.
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: The updated [Incident Object](#incident-object).

#### Delete an Incident
*   **Endpoint**: `DELETE /incidents/{id}`
*   **Description**: Deletes an incident (moves to trash).
*   **Permissions**: Requires `delete_incident` capability for the given incident ID.
*   **Parameters**:
    *   `id` (integer, required): The ID of the incident.
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: JSON object with a success message and the data of the trashed item.
    ```json
    {
        "message": "Incident trashed successfully.",
        "previous_data": { /* Incident Object before deletion */ }
    }
    ```

#### Get Incident Logs
*   **Endpoint**: `GET /incidents/{id}/logs`
*   **Description**: Retrieves activity logs (comments) for a specific incident.
*   **Permissions**: Requires `read_incident` capability for the given incident ID.
*   **Parameters**:
    *   `id` (integer, required): The ID of the incident.
    *   `per_page` (integer, optional, default: 10): Number of logs per page.
    *   `page` (integer, optional, default: 1): Page number.
    *   `order` (string, optional, default: 'DESC'): 'ASC' or 'DESC'.
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: Array of [Log Entry Objects](#log-entry-object). Includes `X-WP-Total` and `X-WP-TotalPages` headers.

#### Add an Incident Log
*   **Endpoint**: `POST /incidents/{id}/logs`
*   **Description**: Adds a new log entry (comment) to an incident.
*   **Permissions**: Requires `edit_incident` capability for the given incident ID.
*   **Parameters**:
    *   `id` (integer, required): The ID of the incident.
*   **Request Body (JSON)**:
    *   `log_entry` (string, required): The content of the log entry.
*   **Success Response**:
    *   **Status Code**: `201 Created`
    *   **Body**: The created [Log Entry Object](#log-entry-object).
*   **Example Request Body**:
    ```json
    {
        "log_entry": "Unit E1 on scene."
    }
    ```

#### Assign Personnel/Vehicles to Incident
*   **Endpoint**: `PUT /incidents/{id}/assign` (or `POST`)
*   **Description**: Assigns or updates personnel and/or vehicles assigned to an incident.
*   **Permissions**: Requires `edit_incident` capability for the given incident ID. (Internally also checks `assign_personnel_to_incidents` or `assign_vehicles_to_incidents` if those specific meta caps were strictly enforced, but `edit_incident` is the primary gate).
*   **Parameters**:
    *   `id` (integer, required): The ID of the incident.
*   **Request Body (JSON)**:
    *   `personnel_ids` (array of integers, optional): Array of Personnel post IDs.
    *   `vehicle_ids` (array of integers, optional): Array of Vehicle post IDs.
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: The updated [Incident Object](#incident-object) with assignment details.
*   **Example Request Body**:
    ```json
    {
        "personnel_ids": [10, 15],
        "vehicle_ids": [5, 8]
    }
    ```

---
### Personnel

Standard CRUD endpoints are available for Personnel.
*   `GET /personnel`: Lists personnel.
    *   Permissions: `edit_personnels`.
    *   Query Params: `per_page`, `page`, `orderby`, `order`, `rank` (string), `shift_id` (integer).
*   `POST /personnel`: Creates new personnel.
    *   Permissions: `publish_personnels`.
    *   Request Body: [Personnel Object fields](#personnel-object).
*   `GET /personnel/{id}`: Retrieves specific personnel.
    *   Permissions: `read_personnel` (for the specific ID) or `edit_personnels` (general).
*   `PUT /personnel/{id}`: Updates specific personnel.
    *   Permissions: `edit_personnel` (for the specific ID).
*   `DELETE /personnel/{id}`: Deletes specific personnel (moves to trash).
    *   Permissions: `delete_personnel` (for the specific ID).

#### Get Personnel's Assigned Shift
*   **Endpoint**: `GET /personnel/{id}/shifts`
*   **Description**: Retrieves the assigned shift details for a specific personnel member.
*   **Permissions**: `read_personnel` (for the specific ID) or `edit_personnels`.
*   **Parameters**:
    *   `id` (integer, required): The ID of the personnel.
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: The [Shift Object](#shift-object) if assigned, or an empty array/message if not.

---
### Vehicles

Standard CRUD endpoints are available for Vehicles.
*   `GET /vehicles`: Lists vehicles.
    *   Permissions: `edit_vehicles`.
    *   Query Params: `per_page`, `page`, `orderby`, `order`, `status` (post status), `vehicle_status` (taxonomy slug), `vehicle_type` (meta).
*   `POST /vehicles`: Creates a new vehicle.
    *   Permissions: `publish_vehicles`.
    *   Request Body: [Vehicle Object fields](#vehicle-object).
*   `GET /vehicles/{id}`: Retrieves a specific vehicle.
    *   Permissions: `read_vehicle` (for the specific ID) or `edit_vehicles`.
*   `PUT /vehicles/{id}`: Updates a specific vehicle.
    *   Permissions: `edit_vehicle` (for the specific ID).
*   `DELETE /vehicles/{id}`: Deletes a specific vehicle (moves to trash).
    *   Permissions: `delete_vehicle` (for the specific ID).

#### Vehicle Maintenance Logs
*   `GET /vehicles/{id}/logs`: Retrieves maintenance logs for a vehicle.
    *   Permissions: `edit_vehicle` (for the specific ID).
    *   Query Params: `per_page`, `page`, `order`.
    *   Response: Array of [Log Entry Objects](#log-entry-object) (comment type: `vehicle_maintenance_log`).
*   `POST /vehicles/{id}/logs`: Adds a new maintenance log to a vehicle.
    *   Permissions: `edit_vehicle` (for the specific ID).
    *   Request Body: `{ "log_entry": "Maintenance details..." }`.
    *   Response: Created [Log Entry Object](#log-entry-object).

---
### Equipment

Standard CRUD endpoints are available for Equipment.
*   `GET /equipment`: Lists equipment.
    *   Permissions: `edit_equipments`.
    *   Query Params: `per_page`, `page`, `orderby`, `order`, `status` (post status), `equipment_status` (taxonomy slug), `equipment_type` (meta), `location` (meta).
*   `POST /equipment`: Creates new equipment.
    *   Permissions: `publish_equipments`.
    *   Request Body: [Equipment Object fields](#equipment-object).
*   `GET /equipment/{id}`: Retrieves specific equipment.
    *   Permissions: `read_equipment` (for the specific ID) or `edit_equipments`.
*   `PUT /equipment/{id}`: Updates specific equipment.
    *   Permissions: `edit_equipment` (for the specific ID).
*   `DELETE /equipment/{id}`: Deletes specific equipment (moves to trash).
    *   Permissions: `delete_equipment` (for the specific ID).

#### Equipment Maintenance Logs
*   `GET /equipment/{id}/logs`: Retrieves maintenance logs for equipment.
    *   Permissions: `edit_equipment` (for the specific ID).
    *   Query Params: `per_page`, `page`, `order`.
    *   Response: Array of [Log Entry Objects](#log-entry-object) (comment type: `equipment_maintenance_log`).
*   `POST /equipment/{id}/logs`: Adds a new maintenance log to equipment.
    *   Permissions: `edit_equipment` (for the specific ID).
    *   Request Body: `{ "log_entry": "Maintenance details..." }`.
    *   Response: Created [Log Entry Object](#log-entry-object).

---
### Shifts

Standard CRUD endpoints are available for Shifts.
*   `GET /shifts`: Lists shifts.
    *   Permissions: `edit_shifts`.
    *   Query Params: `per_page`, `page`, `orderby`, `order`, `status` (post status).
*   `POST /shifts`: Creates a new shift.
    *   Permissions: `publish_shifts`.
    *   Request Body: [Shift Object fields](#shift-object).
*   `GET /shifts/{id}`: Retrieves a specific shift.
    *   Permissions: `read_shift` (for the specific ID) or `edit_shifts`.
*   `PUT /shifts/{id}`: Updates a specific shift.
    *   Permissions: `edit_shift` (for the specific ID).
*   `DELETE /shifts/{id}`: Deletes a specific shift (moves to trash).
    *   Permissions: `delete_shift` (for the specific ID).

---
### Duty Roster

#### Get Duty Roster
*   **Endpoint**: `GET /roster`
*   **Description**: Retrieves a list of personnel with their assigned shift information. This is a basic roster; advanced on-duty calculation based on complex rotation patterns is not fully implemented in this version.
*   **Permissions**: Requires `read_shifts` capability.
*   **Parameters (Query String)**:
    *   `shift_id` (integer, optional): Filter the roster by a specific Shift ID.
    *   `date` (string, optional, format: YYYY-MM-DD): The date for which to view the roster. (Currently, this influences the "on-duty approximation" but not the list of personnel itself if no advanced rotation logic is in place).
*   **Success Response**:
    *   **Status Code**: `200 OK`
    *   **Body**: Array of objects, each containing:
        *   `personnel`: The [Personnel Object](#personnel-object).
        *   `assigned_shift` (optional): The [Shift Object](#shift-object) if assigned, including a `currently_on_duty_approximation` boolean (basic check).
*   **Example Response Snippet**:
    ```json
    [
        {
            "personnel": { /* Personnel Object ... */ },
            "assigned_shift": {
                /* Shift Object ... */,
                "currently_on_duty_approximation": true 
            }
        },
        // ... more personnel
    ]
    ```

---

## 4. Data Models / Schemas

The `irm_prepare_item_for_response` function structures the data for CPTs. Common fields include:
*   `id` (integer): Post ID.
*   `title` (object): Contains `raw` and `rendered` title.
*   `content` (object): Contains `raw` and `rendered` content (post editor).
*   `date_created_gmt` (string, format: YYYY-MM-DDTHH:MM:SS): Creation date in GMT.
*   `date_modified_gmt` (string, format: YYYY-MM-DDTHH:MM:SS): Modification date in GMT.
*   `slug` (string): Post slug.
*   `link` (string): Permalink to the post.
*   `status` (string): Post status (e.g., 'publish', 'draft').
*   `meta` (object): Contains publicly exposed meta fields (see specific objects below).
*   `[taxonomy_slug]` (array of objects): For each registered public taxonomy, an array of term objects (`id`, `name`, `slug`).

### Incident Object
*   **Meta Fields (`meta`)**:
    *   `incident_type` (string)
    *   `location` (string)
    *   `datetime` (string, YYYY-MM-DD HH:MM:SS)
    *   `responding_units` (string)
    *   `assigned_personnel_ids` (array of integers)
    *   `assigned_vehicle_ids` (array of integers)
    *   `assigned_personnel_details` (array of objects: `{id, title, link}`)
    *   `assigned_vehicle_details` (array of objects: `{id, title, link}`)
*   **Taxonomies**:
    *   `incident_status`: Array of assigned incident status terms.

### Personnel Object
*   **Meta Fields (`meta`)**:
    *   `rank` (string)
    *   `assigned_shift_id` (integer)
    *   `qualifications` (string)
    *   `contact_information` (string)

### Vehicle Object
*   **Meta Fields (`meta`)**:
    *   `vehicle_type` (string)
    *   `call_sign` (string)
    *   `assigned_personnel` (string) - Text description of primary operator/crew.
    *   `maintenance_schedule` (string, YYYY-MM-DD) - Next scheduled maintenance.
    *   `last_maintenance_date` (string, YYYY-MM-DD)
*   **Taxonomies**:
    *   `vehicle_status`: Array of assigned vehicle status terms.

### Equipment Object
*   **Meta Fields (`meta`)**:
    *   `equipment_type` (string)
    *   `equipment_id` (string) - Custom equipment identifier.
    *   `location` (string)
    *   `maintenance_schedule` (string, YYYY-MM-DD) - Next scheduled maintenance.
    *   `last_maintenance_date` (string, YYYY-MM-DD)
*   **Taxonomies**:
    *   `equipment_status`: Array of assigned equipment status terms.

### Shift Object
*   **Meta Fields (`meta`)**:
    *   `shift_start_time` (string, HH:MM or HH:MM:SS)
    *   `shift_end_time` (string, HH:MM or HH:MM:SS)
*   **Content**: The `content.rendered` field contains the description of the shift rotation pattern.

### Log Entry Object (Comment)
Used for Incident Logs and Maintenance Logs (Vehicle/Equipment).
*   `log_id` (integer): Comment ID.
*   `author_name` (string): Name of the author who added the log.
*   `author_id` (integer): User ID of the author.
*   `content` (object): `{ "rendered": "Log content with HTML allowed by wp_kses_post" }`.
*   `date_gmt` (string, format: YYYY-MM-DDTHH:MM:SS): Date the log was added, in GMT.

---

## 5. Common Error Responses

The API uses standard HTTP status codes for errors.
*   **`400 Bad Request`**: The request was malformed, such as missing a required parameter or invalid data format.
    *   Example: `{"code":"rest_invalid_param","message":"Invalid parameter(s): datetime","data":{"status":400,"params":{"datetime":"datetime is not a valid datetime format (YYYY-MM-DD HH:MM:SS)."}}}`
*   **`401 Unauthorized`**: Authentication is required and has failed or has not yet been provided.
    *   Example: `{"code":"rest_not_logged_in","message":"You are not currently logged in.","data":{"status":401}}`
*   **`403 Forbidden`**: The authenticated user does not have permission to perform the requested action.
    *   Example: `{"code":"rest_forbidden","message":"Sorry, you are not allowed to do that.","data":{"status":403}}`
*   **`404 Not Found`**: The requested resource (e.g., a specific incident or endpoint) does not exist.
    *   Example: `{"code":"rest_post_invalid_id","message":"Incident not found.","data":{"status":404}}`
*   **`500 Internal Server Error`**: An unexpected error occurred on the server.
    *   Example: `{"code":"log_creation_failed","message":"Failed to add log entry.","data":{"status":500}}`

Error responses typically include:
*   `code` (string): A machine-readable error code (e.g., `rest_forbidden`).
*   `message` (string): A human-readable error message.
*   `data` (object):
    *   `status` (integer): The HTTP status code.
    *   `params` (object, optional): Details about invalid parameters for 400 errors.

---
This documentation should provide a good overview for developers looking to interact with the Incident Response Manager API.
