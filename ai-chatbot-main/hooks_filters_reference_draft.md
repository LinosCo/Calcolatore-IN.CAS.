## 8. Developer Information

### Hooks & Filters (Actions & Filters)

The AI Chatbot plugin is designed to be highly configurable through its extensive admin settings panel, providing a wide array of options to tailor its appearance and functionality to your specific requirements.

Currently, the AI Chatbot plugin does not expose a dedicated set of custom PHP action (`do_action()`) or filter (`apply_filters()`) hooks specifically intended for third-party developers to significantly extend or modify its core PHP behavior beyond the available settings. The plugin integrates with WordPress and operates using standard WordPress core hooks for functionalities such as adding administrative menus, enqueueing scripts and styles, processing AJAX requests, and registering shortcodes.

**Customization & Extensibility:**

While direct PHP hooks for core features are not formally published at this time, you can customize and extend the plugin in several ways:

*   **CSS Customization:** The visual appearance of the chat widget is controlled by CSS. You can override the plugin's default styles by targeting its specific CSS classes (most are prefixed with `vac-` or `vac-chat-`) in your active theme's stylesheet (e.g., `style.css`) or through the WordPress Customizer's "Additional CSS" feature. This allows for significant visual integration with your site's design.

*   **JavaScript Interaction:** The frontend behavior is managed by JavaScript. While not formal hooks, advanced users with JavaScript knowledge might be able to interact with the chatbot's DOM elements or listen for specific DOM events if deep client-side customization is required. However, this would be considered an advanced customization and should be done with care to avoid conflicts with plugin updates.

*   **Feature Requests & Future Development:** We are open to feedback and potential future enhancements. If you have specific requirements for extensibility points, such as particular PHP hooks or filters that would be beneficial for your use case, please consider reaching out to the plugin author at [voler.ai](https://voler.ai) with your suggestions. Future versions of the plugin may incorporate such developer-oriented features based on demand and common needs.

For most common customization needs, we encourage you to first explore the comprehensive settings available within the **AI Chatbot** admin panel.
