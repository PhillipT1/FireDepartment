# Integrating with Zapier, Make (Integromat), and n8n

This document provides guidance on how to connect third-party automation services like Zapier, Make, and n8n to the Incident Response Manager (IRM) plugin's REST API.

## Table of Contents
1.  [Authentication: Using Application Passwords](#authentication)
2.  [Working with Triggers (Polling for New/Updated Data)](#triggers)
    *   [Example: Trigger for "New Incident"](#example-trigger-new-incident)
    *   [Example: Trigger for "Updated Incident"](#example-trigger-updated-incident)
3.  [Working with Actions (Creating/Updating Data)](#actions)
    *   [Example: Action for "Create Incident"](#example-action-create-incident)
    *   [Example: Action for "Add Log to Incident"](#example-action-add-log-to-incident)
4.  [Basic Webhook Support (New Incident Creation)](#webhook-support)
    *   [Setting up the Webhook URL](#setting-up-webhook-url)
    *   [Webhook Payload](#webhook-payload)
5.  [General Tips for Integration](#general-tips)

---

## 1. Authentication

The recommended method for authenticating external services with the WordPress REST API (and thus the IRM plugin's API) is by using **Application Passwords**.

### Generating an Application Password:
1.  **Enable Application Passwords**: If your WordPress site does not already support Application Passwords (it's a core feature since WordPress 5.6), you might not need to do anything extra. If you have security plugins, ensure they are not blocking this feature or the REST API.
2.  **User Profile**: Log in to your WordPress admin dashboard with a user account that has the necessary capabilities for the API actions you want to perform (e.g., a Chief or Administrator role for full access).
3.  **Go to Your Profile**: Navigate to "Users" -> "Your Profile".
4.  **Application Passwords Section**: Scroll down to the "Application Passwords" section.
5.  **Create a New Application Password**:
    *   Enter a name for the application (e.g., "Zapier IRM Integration", "Make IRM"). This name is for your reference.
    *   Click the "Add New Application Password" button.
6.  **Copy the Password**: A new password will be generated and displayed **only once**. Copy this password immediately and store it securely (e.g., in your automation platform's connection settings). You will not be able to see this password again.
    *   The password will look something like: `abcd efgh ijkl mnop qrst uvwx` (with spaces). Include the spaces when using it.

### Using the Application Password in Your Automation Service:
*   **Authentication Type**: When setting up your connection in Zapier, Make, or n8n, choose "Basic Auth" or "API Key" if a dedicated WordPress integration isn't available that supports Application Passwords directly.
*   **Username**: Use the WordPress username of the user who generated the Application Password.
*   **Password**: Use the generated Application Password (the one you copied, including spaces).

Services like Zapier and Make often have dedicated WordPress integrations that might prompt you for your site URL, username, and Application Password.

---

## 2. Working with Triggers (Polling for New/Updated Data)

Automation platforms typically use polling to check for new or updated items. This involves periodically making a `GET` request to a list endpoint. To make this efficient, use date-based filters and sorting.

### Key Parameters for Polling:
*   `orderby=modified`: Sorts items by their last modification time. This is crucial for finding recently updated items.
*   `order=ASC` or `order=DESC`: Use `ASC` with `modified_after` to get the oldest updates first, or `DESC` to get the newest. For "New Item since last check" triggers, `ASC` with `modified_after` is typical.
*   `modified_after=YYYY-MM-DDTHH:MM:SS` (ISO8601 format) or `YYYY-MM-DD HH:MM:SS`: Retrieves items modified after the specified GMT/UTC timestamp. Your automation service should store the timestamp of the last retrieved item and use it in the next poll.
*   `per_page=N`: Limits the number of items returned (e.g., `per_page=10`). Start with a small number for polling.

### Example: Trigger for "New Incident"
This trigger checks for incidents created since the last check.

1.  **Platform Setup (e.g., Zapier "New Item in Feed" or "New Item via API Call")**:
    *   **API Endpoint URL**: `https://yourdomain.com/wp-json/irm/v1/incidents`
    *   **HTTP Method**: `GET`
    *   **Authentication**: As described in [Authentication](#authentication).
    *   **Query Parameters**:
        *   `orderby=date` (or `id` if creation order is strictly by ID)
        *   `order=ASC`
        *   `date_after={{last_successful_poll_timestamp}}` (or a similar dynamic variable provided by the platform, representing the `post_date_gmt` of the last seen incident). Alternatively, if checking for *any* new item, you might sort by `id` and `order=DESC` and process only items with an ID greater than the last seen ID. For creation, `date` (which is `post_date`) is often used.
        *   `per_page=5` (or a suitable small number)
2.  **Deduplication**: The automation platform usually handles deduplication based on a unique ID (e.g., the incident `id`).

### Example: Trigger for "Updated Incident"
This trigger checks for incidents modified since the last check.

1.  **Platform Setup**:
    *   **API Endpoint URL**: `https://yourdomain.com/wp-json/irm/v1/incidents`
    *   **HTTP Method**: `GET`
    *   **Authentication**: As described.
    *   **Query Parameters**:
        *   `orderby=modified`
        *   `order=ASC`
        *   `modified_after={{last_successful_poll_timestamp_for_modified}}` (use a GMT/UTC timestamp in `YYYY-MM-DD HH:MM:SS` format, representing the `post_modified_gmt` of the last seen updated incident).
        *   `per_page=5`
2.  **Deduplication**: Based on incident `id`. The platform will need to track which version of an item it has processed if multiple updates occur between polls.

**Note on Timestamps**: Ensure you are using GMT/UTC timestamps for `modified_after` and `date_after` as WordPress stores dates in GMT (`post_date_gmt`, `post_modified_gmt`). The API endpoints are designed to query against these GMT fields.

---

## 3. Working with Actions (Creating/Updating Data)

Actions involve making `POST`, `PUT`, or `DELETE` requests to the API.

### Example: Action for "Create Incident"

1.  **Platform Setup (e.g., Zapier "Create Item via API Call")**:
    *   **API Endpoint URL**: `https://yourdomain.com/wp-json/irm/v1/incidents`
    *   **HTTP Method**: `POST`
    *   **Authentication**: As described.
    *   **Request Body (JSON)**: Map data from previous steps in your automation to the fields of the [Incident Object](#incident-object-api-documentationmd).
        ```json
        {
            "title": "Automated: Power Line Down",
            "content": "Details from external alert system...",
            "incident_type": "Utility Hazard",
            "location": "Corner of Main St & 1st Ave",
            "datetime": "2023-10-27 10:00:00", 
            "incident_status_slug": "reported" 
        }
        ```
        *Note on `datetime`*: Ensure the format is `YYYY-MM-DD HH:MM:SS`.
        *Note on `status` vs `incident_status_slug`*: `status` refers to the post status (publish, draft), `incident_status_slug` refers to the taxonomy term for incident status.

### Example: Action for "Add Log to Incident"

1.  **Platform Setup**:
    *   **API Endpoint URL**: `https://yourdomain.com/wp-json/irm/v1/incidents/{incident_id}/logs`
        *   Replace `{incident_id}` with the actual ID of the incident (likely from a previous step in your automation).
    *   **HTTP Method**: `POST`
    *   **Authentication**: As described.
    *   **Request Body (JSON)**:
        ```json
        {
            "log_entry": "Automated update: External system reported progress."
        }
        ```

---

## 4. Basic Webhook Support (New Incident Creation)

The IRM plugin has basic support for sending an outgoing webhook when a new incident is created and published. This can be more efficient than polling for some use cases.

### Setting up the Webhook URL:
Currently, there isn't a dedicated settings page within the plugin to configure this webhook URL. It needs to be set in the WordPress options table.
*   **Option Name**: `irm_webhook_url_new_incident`
*   **How to Set (Example using WP-CLI)**:
    ```bash
    wp option update irm_webhook_url_new_incident 'https://your-zapier-make-n8n-webhook-url.com/listen' --format=json
    ```
*   **How to Set (Example using PHP in a theme's `functions.php` or a custom snippet - run once)**:
    ```php
    // Run this code snippet once, e.g., via a temporary theme functions.php addition or a custom plugin.
    // update_option('irm_webhook_url_new_incident', 'https://your-zapier-make-n8n-webhook-url.com/listen');
    ```
    Replace `https://your-zapier-make-n8n-webhook-url.com/listen` with the actual webhook URL provided by Zapier, Make, n8n, or your custom service.

### Webhook Payload:
When a new incident is published, the plugin will send a `POST` request to the configured URL with a JSON payload containing the incident data. The payload structure will be similar to the [Incident Object](#incident-object-api-documentationmd) returned by the GET `/incidents/{id}` endpoint.

**Example Payload Sent by Webhook:**
```json
{
    "id": 123,
    "title": {"raw": "New Incident Title", "rendered": "New Incident Title"},
    "content": {"raw": "Incident details...", "rendered": "<p>Incident details...</p>"},
    "date_created_gmt": "2023-10-27T14:30:00",
    "date_modified_gmt": "2023-10-27T14:30:00",
    "slug": "new-incident-title",
    "link": "http://yourdomain.com/incident/new-incident-title/",
    "status": "publish",
    "meta": {
        "incident_type": "Type Value",
        "location": "Location Value",
        "datetime": "2023-10-27 14:30:00",
        // ... other meta fields
    },
    "incident_status": [ {"id": 1, "name": "Reported", "slug": "reported"} ],
    // ... other taxonomy data
}
```

Your automation service should be configured to listen for this POST request at its webhook URL and parse the JSON payload.

---

## 5. General Tips for Integration

*   **Start Simple**: Begin with a basic trigger or action to ensure the connection and authentication are working correctly.
*   **Check API Documentation**: Refer to the main `API_DOCUMENTATION.md` for detailed information on all available endpoints, parameters, and response structures.
*   **Error Handling**: Pay attention to HTTP status codes and error messages returned by the API to troubleshoot issues.
*   **Rate Limiting**: While the IRM plugin itself doesn't impose rate limits, your web server or WordPress security plugins might. Be mindful of how frequently your automations poll the API. Webhooks are generally better for high-frequency updates.
*   **Testing**: Use tools like Postman or Insomnia to manually test API requests before building complex automations. This helps in understanding the request/response flow.
*   **Security**: Always use Application Passwords generated for specific users with the minimum necessary capabilities. Regularly review and revoke unused Application Passwords.

This guide should help you get started with integrating the Incident Response Manager plugin with various automation platforms.
