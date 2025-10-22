## 7. Shortcode Reference

The Voler AI Chatbot plugin includes a convenient shortcode, `[vac_chatbot]`, which allows you to embed the chatbot directly into the content of specific pages, posts, or custom post types. This provides greater flexibility over where and how the chatbot appears, potentially overriding some of the global display settings for that specific instance.

### `[vac_chatbot]`

This is the primary shortcode for the Voler AI Chatbot plugin.

**Basic Usage:**

To display the chatbot using all the global settings configured in the **Voler AI Chatbot** admin panel, simply insert the following shortcode into your page or post content editor:

```
[vac_chatbot]
```

**Behavior Notes:**

*   If the chatbot is globally enabled to show on all pages (via the "Show Chatbot" setting), using this shortcode on a specific page will typically not result in duplicate chatbot instances. The plugin is designed to detect the presence of the shortcode and will usually prevent the default footer-rendered widget from loading on that same page.
*   The shortcode is useful if you have the global "Show Chatbot" setting disabled but want to display the chatbot only on select pages.

**Accepted Attributes:**

You can customize the appearance and behavior of a specific chatbot instance rendered by the shortcode by adding attributes. These attributes will override the corresponding global settings for that instance only.

*   **`position`**
    *   **Description**: Overrides the default global position of the chatbot widget for this specific instance.
    *   **Accepted Values**:
        *   `bottom-right`
        *   `bottom-left`
    *   **Default**: If not set, uses the position configured in **Voler AI Chatbot > Settings > General Settings**.
    *   **Example**: `[vac_chatbot position="bottom-left"]`

*   **`primary_color`**
    *   **Description**: Overrides the global primary color for this specific chatbot instance. This affects elements like the chat header and user message bubbles.
    *   **Accepted Values**: Any valid HEX color code (e.g., `#A020F0` for purple).
    *   **Default**: If not set, uses the primary color from **Voler AI Chatbot > Settings > General Settings**.
    *   **Example**: `[vac_chatbot primary_color="#A020F0"]`

*   **`secondary_color`**
    *   **Description**: Overrides the global secondary color for this specific chatbot instance. This affects elements like the AI's message bubbles.
    *   **Accepted Values**: Any valid HEX color code (e.g., `#F0A020` for orange).
    *   **Default**: If not set, uses the secondary color from **Voler AI Chatbot > Settings > General Settings**.
    *   **Example**: `[vac_chatbot secondary_color="#F0A020"]`

*   **`name`**
    *   **Description**: Overrides the chatbot's display name shown in the header for this specific instance.
    *   **Accepted Values**: Any text string (e.g., "Support Bot", "Product Helper").
    *   **Default**: If not set, uses the "Assistant Name" from **Voler AI Chatbot > Settings > General Settings**.
    *   **Example**: `[vac_chatbot name="Specific Page Bot"]`

**Combined Example:**

You can combine multiple attributes to customize an embedded chatbot instance extensively:

```
[vac_chatbot position="bottom-left" name="Customer Service AI" primary_color="#123456" secondary_color="#ABCDEF"]
```

This example would render the chatbot on the bottom-left of the page where the shortcode is placed. It would be titled "Customer Service AI", and use the specified custom primary and secondary colors. Other settings not overridden by attributes (like the welcome message, suggested questions, etc.) will still be inherited from the global plugin settings.
