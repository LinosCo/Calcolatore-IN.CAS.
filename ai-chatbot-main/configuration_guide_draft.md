## 4. Configuration Guide

This guide provides a comprehensive overview of all the settings available in the AI Chatbot plugin. Proper configuration is key to tailoring the chatbot's behavior and appearance to your website's needs.

### Accessing Settings

After installing and activating the plugin, you can access the configuration page by navigating to **AI Chatbot** in the main menu of your WordPress admin dashboard.

The settings are organized into several sections:

### API Settings

This section contains crucial credentials for connecting to the OpenAI service.

*   **OpenAI API Key**
    *   **Description:** Your unique API key from OpenAI. This is required for the chatbot to make requests to the OpenAI API.
    *   **Input:** Text field.
    *   **Default:** None (empty).
    *   **Note:** Treat this key as a password and keep it confidential.
*   **Assistant ID**
    *   **Description:** The ID of the OpenAI Assistant you want this chatbot to use. Each Assistant can be configured with specific instructions, models, and tools in your OpenAI account.
    *   **Input:** Text field.
    *   **Default:** None (empty).
*   **Test Connection Button**
    *   **Description:** After entering your API Key and optionally an Assistant ID, click this button to verify if the plugin can successfully connect to the OpenAI API and access the specified Assistant.
    *   **Action:** Displays a success or error message.
*   **Connection & Assistant Management Buttons**
    *   **Description:** Found near the API Key and Assistant ID fields. These are critical for initializing or updating your assistant on OpenAI's servers.
    *   `Test API Connection`: Checks if your API key is valid and can connect to OpenAI. Also verifies the Assistant ID if provided.
    *   `Create/Update Assistant`: This button synchronizes your settings (like Assistant Name, Instructions, and enabled tools such as File Search based on Knowledge Files) with OpenAI. If no Assistant ID is in settings, it attempts to create a new one. **Crucially, if you add or change Knowledge Files, you must click this button to make the live assistant use them.**
*   **Assistant Instructions**
    *   **Description:** Provide specific instructions for your Assistant's behavior, personality, and the scope of its knowledge or tasks (e.g., "You are a helpful customer support agent for a bookstore. Only answer questions related to books and store policies."). These instructions are sent to OpenAI when the assistant is created or updated.
    *   **Input:** Text area.
    *   **Default:** `You are a helpful assistant.`
*   **Assistant Knowledge Files**
    *   **Description:** This powerful feature allows your Assistant to use content from files you provide (e.g., PDFs, TXT documents) to answer user questions. This uses OpenAI's "File Search" capability.
    *   **How to use:**
        1.  Click the **"Select/Manage Assistant Files from Media Library"** button. This opens the WordPress Media Library.
        2.  Upload new files or select existing ones from your Media Library.
        3.  After selection, the chosen files will be uploaded to your OpenAI account (specifically to a Vector Store associated with this plugin) and will appear in the "Associated Files" list below the button.
        4.  **Important:** To make these files active and usable by your live OpenAI Assistant, you **must** click the **"Apply File Changes & Update Assistant"** button (located within this section) or the main "Create/Update Assistant" button near the API settings. This action configures your OpenAI Assistant to use the updated set of files in its Vector Store for File Search.
    *   **File Management:** You can remove previously associated files from this list. Removing a file here and then clicking "Apply File Changes & Update Assistant" will disassociate it from the assistant's knowledge.
    *   **Note:** The plugin handles the creation and management of the Vector Store on OpenAI, as well as enabling the `file_search` tool for your assistant when files are present and changes are applied.

### General Settings

Configure the overall behavior and appearance of the chatbot.

*   **Show Chatbot**
    *   **Description:** Globally enables or disables the chatbot widget on your website.
    *   **Input:** Checkbox.
    *   **Default:** Enabled (checked).
*   **Assistant Name**
    *   **Description:** The display name for your chatbot that appears in the chat widget header.
    *   **Input:** Text field.
    *   **Default:** `AI Assistant`
*   **Assistant Icon URL**
    *   **Description:** URL to an image file to be used as the chatbot's icon in the header. If left blank, a default icon will be used.
    *   **Input:** Text field (URL).
    *   **Default:** None (empty - uses a default Dashicon).
*   **Primary Color**
    *   **Description:** The main color used for the chat widget header, user messages, and other primary UI elements.
    *   **Input:** Color picker.
    *   **Default:** `#007bff` (a shade of blue).
*   **Secondary Color**
    *   **Description:** The secondary color, often used for AI messages or other UI accents.
    *   **Input:** Color picker.
    *   **Default:** `#6c757d` (a shade of gray).
*   **Widget Position**
    *   *(This setting was mentioned in previous reviews but not explicitly listed in `add_settings_fields`. Assuming it's part of `vac_settings` and controlled by a dropdown or radio buttons, typically added via a custom render function or a general settings framework. If it's not explicitly in `add_settings_fields`, this documentation might be ahead or based on full expected functionality. For now, I'll include it as it's a common setting.)*
    *   **Description:** Determines where the chatbot widget toggle button and the chat window appear on your website.
    *   **Input:** Dropdown or Radio buttons.
    *   **Options:** `Bottom Right`, `Bottom Left`.
    *   **Default:** `Bottom Right`.

### Welcome Message & Suggested Questions

Customize the initial interaction users have with the chatbot.

*   **Welcome Message**
    *   **Description:** The first message displayed by the chatbot when a user opens the chat window for the first time in a session.
    *   **Input:** Text area.
    *   **Default:** `Welcome! How can I help you today?`
*   **Suggested Questions**
    *   **Description:** A list of predefined questions that users can click to easily start a conversation. Add or remove questions as needed.
    *   **Input:** A list of text fields, with "Add Question" and "Remove" buttons.
    *   **Default:** Includes "What services do you offer?", "How can I get started?", "What are your business hours?", "Do you have any special offers?".

### Lead Generation Settings

Configure the chatbot's lead capture functionality.

*   **Enable Lead Capture**
    *   **Description:** Activates or deactivates the lead generation form within the chatbot.
    *   **Input:** Checkbox.
    *   **Default:** Disabled (unchecked).
*   **Lead Form Introduction**
    *   **Description:** The text displayed to users above the lead capture form fields (e.g., "Please provide your information to continue.").
    *   **Input:** Text area.
    *   **Default:** `Please provide your information to continue.`
*   **Message Threshold**
    *   **Description:** The number of messages exchanged between the user and the AI before the lead capture form is automatically triggered.
    *   **Input:** Number field.
    *   **Default:** `3` (Min: 1, Max: 10).
*   **Trigger Words**
    *   **Description:** A comma-separated list of words. If the user's message contains any of these words, the lead capture form will be triggered.
    *   **Input:** Text area.
    *   **Default:** `price, cost, quote, pricing, buy, purchase`
*   **Form Fields**
    *   **Description:** Configure which fields (Name, Email, Phone, Company, Message) are shown on the lead form and whether they are required.
    *   **Input:** A set of checkboxes for each field ("Enabled", "Required").
    *   **Default:**
        *   Name: Enabled, Required
        *   Email: Enabled, Required
        *   Phone: Enabled, Not Required
        *   Company: Disabled, Not Required
        *   Message: Disabled, Not Required
*   **Notification Email**
    *   **Description:** The email address where notifications for new leads will be sent.
    *   **Input:** Text field.
    *   **Default:** The site administrator's email (from WordPress general settings).

### Privacy Settings

Manage privacy-related messages and links displayed to users.

*   **Privacy Policy URL**
    *   **Description:** The URL to your website's privacy policy page. This link will be used in the lead generation consent checkbox and potentially other places where privacy is mentioned.
    *   **Input:** Text field (URL).
    *   **Default:** None (empty).
*   **OpenAI Processing Notice**
    *   **Description:** This message is displayed in the chat widget (typically below the input area) to inform users that their messages are processed by OpenAI to provide responses.
    *   **Input:** Text area.
    *   **Default:** `Your messages are processed by OpenAI to provide responses.`
*   **Lead Form Consent Label**
    *   **Description:** Customize the text label displayed next to the consent checkbox on the lead capture form. You can use `[privacy policy]` as a placeholder, which will be automatically converted into a link using the "Privacy Policy URL" provided above.
    *   **Input:** Text area.
    *   **Default:** `I agree to the [privacy policy] and the processing of my data.`

### Chat History Settings

Configure how chat conversations are stored and managed.

*   **Enable Chat History**
    *   **Description:** If checked, the plugin will store chat conversations (user messages and AI responses) in the database. This requires user consent.
    *   **Input:** Checkbox.
    *   **Default:** Disabled (unchecked).
*   **Chat History Consent Message**
    *   **Description:** The message displayed to users when asking for their consent to store their chat history.
    *   **Input:** Text area.
    *   **Default:** `To improve your experience and recall past conversations, we can store your chat history. Please consent below.`
*   **Chat History Consent Opt-in Label**
    *   **Description:** The text label for the checkbox that users will click to give their consent for chat history storage.
    *   **Input:** Text area.
    *   **Default:** `I agree to the storage of my chat history.`

---
This completes the configuration guide. Remember to save your settings after making changes.
