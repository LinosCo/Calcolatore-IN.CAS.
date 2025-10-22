# AI Chatbot WordPress Plugin

## Overview

AI Chatbot is a powerful WordPress plugin that integrates OpenAI's Assistant API to provide intelligent, context-aware chat conversations on your website. It enhances user engagement, offers support, and can even capture leads. The plugin is designed with privacy and customization in mind.

## Features

*   **AI-Powered Conversations:** Leverages OpenAI's Assistant API (specifically designed for conversational AI) to provide natural and intelligent responses.
*   **Context-Aware Responses:** The chatbot can analyze the content of the page it's on (including title, description, main text snippets, headings, and links) to provide more relevant and informed answers.
*   **Rich Message Formatting Support:** Both user input and AI responses can be enhanced with Markdown-like formatting, which is then rendered as HTML in the chat widget:
    *   **Bold:** `**text**`
    *   **Italics:** `*text*` or `_text_`
    *   **Unordered Lists:** Lines starting with `*`, `-`, or `+` are converted to bulleted lists.
    *   **Ordered Lists:** Lines starting with `1.`, `2.`, etc., are converted to numbered lists (respecting the starting number).
    *   **Links:** Supports explicit Markdown links `[link text](URL)` and automatically converts standalone URLs into clickable links.
    *   **Code Blocks:** Multi-line code can be wrapped with ``` (triple backticks).
    *   **Inline Code:** Single words or phrases can be formatted as code using ` (single backtick).
    *   **Automatic Source Citation Removal:** Source citations like `[...]` or `【...】` sometimes included by AI models are automatically removed from the displayed messages for a cleaner chat experience.
*   **Intelligent Link Handling:** Links within chat messages are treated intelligently:
    *   Internal links (pointing to the same domain) open in the current browser tab (`_self`).
    *   External links open in a new browser tab (`_blank`) for a better user experience.
*   **Persistent Conversations (Client-Side):**
    *   Chat sessions, identified by an OpenAI Thread ID, are maintained as users navigate across different pages of your website.
    *   This persistence is achieved using browser `localStorage` and typically lasts for 24 hours, or until the user clears their local chat history via the "Clear Chat" button in the widget.
*   **Lead Generation:**
    *   Optionally capture leads directly through the chatbot interface.
    *   Customize lead form fields (name, email, phone, company, message).
    *   Set triggers for the lead form (e.g., after a certain number of messages, or when specific keywords are used).
    *   Receive email notifications for new leads.
    *   View and manage captured leads within the WordPress admin area, including export to CSV.
    *   Requires user consent via a customizable checkbox and privacy policy link. The consent status is stored with each lead.
*   **Self-Hosted Knowledge Base (RAG):**
    *   Index WordPress content and uploaded documents locally and serve them to the model through lightweight similarity search.
    *   Automatically chunks content, stores OpenAI embeddings in a custom table, and injects only the most relevant snippets into prompts.
    *   Upload `.txt`, `.md/.markdown`, or `.pdf` files directly from the Knowledge Base screen; re-indexing regenerates embeddings with a fresh snapshot ID.
    *   Configurable similarity threshold ensures the bot asks clarifying questions when confidence is low, keeping token usage in check.
*   **Chat History (Server-Side):**
    *   **Admin Configurable:** Site administrators can enable or disable the storage of chat conversations.
    *   **User Consent Required:** If enabled, users must explicitly consent to have their chat history stored. The consent message and opt-in label are customizable by the admin.
    *   **Secure Storage:** Consented chat history (user messages, AI responses, thread ID, user identifier, page URL, timestamp) is stored in a custom database table on your WordPress server.
    *   **Admin Interface:** Administrators can view, filter (by Thread ID or User Identifier), and delete chat history entries from the WordPress backend. Options include deleting specific filtered entries or clearing all chat history.
*   **Privacy Features & Controls:**
    *   **Lead Generation Consent:** Users must agree to a privacy statement (customizable label and link to privacy policy page) before submitting lead information. This consent status is recorded.
    *   **Chat History Consent:** Separate, explicit user consent is required for storing chat history server-side.
    *   **Customizable Privacy Messages:** Admins can customize the OpenAI processing notice shown in the chat, the lead consent label, and the chat history consent message and opt-in label.
    *   **Data Deletion:** Tools for admins to delete leads and chat history. Full data removal (settings, leads table, chat history table) upon plugin uninstallation.
*   **Customizable Appearance:**
    *   Set primary and secondary colors for the chat widget to match your site's branding.
    *   Customize chatbot name and icon.
    *   Define initial welcome messages and suggested questions to guide users.
*   **REST API & AJAX:** Uses WordPress REST API for sending messages and AJAX for lead submissions, ensuring modern and reliable communication.
*   **Analytics Dashboard:** Visualize chat activity directly in WordPress—daily chat volume, message distribution, top entry pages, and peak engagement hours are available without extra setup.

## Installation

1.  Download the plugin ZIP file.
2.  In your WordPress admin panel, go to **Plugins > Add New**.
3.  Click **Upload Plugin** at the top.
4.  Choose the downloaded ZIP file and click **Install Now**.
5.  After installation, click **Activate Plugin**.

## Configuration

Once activated, you can configure the AI Chatbot plugin by navigating to **AI Chatbot** in your WordPress admin menu. Key settings include:

*   **API Settings:**
    *   OpenAI API Key
    *   OpenAI Assistant ID
    *   Assistant configuration including instructions and runtime knowledge base controls.
*   **Appearance:**
    *   Chatbot Name & Icon
    *   Primary and Secondary Colors
    *   Widget Position
*   **Messages:**
    *   Welcome Message
    *   Suggested Questions
*   **Lead Generation:**
    *   Enable/Disable Lead Capture
    *   Lead Form Introduction Text
    *   Message Threshold / Trigger Words for lead form
    *   Configuration of form fields
    *   Notification Email
*   **Privacy Settings:**
    *   Privacy Policy URL (used in lead form and potentially other consent messages)
    *   OpenAI Processing Notice (displayed in chat widget)
    *   Lead Form Consent Label
*   **Chat History Settings:**
    *   Enable/Disable Chat History Storage
    *   Chat History Consent Message
    *   Chat History Consent Opt-in Label
*   **Knowledge Base (RAG):**
    *   Enable/Disable the local knowledge base and choose which post types to index.
    *   Upload or remove `.txt`, `.md`, or `.pdf` documents; trigger a rebuild after changes.
    *   Reindex existing WordPress content with a single click and monitor snapshot metadata.
*   **Analytics:**
    *   Review conversation metrics—total chats, messages per chat, busiest pages/hours—inside the **AI Chatbot → Analytics** admin page.
*   **Functionality Toggles:**
    *   Show/Hide Chatbot globally

Ensure you have a valid OpenAI API Key and an Assistant ID from your OpenAI account to enable chat functionality.

## Shortcode

The plugin supports a shortcode `[vac_chatbot]` which can be used to display the chatbot on specific pages or posts, potentially overriding some default position settings if attributes are used (though attribute usage is currently minimal). If the shortcode is present on a page, the default footer widget might not load to prevent duplication.

## Licensing

AI Chatbot is licensed under the GNU General Public License v2.0 or later.
See [license.txt](vac_chatbot/license.txt) for the full license text.

---

We hope you enjoy using the AI Chatbot plugin!
