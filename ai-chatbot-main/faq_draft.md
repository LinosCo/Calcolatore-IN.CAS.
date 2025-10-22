## 10. FAQ (Frequently Asked Questions)

Here are answers to some common questions about the AI Chatbot plugin.

**1. How do I get an OpenAI API Key and Assistant ID?**

To use the AI Chatbot plugin, you'll need credentials from OpenAI:

*   **OpenAI Account:** First, ensure you have an account with OpenAI. You can sign up at [platform.openai.com](https://platform.openai.com/).
*   **API Key:**
    *   Once logged in, you can find or create your API keys in the API Keys section of your OpenAI account dashboard: [platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).
    *   Treat your API key like a password; keep it secure and do not share it publicly.
*   **Assistant ID:**
    *   The chatbot uses an "Assistant" that you configure within your OpenAI account. Assistants allow you to define specific instructions, choose a model (e.g., GPT-4, GPT-3.5-turbo), and enable tools like Code Interpreter or Knowledge Retrieval.
    *   You can create and manage your Assistants via the OpenAI Assistants dashboard: [platform.openai.com/assistants](https://platform.openai.com/assistants).
    *   After creating an Assistant, you will get an Assistant ID (e.g., `asst_xxxxxxxxxxxxxxx`), which you need to enter into the plugin settings.

**2. What are the costs associated with using the OpenAI API?**

*   The AI Chatbot plugin itself is licensed under the terms specified in its `license.txt` file (GNU General Public License v2.0 or later).
*   However, using the OpenAI API to power the chat functionality **will incur costs** from OpenAI. These costs are based on your usage, such as the number of tokens processed (both input and output), the specific model used by your Assistant, and any additional features like Code Interpreter or function calling.
*   It is crucial to understand OpenAI's pricing structure. For detailed and up-to-date information, please refer to the official OpenAI pricing page: [openai.com/pricing](https://openai.com/pricing).
*   You are responsible for monitoring and managing your own OpenAI API usage and associated costs through your OpenAI account dashboard.

**3. How is user privacy handled by the plugin?**

The AI Chatbot plugin has been developed with user privacy in mind:

*   **Lead Generation Consent:** If you enable the lead generation feature, users are required to give consent before their information is submitted. The consent checkbox label can be customized, and you can link to your website's privacy policy page directly from the consent area. The status of this consent is stored with each lead.
*   **Chat History Consent:** The plugin offers optional server-side storage of chat conversations. This feature is disabled by default and, if enabled by the site administrator, requires *explicit user consent* before any chat messages are stored on your server. The consent message and opt-in label are customizable.
*   **Data Storage:**
    *   Captured leads (including consent status) are stored in a custom database table (`wp_vac_leads`) within your own WordPress database.
    *   Consented chat history is stored in a separate custom database table (`wp_vac_chat_history`) within your WordPress database.
    *   Plugin settings (including your API key) are stored in the standard WordPress options table.
*   **OpenAI Processing:** User messages and relevant page context are sent to OpenAI's API for processing to generate responses. This is essential for the chatbot's functionality. Users are informed of this via a configurable notice in the chat widget.
*   **Your Privacy Policy:** It is highly recommended that you maintain a comprehensive privacy policy on your website that details how user data is collected, used, stored, and shared, including its use by third-party services like OpenAI via this plugin. You can link to this policy from the plugin's settings.

**4. Is the Voler AI Chatbot plugin compatible with [Specific Theme/Page Builder/Multilingual Plugin]?**

*   **General Compatibility:** The Voler AI Chatbot plugin is built following WordPress coding standards and best practices. It should be compatible with most well-coded themes and plugins.
*   **Themes & Page Builders:** While we aim for broad compatibility, highly complex themes or page builders that heavily modify default WordPress behavior or JavaScript handling might occasionally cause conflicts. We recommend testing the chatbot thoroughly after installation, especially its display and JavaScript functionality. If you encounter issues, try temporarily switching to a default WordPress theme to determine if the conflict originates from your theme.
*   **Multilingual Plugins (e.g., WPML, TranslatePress, Polylang):**
    *   The plugin's admin panel strings and user-facing strings in the chat widget are **translation-ready**. The text domain used is `vac_chatbot`. You can create your own translations (see FAQ #5).
    *   The plugin also includes a feature to instruct the AI to respond in the current language of your website (based on `get_locale()`). This helps provide a more consistent multilingual experience.

**5. How can I translate the plugin?**

The Voler AI Chatbot plugin is prepared for translation into other languages.

*   **Text Domain:** The text domain used throughout the plugin is `vac_chatbot`.
*   **POT File:** A `.pot` (Portable Object Template) file will be included in the plugin's `languages/` directory. This file serves as a template for creating new translations.
*   **Translation Process:**
    1.  You can use a plugin like [Loco Translate](https://wordpress.org/plugins/loco-translate/) directly within your WordPress admin to create and manage translations.
    2.  Alternatively, you can use desktop software like [Poedit](https://poedit.net/) to translate the strings from the `.pot` file.
    3.  When you save your translations, Poedit (or Loco Translate) will generate a `.po` file (human-readable) and a `.mo` file (machine-readable).
*   **File Naming:** The `.po` and `.mo` files must be named according to your language and country code. For example, for Spanish (Spain), the files would be `vac_chatbot-es_ES.po` and `vac_chatbot-es_ES.mo`.
*   **Location:** Place your completed `.mo` (and `.po`) files in the plugin's `languages/` directory (e.g., `wp-content/plugins/vac_chatbot/languages/`). WordPress will automatically load the translation if the site language matches. Alternatively, you can place them in `wp-content/languages/plugins/`.

If you create a translation, we encourage you to share it with the plugin author or community!

**6. How do I add custom knowledge/documents for the Assistant to use?**

*   The plugin allows you to upload files (like PDFs, TXT documents, etc.) to provide your OpenAI Assistant with custom knowledge. This uses OpenAI's "File Search" feature.
*   **Steps:**
    1.  Go to **AI Chatbot > Settings** in your WordPress admin panel.
    2.  In the "API Settings" or "General Settings" section, find the **"Assistant Knowledge Files"** area.
    3.  Click the **"Select/Manage Assistant Files from Media Library"** button. This will open your WordPress Media Library.
    4.  Upload or select the document(s) you want the assistant to use.
    5.  The selected files will appear in the "Associated Files" list.
    6.  **Crucial Step:** After you have added or removed files from the list, you **must** click the **"Apply File Changes & Update Assistant"** button (usually found in the same "Assistant Knowledge Files" section or near the main "Create/Update Assistant" button). This action updates your OpenAI Assistant's configuration to use the new set of files through its associated Vector Store.
*   **How it works:** The plugin uploads these files to OpenAI and manages them in a Vector Store. When you click "Apply File Changes & Update Assistant," the plugin ensures your Assistant is set up to use this Vector Store for `file_search`.
*   **Note:** Ensure your Assistant on the OpenAI platform is compatible with the `file_search` tool (the plugin attempts to configure this for you).

**7. Why aren't my uploaded knowledge files being used by the Assistant?**

*   **"Apply Changes" Not Clicked:** The most common reason is forgetting to click the **"Apply File Changes & Update Assistant"** button after adding/removing files in the WordPress admin settings. This step is essential to link the updated file set (via the Vector Store) to your live OpenAI Assistant.
*   **File Processing:** After a file is added to a Vector Store via OpenAI, it needs to be processed by OpenAI (chunked, embedded, indexed). This can take a short amount of time, especially for large files. If you test immediately, the file might not be ready. Check the file status in your Vector Store on the OpenAI platform.
*   **Assistant Instructions:** Ensure your Assistant's general instructions don't contradict or prevent it from using file search. Usually, this is not an issue unless explicitly instructed to ignore documents.
*   **Correct Assistant ID:** Double-check that the Assistant ID in the plugin settings is the one you intend to use with these knowledge files.
