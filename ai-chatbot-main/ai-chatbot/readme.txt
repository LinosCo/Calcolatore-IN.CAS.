=== AI Chatbot ===
Contributors: voler.ai
Tags: ai, chatbot, openai, gpt, assistant
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrates OpenAI's Assistant API for intelligent, context-aware chat conversations on your website.

== Description ==

AI Chatbot is a powerful WordPress plugin that leverages OpenAI's Assistant API to bring intelligent, context-aware chat conversations to your website. It's designed to enhance user engagement, offer instant support, answer questions by understanding page content, and optionally capture leads. The plugin is built with privacy, customization, and ease of use in mind.

**Key Features:**

*   **AI-Powered Conversations:** Utilizes OpenAI's Assistant API for natural and intelligent responses.
*   **Context-Awareness:** Analyzes current page content (title, description, text snippets, headings, links) for relevant answers.
*   **Rich Message Formatting:** Supports Markdown for bold, italics, lists, links, and code blocks in chat.
*   **Intelligent Link Handling:** Internal links open in the same tab, external links in a new tab.
*   **Persistent Client-Side Conversations:** Maintains chat sessions across page navigation using `localStorage` (typically for 24 hours or until cleared by user).
*   **Lead Generation:** Optional feature to capture leads with customizable forms, triggers (message count, keywords), email notifications, and admin management (view/export leads). Requires user consent.
*   **Server-Side Chat History:** Optional, consent-based storage of chat conversations in your WordPress database. Admins can view, filter, and delete history.
*   **Privacy Controls:** Customizable consent mechanisms for lead generation and chat history, configurable privacy notices.
*   **Customizable Appearance:** Set colors, chatbot name, and icon. Define welcome messages and suggested questions.
*   **REST API & AJAX:** Modern communication for message handling and lead submissions.
*   **Knowledge Files (File Search):** Upload documents (PDFs, TXT, etc.) via WordPress Media Library. The assistant can then use these files as a knowledge base (requires clicking "Apply File Changes & Update Assistant" in settings).
*   **Shortcode Support:** Use `[ai-chatbot]` to embed the chatbot on specific pages.

== Installation ==

1.  Upload the `ai-chatbot` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Navigate to the "AI Chatbot" menu in your WordPress admin panel to configure the settings. You will need to enter your OpenAI API Key and Assistant ID.

For more detailed installation instructions, please refer to the included documentation.

== Frequently Asked Questions ==

= How do I get an OpenAI API Key and Assistant ID? =

You need an OpenAI account.
*   **API Key:** Create or find your API keys at [platform.openai.com/account/api-keys](https://platform.openai.com/account/api-keys).
*   **Assistant ID:** Create and manage your Assistants at [platform.openai.com/assistants](https://platform.openai.com/assistants). The ID (e.g., `asst_xxxxxxxxxxxxxxx`) is needed for the plugin.

= What are the costs associated with using the OpenAI API? =

The plugin itself is licensed under GPLv2 or later. However, OpenAI API usage incurs costs based on your usage (tokens, model, features). Please refer to [openai.com/pricing](https://openai.com/pricing) for details. You are responsible for these costs.

= How is user privacy handled? =

The plugin includes features for privacy:
*   **Lead Generation Consent:** Requires user agreement with a customizable label and privacy policy link.
*   **Chat History Consent:** Explicit user consent is needed for server-side storage of chats.
*   **Data Storage:** Leads and consented chat history are stored in your WordPress database.
*   **OpenAI Processing:** Users are informed that messages are processed by OpenAI.
*   It's recommended to have a comprehensive privacy policy on your site.

= Is the plugin compatible with my theme/other plugins? =

The plugin follows WordPress standards and should be compatible with most well-coded themes and plugins. If you encounter issues, test with a default theme and try deactivating other plugins to identify conflicts. The plugin is translation-ready and aims to support multilingual sites.

= How can I translate the plugin? =

The plugin uses the text domain `ai-chatbot`. A `.pot` file is included in the `languages/` directory. Use tools like Poedit or Loco Translate to create `.po` and `.mo` files (e.g., `ai-chatbot-es_ES.po`) and place them in the plugin's `languages/` folder or `wp-content/languages/plugins/`.

= How do I add custom knowledge/documents for the Assistant to use? =

The plugin allows you to upload files (PDFs, TXT, etc.) via the WordPress Media Library. These files become a knowledge source for your Assistant using OpenAI's "File Search" capability.
1.  In the plugin settings (**AI Chatbot > Settings**), find the "Assistant Knowledge Files" section.
2.  Click "Select/Manage Assistant Files from Media Library" to upload or choose files.
3.  **Crucially**, after files are listed, click the **"Apply File Changes & Update Assistant"** button. This links the files (via a Vector Store) to your live OpenAI Assistant. Without this step, the assistant won't use the files.

= Why are my uploaded knowledge files not being used? =

Ensure you've clicked the **"Apply File Changes & Update Assistant"** button in the plugin settings after managing your files. Also, allow some time for OpenAI to process the files in the Vector Store. You can check file status in your OpenAI account.

== Screenshots ==

1.  Chatbot widget on the frontend.
2.  Admin settings page - General Settings.
3.  Admin settings page - Lead Generation.
4.  Admin settings page - Unified Consent Screen.
5.  Leads management table.
6.  Chat history management table.

(These are descriptions; actual screenshot files would be added to the ThemeForest submission package, typically in an `assets` directory at the WordPress.org plugin root if submitting there, or as part of the item preview on ThemeForest).

== Changelog ==

= 1.0.0 - YYYY-MM-DD =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release of the AI Chatbot plugin.

(Further sections like "Support" can be added if desired, but this covers the basics for a readme.txt)
