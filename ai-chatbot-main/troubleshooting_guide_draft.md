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

### 6. Knowledge Files / File Search Not Working

If you've uploaded files to be used as knowledge by the assistant, but it doesn't seem to be using them:

*   **"Apply File Changes & Update Assistant" Button:**
    *   After adding files via the Media Library and seeing them in the "Associated Files" list in **AI Chatbot > Settings** (under "Assistant Knowledge Files"), you **must** click the **"Apply File Changes & Update Assistant"** button.
    *   This step is critical because it tells your OpenAI Assistant to use the updated Vector Store containing your files. Without this, the assistant is unaware of the new knowledge.
*   **File Processing Time (OpenAI):**
    *   When files are added to a Vector Store, OpenAI needs time to process them (index, create embeddings, etc.). This can take a few moments to several minutes depending on file size and OpenAI's current load.
    *   You can check the status of files within your Vector Store directly on the OpenAI platform (under Storage > Vector Stores). Ensure the files show as "Completed" or "Active".
*   **Assistant Configuration on OpenAI:**
    *   The plugin attempts to enable the `file_search` tool on your assistant when you apply file changes. Verify in your OpenAI Assistants dashboard that the relevant assistant has the "File Search" tool enabled and is associated with the correct Vector Store ID (the ID is visible in the plugin settings if a Vector Store has been created/used).
*   **Query Phrasing:**
    *   Ensure your test questions are specific enough that the assistant *should* consult the uploaded documents. Vague questions might not trigger file search.
*   **Plugin Settings Cache:**
    *   Although recent fixes address this, if you suspect very old data is being used, try saving the main plugin settings page again (even without changes) and then re-clicking "Apply File Changes & Update Assistant".
*   **Correct Files Associated:**
    *   Double-check in the "Associated Files" list in the plugin settings that the files you expect are indeed listed there.

### 7. Messages Not Sending/Receiving (General Errors)

For general issues with message sending or receiving:

*   **Browser Console:** Always check the browser's developer console (JavaScript console and Network tab) for errors first. This is often the quickest way to identify client-side issues or failed API requests.
*   **PHP Error Logs:** Check your server's PHP error logs for any errors originating from the plugin files. WordPress debug mode (`WP_DEBUG` and `WP_DEBUG_LOG`) can be helpful here.
*   **OpenAI API Status:** Occasionally, the OpenAI API itself might experience downtime or performance degradation. Check the [OpenAI status page](https://status.openai.com/) for any ongoing incidents.
*   **Network Connectivity:** Ensure your website server has stable internet connectivity to reach the OpenAI API.

If you continue to experience issues, consider seeking support from the plugin author or your website developer. Providing specific error messages and steps to reproduce the problem will be very helpful.
