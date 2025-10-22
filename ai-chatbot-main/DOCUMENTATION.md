# Voler AI Chatbot WordPress Plugin - User & Developer Guide

## Table of Contents
1.  [Introduction](#1-introduction)
2.  [Features Overview](#2-features-overview)
3.  [Installation Guide](#3-installation-guide)
    *   [Minimum Requirements](#minimum-requirements)
    *   [Installing via WordPress Admin](#installing-via-wordpress-admin-recommended)
    *   [Installing via FTP](#installing-via-ftp-alternative-method)
    *   [Initial Activation](#initial-activation)
4.  [Configuration Guide](#4-configuration-guide)
    *   [Accessing Settings](#accessing-settings)
    *   [API Settings](#api-settings)
    *   [General Settings](#general-settings)
    *   [Welcome Message & Suggested Questions](#welcome-message--suggested-questions)
    *   [Lead Generation Settings](#lead-generation-settings)
    *   [Privacy Settings](#privacy-settings)
    *   [Chat History Settings](#chat-history-settings)
    *   [Knowledge Base (RAG) Settings](#knowledge-base-rag-settings)
5.  [Using the Chatbot](#5-using-the-chatbot)
    *   [Frontend Widget](#frontend-widget)
    *   [Lead Form Interaction](#lead-form-interaction)
    *   [Chat History Consent](#chat-history-consent)
6.  [Managing Captured Data](#6-managing-captured-data)
    *   [Viewing Leads](#viewing-leads)
    *   [Exporting Leads to CSV](#exporting-leads-to-csv)
    *   [Viewing Chat History](#viewing-chat-history)
    *   [Exporting Chat History to CSV](#exporting-chat-history-to-csv)
    *   [Deleting Leads and Chat History](#deleting-leads-and-chat-history)
    *   [Analytics Dashboard](#analytics-dashboard)
7.  [Shortcode Reference](#7-shortcode-reference)
    *   [`[vac_chatbot]`](#vac_chatbot)
    *   [Accepted Attributes](#accepted-attributes)
8.  [Developer Information](#8-developer-information)
    *   [Hooks (Actions & Filters)](#hooks-actions--filters)
    *   [Custom CSS](#custom-css)
9.  [Troubleshooting Guide](#9-troubleshooting-guide)
10. [FAQ](#10-faq-frequently-asked-questions)
11. [Licensing](#11-licensing)
12. [Support](#12-support)

---

## 1. Introduction

Welcome to the Voler AI Chatbot WordPress Plugin! This plugin allows you to integrate a powerful, OpenAI-driven chatbot onto your website. Engage with your visitors, provide instant support, answer questions with context-awareness, and even capture leads seamlessly. This guide will help you install, configure, and make the most of the Voler AI Chatbot plugin.

---

## 2. Features Overview

The Voler AI Chatbot plugin is packed with features to enhance user interaction on your WordPress site. Key functionalities include:

*   Intelligent conversations powered by OpenAI Assistants.
*   Context-aware responses enriched with on-page content plus a self-hosted retrieval-augmented knowledge base.
*   Rich message formatting (Markdown support).
*   Persistent chat sessions for users.
*   Customizable lead generation forms and triggers.
*   Optional server-side chat history storage with user consent.
*   Admin interface to view and manage leads and chat history.
*   Comprehensive privacy controls and consent mechanisms.
*   Easy customization of appearance and default messages.
*   Self-hosted Knowledge Base (RAG) with automatic chunking, embeddings, and similarity search against WordPress content or uploaded `.txt`, `.md`, and `.pdf` documents.
*   Built-in analytics dashboard summarizing chat activity, message volume, and top-performing pages.

For a detailed list of features, please also refer to the main `README.md` file included with the plugin.

---

## 3. Installation Guide

This guide will walk you through installing the Voler AI Chatbot plugin on your WordPress website, enabling you to integrate powerful AI-driven conversations.

### Minimum Requirements

To ensure the Voler AI Chatbot plugin functions correctly, please make sure your WordPress environment meets the following minimum requirements:

*   **WordPress version:** 5.8 or higher
*   **PHP version:** 7.4 or higher
*   **Database:** MySQL 5.6 or higher / MariaDB 10.1 or higher
*   **OpenAI Account:** A valid OpenAI API Key and an Assistant ID are required for the chat functionality to connect to OpenAI's services.

### Installing via WordPress Admin (Recommended)

This is the easiest and most common method for installing WordPress plugins.

1.  **Download Plugin**: If you have received the Voler AI Chatbot plugin as a ZIP file (e.g., from a marketplace or direct download), save it to your computer.
2.  **Navigate to Plugins**: In your WordPress admin dashboard, go to **Plugins > Add New**.
3.  **Upload Plugin**: Click the **Upload Plugin** button, usually found at the top of the "Add Plugins" page.
4.  **Choose File**: Click the **Choose File** button, then locate and select the plugin ZIP file (`voler-ai-chatbot.zip` or similar) that you downloaded.
5.  **Install Now**: Click the **Install Now** button. WordPress will upload the plugin file and install it.
6.  **Activate Plugin**: After the installation is complete, you will see a success message. Click the **Activate Plugin** button to enable Voler AI Chatbot on your site.

### Installing via FTP (Alternative Method)

If you prefer to install plugins manually or if your server has restrictions on uploads via the WordPress admin, you can use FTP.

1.  **Download and Extract**: Download the plugin ZIP file (if applicable) and extract its contents on your computer. This will create a folder named `voler-ai-chatbot` (or similar).
2.  **Connect via FTP**: Use an FTP client (such as FileZilla, Cyberduck, or Transmit) to connect to your WordPress website's server.
3.  **Upload Folder**: Navigate to the `wp-content/plugins/` directory on your server. Upload the entire `voler-ai-chatbot` folder (extracted in step 1) into this directory.
4.  **Activate Plugin**: Go to your WordPress admin dashboard, then navigate to **Plugins > Installed Plugins**. You should see "Voler AI Chatbot" in the list of installed plugins. Click the **Activate** link below its name.

### Initial Activation

Upon first activation, the Voler AI Chatbot plugin automatically performs several setup tasks to ensure it's ready for use:

*   **Database Tables**: It creates the necessary custom database tables used for storing captured leads (`wp_vac_leads`) and, if the feature is enabled, chat conversation history (`wp_vac_chat_history`).
*   **Default Settings**: It initializes a set of default settings. These can be customized later.

Once activated, you can find the plugin's configuration page by navigating to the **Voler AI Chatbot** menu item in your WordPress admin panel. It's recommended to visit the settings page to enter your OpenAI API Key and Assistant ID, and to configure other features according to your needs.

---

## 4. Configuration Guide

This guide provides a comprehensive overview of all the settings available in the Voler AI Chatbot plugin. Proper configuration is key to tailoring the chatbot's behavior and appearance to your website's needs.

### Accessing Settings

After installing and activating the plugin, you can access the configuration page by navigating to **Voler AI Chatbot** in the main menu of your WordPress admin dashboard.

The settings are organized into several sections:

### API Settings

This section contains crucial credentials for connecting to the OpenAI service.

*   **OpenAI API Key**
    *   **Description:** Your unique API key from OpenAI. This is required for the chatbot to make requests to the OpenAI API. You can create and find your API keys at the <a href="https://platform.openai.com/account/api-keys" target="_blank" rel="noopener noreferrer">OpenAI API Keys page</a>.
    *   **Input:** Text field.
    *   **Default:** None (empty).
    *   **Note:** Treat this key as a password and keep it confidential.
*   **Assistant ID**
    *   **Description:** The ID of the OpenAI Assistant you want this chatbot to use. Each Assistant can be configured with specific instructions, models, and tools in your OpenAI account. You can create and manage your Assistants at the <a href="https://platform.openai.com/assistants" target="_blank" rel="noopener noreferrer">OpenAI Assistants page</a>.
    *   **Input:** Text field.
    *   **Default:** None (empty).
*   **Test Connection Button**
    *   **Description:** After entering your API Key and optionally an Assistant ID, click this button to verify if the plugin can successfully connect to the OpenAI API and access the specified Assistant.
    *   **Action:** Displays a success or error message.
*   **Connection & Assistant Management**
    *   **Description:** This area includes the "Test API Connection" button and the "Create/Update Assistant" button for managing the remote OpenAI assistant configuration.
    *   **Buttons:**
        *   `Test API Connection`: Verifies API key and Assistant ID (if provided).
        *   `Create/Update Assistant`: Creates a new assistant if no ID is provided or updates the existing one with the current instructions and tool configuration.
*   **Assistant Instructions**
    *   **Description:** Define the general instructions, role, and tone for your assistant (e.g., "You are a helpful assistant specializing in sailing."). This is part of the data sent when creating/updating the assistant.
    *   **Input:** Text area.
    *   **Default:** `You are a helpful assistant.`
*   **Knowledge Base Controls**
    *   **Description:** Toggles the local retrieval-augmented knowledge base and surfaces the most recent snapshot information. Actual indexing tasks (uploading documents, reindexing content) are handled from the dedicated **Knowledge Base** tab described later in this guide.
    *   **Note:** When the knowledge base is enabled, the chatbot automatically embeds user queries, retrieves the best-matching chunks, and injects only the highest-similarity snippets into prompts. If confidence is low, the bot will ask users for clarification instead of guessing.

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
*   **Widget Position** (*Note: This setting is managed via `vac_params` passed to JavaScript; ensure it's listed if a UI option exists or clarify if it's only via shortcode/filter.*)
    *   **Description:** Determines where the chatbot widget toggle button and the chat window appear on your website.
    *   **Input:** Typically a Dropdown or Radio buttons (e.g., in a dedicated settings field if available).
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
    *   **Description:** URL to your privacy policy (e.g., `https://yourwebsite.com/privacy-policy`). This will be linked in the lead form and chat widget where the `[privacy policy]` placeholder is used.
    *   **Input:** Text field (URL).
    *   **Default:** None (empty).
*   **OpenAI Processing Notice**
    *   **Description:** This message is displayed in the chat widget (typically below the input area) to inform users that their messages are processed by OpenAI to provide responses.
    *   **Input:** Text area.
    *   **Default:** `Your messages are processed by OpenAI to provide responses.`
*   **Lead Form Consent Label**
    *   **Description:** Customize the consent label displayed next to the checkbox on the lead capture form. Use `[privacy policy]` (including brackets) to insert a link to your Privacy Policy URL specified above.
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

### Knowledge Base (RAG) Settings

Fine-tune the retrieval-augmented knowledge base that keeps responses grounded in your own content.

*   **Enable Knowledge Base**
    *   **Description:** Master toggle for local embedding lookup. When switched off, user prompts go directly to the model without extra context.
    *   **Input:** Checkbox.
*   **Content Post Types**
    *   **Description:** Select which WordPress post types should be indexed. Adjust this list before reindexing to include new content sources (e.g., WooCommerce products).
    *   **Input:** Multi-select checklist.
*   **Reindex Content**
    *   **Description:** Clears existing knowledge rows for the selected sources, re-syncs posts, and schedules the embedding rebuild job. Use after large content edits.
    *   **Action:** Button; progress completes asynchronously.
*   **Upload Knowledge Files**
    *   **Description:** Accepts `.txt`, `.md/.markdown`, and `.pdf` files. The plugin extracts text, stores it in the knowledge table, and triggers a rebuild so the new material is chunked and embedded.
    *   **UI:** Drag-and-drop area + table of uploaded files (with download/remove actions and metadata such as size and upload date).
*   **Similarity Threshold**
    *   **Description:** Optional numeric field that governs how strict the cosine similarity filter is when selecting chunks. Higher values mean the bot is more likely to ask for clarification.
*   **Snapshot Info**
    *   **Description:** Displays the most recent snapshot ID, generation timestamp, embedding model, and chunk count—useful for verifying a rebuild completed.

---
This completes the configuration guide. Remember to save your settings after making changes by clicking the "Save Changes" button at the bottom of the settings page, and run the Knowledge Base rebuild whenever you upload new documents or publish significant content updates.

---

## 5. Using the Chatbot

This section describes how users interact with the AI Chatbot on the frontend of your website.

### Frontend Widget

*   The chatbot typically appears as a small toggle button, usually in the bottom-right or bottom-left corner of the screen, based on your configuration.
*   Clicking this button opens the chat window.
*   The chat window displays a welcome message and any suggested questions you've configured.
*   Users can type their messages into the input field and press Enter or click the send button.
*   The conversation history within the current session is displayed in the message area.
*   AI responses are formatted to support rich text like bold, italics, lists, links, and code snippets.

### Lead Form Interaction

*   If Lead Generation is enabled and triggered (either by message count or keywords), the standard message input area will be replaced by the lead capture form.
*   The form will display the introduction text and the fields (Name, Email, Phone, etc.) you've enabled.
*   Users must fill out any required fields and check the privacy consent checkbox to submit the form.
*   After successful submission, a thank you message is shown, and the chat input area typically reappears.
*   Users can also choose to "Skip" the lead form if that option is available.

### Chat History Consent

*   If Chat History storage is enabled in the admin settings, a consent UI will appear in the chat widget before the user starts interacting significantly or when the chat is first opened (if no prior consent decision is stored).
*   This UI will display a message (customizable by the admin) explaining that chat history can be stored and will provide a checkbox for the user to opt-in.
*   Users can agree by checking the box or decline using a "No, thanks" button.
*   Once a choice is made, it's remembered for the session (in browser localStorage), and the consent UI is hidden.
*   If consent is given, subsequent messages in that thread (both user and AI) will be saved to the database.

---

## 6. Managing Captured Data

The AI Chatbot plugin provides admin interfaces for viewing and managing the data it captures.

### Viewing Leads

*   Navigate to **AI Chatbot > Leads** in your WordPress admin panel.
*   This page displays a table of all captured leads with columns for Name, Email, Phone, Page URL, Captured At, Privacy Consent status, and Consent Date.
*   You can sort leads by most columns.
*   Basic pagination is available if you have many leads.

### Exporting Leads to CSV

*   On the **AI Chatbot > Leads** page, click the **"Export Leads to CSV"** button.
*   This will generate and download a CSV file containing all lead data, suitable for import into CRM systems or spreadsheets.

### Viewing Chat History

*   Navigate to **AI Chatbot > Chat History** in your WordPress admin panel.
*   This page displays a table of all stored chat messages (where user consent was given).
*   Columns include ID, Thread ID, User Identifier (anonymous ID), Sender (user/assistant), Message Content (truncated), Page Context URL, and Timestamp.
*   You can sort history by most columns.
*   Pagination is available.
*   You can filter the history by **Thread ID** or **User Identifier** using the provided filter fields.

### Exporting Chat History to CSV

*   On the **AI Chatbot > Chat History** page, click the **"Export Chat History to CSV"** button.
*   This will generate and download a CSV file containing all consented chat history entries.

### Deleting Leads and Chat History

*   **Individual Leads:** On the **AI Chatbot > Leads** page, each lead row has a "Delete" link. Clicking this (after a confirmation) will permanently remove the lead.
*   **Chat History:**
    *   **Delete Filtered History:** On the **AI Chatbot > Chat History** page, you can enter a Thread ID or User Identifier and click "Delete Matching History" to remove specific conversations or all messages from a particular anonymous user. This action requires confirmation.
    *   **Clear All Chat History:** A "Clear All Chat History" button is available on the Chat History page. Clicking this (after a confirmation) will permanently delete *all* stored chat messages from the database.
*   All deletion actions require appropriate admin permissions and include nonce protection.

### Analytics Dashboard

*   Navigate to **AI Chatbot > Analytics** to review aggregate chat metrics without leaving WordPress.
*   The summary cards highlight total chats, total messages, average messages per chat, and the number of pages that initiated at least one conversation.
*   Additional tables chart:
    *   **Chats per Day (last 14 days)** showing unique threads vs. total messages.
    *   **Messages per Chat Distribution** to spot unusually short or long conversations.
    *   **Top Entry Pages** ranked by chat count and message volume.
    *   **Peak Hours** (based on recent activity) to identify when visitors engage most.
*   Use these insights to tune prompts, schedule staffing, or decide which content needs deeper knowledge base coverage.

---

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

---

## 8. Developer Information

### Hooks (Actions & Filters)

The AI Chatbot plugin is built to be highly configurable through its extensive admin settings panel, allowing for a wide range of customizations to suit your needs.

Currently, the AI Chatbot plugin does not expose a dedicated set of custom PHP action (`do_action()`) or filter (`apply_filters()`) hooks specifically intended for third-party developers to significantly extend or modify its core PHP behavior beyond the available settings. The plugin integrates with WordPress and operates using standard WordPress core hooks for functionalities such as adding administrative menus, enqueueing scripts and styles, processing AJAX requests, and registering shortcodes.

### Custom CSS

The visual appearance of the chat widget is controlled by CSS. You can override the plugin's default styles by targeting its specific CSS classes (most are prefixed with `vac-` or `vac-chat-`) in your active theme's stylesheet (e.g., `style.css`) or through the WordPress Customizer's "Additional CSS" feature. This allows for significant visual integration with your site's design.

**Feature Requests & Future Development:**

We are open to feedback and potential future enhancements. If you have specific requirements for extensibility points, such as particular PHP hooks or filters that would be beneficial for your use case, please consider reaching out to the plugin author at [voler.ai](https://voler.ai) with your suggestions. Future versions of the plugin may incorporate such developer-oriented features based on demand and common needs.

For most common customization needs, we encourage you to first explore the comprehensive settings available within the **AI Chatbot** admin panel.

---

## 9. Troubleshooting Guide

This guide provides solutions to common issues you might encounter while using the AI Chatbot plugin.

### 1. Chat Widget Not Appearing

If the chatbot widget (the toggle button or the chat window itself) is not visible on your website, try these steps:

*   **Check Global Setting:**
    *   Navigate to **AI Chatbot > Settings > General Settings** in your WordPress admin panel.
    *   Ensure the **"Show Chatbot"** checkbox is enabled. If this is disabled, the chatbot will not appear unless a shortcode is used.
*   **Theme Conflict:**
    *   Temporarily switch to a default WordPress theme (e.g., Twenty Twenty-Four, Twenty Twenty-Three). If the widget appears, your original theme might be causing a conflict (e.g., missing `wp_footer()` hook, or CSS/JS conflicts).
*   **JavaScript Errors:**
    *   Open your browser's developer console (usually by pressing F12, then selecting the "Console" tab).
    *   Look for any JavaScript errors. Errors from other plugins or your theme can prevent the chatbot's JavaScript from running correctly.
*   **Shortcode Usage:**
    *   If you are using the `[vac_chatbot]` shortcode to display the widget on a specific page, ensure the shortcode is correctly entered in the page/post content editor.
    *   Verify that the plugin's logic to hide the default footer widget when a shortcode is present is functioning as expected (it usually should).

### 2. Cannot Connect to OpenAI / API Errors

If the chatbot is visible but cannot send messages, or you see API-related errors:

*   **Verify API Credentials:**
    *   Go to **AI Chatbot > Settings > API Settings**.
    *   Double-check that your **"OpenAI API Key"** is correctly entered and is active (has available funds/credits in your OpenAI account).
    *   Ensure the **"Assistant ID"** is correct and corresponds to an existing Assistant in your OpenAI dashboard.
*   **Test Connection:**
    *   Use the **"Test Connection"** button in the API Settings section of the plugin. This will help diagnose if the credentials are valid and if your server can reach OpenAI.
*   **Server Outbound Requests:**
    *   Confirm that your web server can make outbound HTTPS requests (specifically to `api.openai.com`). Some hosting providers might restrict this. You may need to contact your host or, if you have server access, perform a `curl https://api.openai.com/v1/models -H "Authorization: Bearer YOUR_API_KEY"` test from the command line.
*   **Error Messages:**
    *   Look for specific error messages displayed in the chat widget itself or in the browser's developer console (Network tab, check the response from `send-message` or `admin-ajax.php` calls). These messages often provide clues from OpenAI.
*   **Plugin Logs:**
    *   Check your server's PHP error logs and the plugin's own logs (if logging is enabled and configured) for more detailed error information from the `AICB_API` class.

### 3. Lead Form Issues

If the lead generation form is not working as expected:

*   **Check Enable Setting:**
    *   Go to **AI Chatbot > Settings > Lead Generation Settings**.
    *   Make sure **"Enable Lead Capture"** is checked.
*   **Form Configuration:**
    *   Verify that the fields you expect to see are enabled in the "Form Fields" configuration.
    *   If you have set fields as "Required", ensure they are being filled out completely before submission.
*   **JavaScript Errors:**
    *   Check the browser console for JavaScript errors that might prevent form submission logic from running.
*   **AJAX Errors:**
    *   When submitting the form, open your browser's developer console and switch to the "Network" tab.
    *   Look for the `admin-ajax.php` request. Check its status code and the response. Any errors here can indicate server-side problems with the submission.
    *   The `handle_save_lead` method in `class-vac-frontend.php` now includes diagnostic logging; check your PHP error logs for `[VAC handle_save_lead]` entries.
*   **Spam Filters:**
    *   Rarely, other security or spam-filtering plugins might interfere with AJAX submissions. If you suspect this, try temporarily disabling other relevant plugins to see if the issue resolves.

### 4. Chat History Not Saving

If chat history is enabled but conversations are not being saved:

*   **Check Enable Setting:**
    *   Go to **AI Chatbot > Settings > Chat History Settings**.
    *   Ensure **"Enable Chat History"** is checked.
*   **User Consent:**
    *   The user must explicitly consent to chat history storage via the checkbox presented in the chat widget (if the consent UI is shown). If consent is not 'given' (stored in `localStorage`), history will not be saved.
*   **Database/PHP Errors:**
    *   Check your server's PHP error logs for any database errors related to inserting data into the `wp_vac_chat_history` table. The `VAC_Activator.php` file now includes more robust table creation and logging. Ensure the table was created successfully during plugin activation/update.
    *   The `VAC_Chat_History_Manager::save_message()` method also logs errors if database insertion fails.

### 5. Styling Conflicts / Chat Widget Looks Incorrect

If the chat widget's appearance is distorted or doesn't match the plugin's intended style:

*   **Theme/Plugin CSS Overrides:**
    *   Your active theme or another plugin might have aggressive CSS rules that are overriding the chatbot's styles.
*   **Developer Tools:**
    *   Use your browser's developer tools (Inspector/Elements tab) to right-click on the problematic part of the chat widget and "Inspect Element".
    *   Examine the applied CSS rules to identify which styles are taking precedence and where they originate from.
*   **CSS Specificity:**
    *   To fix this, you may need to write more specific CSS rules. You can add these to your theme's `style.css` file, a child theme's stylesheet, or the "Additional CSS" section in the WordPress Customizer.
    *   Target the plugin's elements using their specific classes (most are prefixed with `vac-`).

### 6. Messages Not Sending/Receiving (General Errors)

For general issues with message sending or receiving:

*   **Browser Console:** Always check the browser's developer console (JavaScript console and Network tab) for errors first. This is often the quickest way to identify client-side issues or failed API requests.
*   **PHP Error Logs:** Check your server's PHP error logs for any errors originating from the plugin files. WordPress debug mode (`WP_DEBUG` and `WP_DEBUG_LOG`) can be helpful here.
*   **OpenAI API Status:** Occasionally, the OpenAI API itself might experience downtime or performance degradation. Check the [OpenAI status page](https://status.openai.com/) for any ongoing incidents.
*   **Network Connectivity:** Ensure your website server has stable internet connectivity to reach the OpenAI API.

If you continue to experience issues, consider seeking support from the plugin author or your website developer. Providing specific error messages and steps to reproduce the problem will be very helpful.

---

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

**4. Is the AI Chatbot plugin compatible with [Specific Theme/Page Builder/Multilingual Plugin]?**

*   **General Compatibility:** The AI Chatbot plugin is built following WordPress coding standards and best practices. It should be compatible with most well-coded themes and plugins.
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

---

## 11. Licensing

AI Chatbot is licensed under the GNU General Public License v2.0 or later.
See [license.txt](vac_chatbot/license.txt) for the full license text.

---

## 12. Support

For support, please contact [support@voler.ai](mailto:support@voler.ai).
(Please replace with the actual support channel/link if different).
