<?php
/**
 * Knowledge Base Manager for AI Chatbot
 * Replaces OpenAI file search with local knowledge base
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICB_Knowledge_Base {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aicb_knowledge_base';
    }
    
    /**
     * Create knowledge base table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'aicb_knowledge_base';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            content_hash varchar(64) NOT NULL,
            source_type varchar(50) DEFAULT 'custom',
            source_id int(11) DEFAULT NULL,
            source_url varchar(500) DEFAULT NULL,
            excerpt text DEFAULT NULL,
            keywords text DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            priority int(3) DEFAULT 50,
            status varchar(20) DEFAULT 'active',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY content_hash (content_hash),
            KEY source_type (source_type),
            KEY status (status),
            KEY priority (priority),
            FULLTEXT KEY search_content (title, content, excerpt, keywords)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Search knowledge base for relevant content
     */
    public function search($query, $limit = 3) {
        global $wpdb;
        
        if (empty($query)) {
            return array();
        }
        
        // Clean query
        $search_terms = $this->extract_search_terms($query);
        if (empty($search_terms)) {
            return array();
        }
        
        // Build FULLTEXT search query
        $search_query = '"' . implode('" "', array_slice($search_terms, 0, 5)) . '"';
        
        $sql = $wpdb->prepare("
            SELECT id, title, content, excerpt, source_type, source_url, priority,
                   MATCH(title, content, excerpt, keywords) AGAINST(%s IN BOOLEAN MODE) as relevance_score
            FROM {$this->table_name} 
            WHERE status = 'active' 
                AND MATCH(title, content, excerpt, keywords) AGAINST(%s IN BOOLEAN MODE)
            ORDER BY relevance_score DESC, priority DESC
            LIMIT %d
        ", $search_query, $search_query, $limit);
        
        $results = $wpdb->get_results($sql);
        
        if (empty($results)) {
            // Fallback to LIKE search if FULLTEXT doesn't return results
            $results = $this->fallback_search($search_terms, $limit);
        }
        
        return $this->format_search_results($results);
    }
    
    /**
     * Fallback search using LIKE
     */
    private function fallback_search($search_terms, $limit) {
        global $wpdb;
        
        $like_conditions = array();
        foreach (array_slice($search_terms, 0, 3) as $term) {
            $like_conditions[] = $wpdb->prepare(
                "(title LIKE %s OR content LIKE %s OR keywords LIKE %s)", 
                '%' . $wpdb->esc_like($term) . '%',
                '%' . $wpdb->esc_like($term) . '%',
                '%' . $wpdb->esc_like($term) . '%'
            );
        }
        
        if (empty($like_conditions)) {
            return array();
        }
        
        $sql = "SELECT id, title, content, excerpt, source_type, source_url, priority
                FROM {$this->table_name} 
                WHERE status = 'active' 
                    AND (" . implode(' OR ', $like_conditions) . ")
                ORDER BY priority DESC, created_at DESC
                LIMIT " . intval($limit);
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Extract search terms from query
     */
    private function extract_search_terms($query) {
        // Remove common stop words
        $stop_words = array(
            'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
            'about', 'what', 'how', 'when', 'where', 'why', 'is', 'are', 'was', 'were', 'be', 'been',
            'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'can', 'may',
            'might', 'must', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us',
            'them', 'my', 'your', 'his', 'hers', 'its', 'our', 'their'
        );
        
        // Extract words
        $words = str_word_count(strtolower($query), 1);
        
        // Remove stop words and short words
        $meaningful_words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });
        
        return array_values($meaningful_words);
    }
    
    /**
     * Format search results
     */
    private function format_search_results($results) {
        $formatted = array();
        
        foreach ($results as $result) {
            $formatted[] = array(
                'id' => $result->id,
                'title' => $result->title,
                'content' => $this->truncate_content($result->content, 300),
                'excerpt' => $result->excerpt ?: $this->create_excerpt($result->content),
                'source_type' => $result->source_type,
                'source_url' => $result->source_url,
                'relevance' => isset($result->relevance_score) ? $result->relevance_score : 0
            );
        }
        
        return $formatted;
    }
    
    /**
     * Add content to knowledge base
     */
    public function add_content($title, $content, $options = array()) {
        global $wpdb;
        
        $content_hash = md5($content);
        
        // Check if content already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE content_hash = %s",
            $content_hash
        ));
        
        if ($existing) {
            return $existing; // Return existing ID
        }
        
        $data = array(
            'title' => sanitize_text_field($title),
            'content' => wp_kses_post($content),
            'content_hash' => $content_hash,
            'excerpt' => $this->create_excerpt($content),
            'keywords' => $this->extract_keywords($title . ' ' . $content),
            'source_type' => isset($options['source_type']) ? $options['source_type'] : 'custom',
            'source_id' => isset($options['source_id']) ? intval($options['source_id']) : null,
            'source_url' => isset($options['source_url']) ? esc_url_raw($options['source_url']) : null,
            'category' => isset($options['category']) ? sanitize_text_field($options['category']) : null,
            'priority' => isset($options['priority']) ? intval($options['priority']) : 50,
            'status' => 'active',
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_name, $data);
        
        if ( $result && class_exists( 'AICB_Embedding_Manager' ) ) {
            AICB_Embedding_Manager::instance()->schedule_rebuild();
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update content in knowledge base
     */
    public function update_content($id, $title, $content, $options = array()) {
        global $wpdb;
        
        $data = array(
            'title' => sanitize_text_field($title),
            'content' => wp_kses_post($content),
            'content_hash' => md5($content),
            'excerpt' => $this->create_excerpt($content),
            'keywords' => $this->extract_keywords($title . ' ' . $content),
            'last_updated' => current_time('mysql')
        );
        
        if (isset($options['category'])) {
            $data['category'] = sanitize_text_field($options['category']);
        }
        
        if (isset($options['priority'])) {
            $data['priority'] = intval($options['priority']);
        }
        
        if (isset($options['status'])) {
            $data['status'] = sanitize_text_field($options['status']);
        }
        
        $updated = $wpdb->update($this->table_name, $data, array('id' => intval($id)));
        if ( $updated && class_exists( 'AICB_Embedding_Manager' ) ) {
            AICB_Embedding_Manager::instance()->schedule_rebuild();
        }
        return $updated;
    }
    
    /**
     * Delete content from knowledge base
     */
    public function delete_content($id) {
        global $wpdb;
        $deleted = $wpdb->delete($this->table_name, array('id' => intval($id)));
        if ( $deleted && class_exists( 'AICB_Embedding_Manager' ) ) {
            AICB_Embedding_Manager::instance()->schedule_rebuild();
        }
        return $deleted;
    }
    
    /**
     * Sync posts to knowledge base
     */
    public function sync_posts($post_types = array('post', 'page')) {
        $posts = get_posts(array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        $synced = 0;
        foreach ($posts as $post) {
            $content = wp_strip_all_tags($post->post_content);
            if (strlen($content) > 100) { // Only sync substantial content
                $result = $this->add_content(
                    $post->post_title,
                    $content,
                    array(
                        'source_type' => 'post',
                        'source_id' => $post->ID,
                        'source_url' => get_permalink($post),
                        'category' => $post->post_type,
                        'priority' => $post->post_type === 'page' ? 60 : 50
                    )
                );
                
                if ($result) {
                    $synced++;
                }
            }
        }
        
        return $synced;
    }
    
    /**
     * Sync WooCommerce products to knowledge base
     */
    public function sync_products($category_ids = array()) {
        if (!class_exists('WooCommerce')) {
            return 0;
        }
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'numberposts' => -1
        );
        
        if (!empty($category_ids)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_ids
                )
            );
        }
        
        $products = get_posts($args);
        $synced = 0;
        
        foreach ($products as $product) {
            $wc_product = wc_get_product($product->ID);
            $content = $product->post_title . "\n\n";
            $content .= $wc_product->get_short_description() . "\n\n";
            $content .= wp_strip_all_tags($product->post_content);
            
            if (strlen($content) > 50) {
                $result = $this->add_content(
                    $product->post_title,
                    $content,
                    array(
                        'source_type' => 'product',
                        'source_id' => $product->ID,
                        'source_url' => get_permalink($product),
                        'category' => 'product',
                        'priority' => 70 // Higher priority for products
                    )
                );
                
                if ($result) {
                    $synced++;
                }
            }
        }
        
        return $synced;
    }
    
    /**
     * Create excerpt from content
     */
    private function create_excerpt($content, $length = 150) {
        $content = wp_strip_all_tags($content);
        return wp_trim_words($content, $length);
    }
    
    /**
     * Truncate content to specified length
     */
    private function truncate_content($content, $length = 300) {
        if (strlen($content) <= $length) {
            return $content;
        }
        
        return substr($content, 0, $length) . '...';
    }
    
    /**
     * Extract keywords from content
     */
    private function extract_keywords($content, $max_keywords = 20) {
        // Simple keyword extraction
        $content = strtolower(wp_strip_all_tags($content));
        $words = str_word_count($content, 1);
        
        // Remove common words
        $stop_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by');
        $words = array_diff($words, $stop_words);
        
        // Count word frequency
        $word_count = array_count_values($words);
        
        // Sort by frequency
        arsort($word_count);
        
        // Get top keywords
        $keywords = array_slice(array_keys($word_count), 0, $max_keywords);
        
        return implode(', ', $keywords);
    }
    
    /**
     * Get knowledge base statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total entries
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'active'");
        
        // By source type
        $source_types = $wpdb->get_results("
            SELECT source_type, COUNT(*) as count 
            FROM {$this->table_name} 
            WHERE status = 'active' 
            GROUP BY source_type
        ");
        
        $stats['by_type'] = array();
        foreach ($source_types as $type) {
            $stats['by_type'][$type->source_type] = $type->count;
        }
        
        // Last updated
        $stats['last_updated'] = $wpdb->get_var("SELECT MAX(last_updated) FROM {$this->table_name}");
        
        return $stats;
    }
    
    /**
     * Clear knowledge base
     */
    public function clear_all($source_type = null) {
        global $wpdb;
        
        if ($source_type) {
            return $wpdb->delete($this->table_name, array('source_type' => $source_type));
        } else {
            return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        }
    }
    
    /**
     * Import from file
     */
    public function import_from_file($file_path, $options = array()) {
        if (!file_exists($file_path)) {
            return array('error' => __('File not found.', 'ai-chatbot'));
        }
        
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $title = pathinfo($file_path, PATHINFO_FILENAME);
        $content = '';
        
        switch ($file_extension) {
            case 'txt':
                $content = file_get_contents($file_path);
                break;
                
            case 'md':
            case 'markdown':
                $raw_markdown = file_get_contents($file_path);
                $content = $this->extract_text_from_markdown($raw_markdown);
                break;
                
            case 'pdf':
                $extracted = $this->extract_text_from_pdf($file_path);
                if (is_wp_error($extracted)) {
                    return array('error' => $extracted->get_error_message());
                }
                $content = $extracted;
                break;
                
            case 'json':
                $json_data = json_decode(file_get_contents($file_path), true);
                if (is_array($json_data)) {
                    $imported = 0;
                    foreach ($json_data as $item) {
                        if (isset($item['title']) && isset($item['content'])) {
                            $result = $this->add_content($item['title'], $item['content'], $options);
                            if ($result) {
                                $imported++;
                            }
                        }
                    }
                    return array('success' => true, 'imported' => $imported);
                }
                return array('error' => __('Invalid JSON structure.', 'ai-chatbot'));
                
            case 'docx':
                return array('error' => __('DOCX extraction requires additional setup.', 'ai-chatbot'));
                
            default:
                return array('error' => __('Unsupported file format.', 'ai-chatbot'));
        }
        
        $content = is_string($content) ? trim($content) : '';
        
        if (!empty($content)) {
            $options['source_type'] = 'file';
            $result = $this->add_content($title, $content, $options);
            if ($result) {
                return array(
                    'success' => true,
                    'id'      => $result,
                    'title'   => $title,
                    'length'  => strlen($content),
                );
            }
            return array('error' => __('Failed to import the supplied file.', 'ai-chatbot'));
        }
        
        return array('error' => __('No content extracted from the file.', 'ai-chatbot'));
    }

    /**
     * Extract plain text from a PDF file using available parsers.
     *
     * @param string $file_path Absolute file path.
     * @return string|WP_Error
     */
    private function extract_text_from_pdf($file_path) {
        $last_error = '';

        if (class_exists('\Smalot\PdfParser\Parser')) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file_path);
                $text = $pdf ? $pdf->getText() : '';
                if (!empty($text)) {
                    return $text;
                }
            } catch (\Exception $e) {
                $last_error = $e->getMessage();
            }
        }

        if (function_exists('pdf_get_text')) {
            $text = @pdf_get_text($file_path);
            if (!empty($text)) {
                return $text;
            }
        }

        $message = !empty($last_error)
            ? $last_error
            : __('PDF extraction is not available on this server. Install a PDF parser (e.g. smalot/pdfparser) to enable it.', 'ai-chatbot');

        return new WP_Error('aicb_pdf_extraction_unavailable', $message);
    }

    /**
     * Convert Markdown content to plain text.
     *
     * @param string $markdown Markdown input.
     * @return string
     */
    private function extract_text_from_markdown($markdown) {
        $markdown = (string) $markdown;

        if ('' === trim($markdown)) {
            return '';
        }

        if (class_exists('Parsedown')) {
            try {
                $parser = \Parsedown::instance();
                $html = $parser->text($markdown);
                return wp_strip_all_tags($html);
            } catch (\Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Fallback to manual stripping if Parsedown fails.
            }
        }

        $text = preg_replace('/```[\s\S]*?```/m', '', $markdown);
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        $text = preg_replace('/!\[[^\]]*\]\(([^)]+)\)/', '', $text);
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1 ($2)', $text);
        $text = preg_replace('/^>+\s?/m', '', $text);
        $text = preg_replace('/^#{1,6}\s*/m', '', $text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
        $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
        $text = str_replace(array('_', '#'), ' ', $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }
    
    /**
     * Export knowledge base
     */
    public function export($format = 'json') {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT title, content, source_type, category, priority, created_at 
            FROM {$this->table_name} 
            WHERE status = 'active' 
            ORDER BY priority DESC, created_at DESC
        ");
        
        switch ($format) {
            case 'json':
                return json_encode($results, JSON_PRETTY_PRINT);
                
            case 'csv':
                $csv = "Title,Content,Source Type,Category,Priority,Created\n";
                foreach ($results as $row) {
                    $csv .= '"' . str_replace('"', '""', $row->title) . '",';
                    $csv .= '"' . str_replace('"', '""', $row->content) . '",';
                    $csv .= '"' . $row->source_type . '",';
                    $csv .= '"' . $row->category . '",';
                    $csv .= '"' . $row->priority . '",';
                    $csv .= '"' . $row->created_at . '"' . "\n";
                }
                return $csv;
                
            case 'xml':
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<knowledge_base>\n";
                foreach ($results as $row) {
                    $xml .= "  <item>\n";
                    $xml .= "    <title><![CDATA[{$row->title}]]></title>\n";
                    $xml .= "    <content><![CDATA[{$row->content}]]></content>\n";
                    $xml .= "    <source_type>{$row->source_type}</source_type>\n";
                    $xml .= "    <category>{$row->category}</category>\n";
                    $xml .= "    <priority>{$row->priority}</priority>\n";
                    $xml .= "    <created_at>{$row->created_at}</created_at>\n";
                    $xml .= "  </item>\n";
                }
                $xml .= "</knowledge_base>";
                return $xml;
                
            default:
                return false;
        }
    }
    
    /**
     * Search and get formatted context for AI
     */
    public function get_context_for_ai($query, $max_context_length = 2000) {
        $results = $this->search($query, 3);
        
        if (empty($results)) {
            return '';
        }
        
        $context = "## Relevant Information:\n\n";
        $current_length = strlen($context);
        
        foreach ($results as $result) {
            $section = "### {$result['title']}\n";
            $section .= $result['excerpt'] . "\n\n";
            
            if ($current_length + strlen($section) > $max_context_length) {
                break;
            }
            
            $context .= $section;
            $current_length += strlen($section);
        }
        
        return trim($context);
    }
    
    /**
     * Get related content suggestions
     */
    public function get_related_content($query, $limit = 5) {
        $results = $this->search($query, $limit);
        
        $related = array();
        foreach ($results as $result) {
            if (!empty($result['source_url'])) {
                $related[] = array(
                    'title' => $result['title'],
                    'excerpt' => $result['excerpt'],
                    'url' => $result['source_url'],
                    'type' => $result['source_type'],
                    'relevance' => $result['relevance']
                );
            }
        }
        
        return $related;
    }
    
    /**
     * Auto-update knowledge base from WordPress content
     */
    public function auto_update() {
        $options = get_option('aicb_settings', array());
        
        if (empty($options['knowledge_base_auto_update'])) {
            return false;
        }
        
        $updated = 0;
        
        // Update from posts
        if (!empty($options['kb_sync_posts'])) {
            $post_types = isset($options['kb_post_types']) ? $options['kb_post_types'] : array('post', 'page');
            $updated += $this->sync_posts($post_types);
        }
        
        // Update from products
        if (!empty($options['kb_sync_products']) && class_exists('WooCommerce')) {
            $category_ids = isset($options['kb_product_categories']) ? $options['kb_product_categories'] : array();
            $updated += $this->sync_products($category_ids);
        }
        
        // Log the update
        if ($updated > 0 && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("AICB Knowledge Base: Auto-updated {$updated} items");
        }
        
        return $updated;
    }
    
    /**
     * Schedule auto-updates
     */
    public static function schedule_auto_update() {
        if (!wp_next_scheduled('aicb_auto_update_knowledge_base')) {
            wp_schedule_event(time(), 'daily', 'aicb_auto_update_knowledge_base');
        }
    }
    
    /**
     * Unschedule auto-updates
     */
    public static function unschedule_auto_update() {
        wp_clear_scheduled_hook('aicb_auto_update_knowledge_base');
    }
}

// Hook for auto-updates
add_action('aicb_auto_update_knowledge_base', function() {
    $kb = new AICB_Knowledge_Base();
    $kb->auto_update();
});

// Hook for post updates
add_action('save_post', function($post_id) {
    $options = get_option('aicb_settings', array());
    
    if (empty($options['knowledge_base_auto_update']) || empty($options['kb_sync_posts'])) {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post || $post->post_status !== 'publish') {
        return;
    }
    
    $kb_post_types = isset($options['kb_post_types']) ? $options['kb_post_types'] : array('post', 'page');
    if (!in_array($post->post_type, $kb_post_types)) {
        return;
    }
    
    $kb = new AICB_Knowledge_Base();
    $content = wp_strip_all_tags($post->post_content);
    
    if (strlen($content) > 100) {
        // Check if already exists
        global $wpdb;
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}aicb_knowledge_base WHERE source_type = 'post' AND source_id = %d",
            $post_id
        ));
        
        if ($existing_id) {
            $kb->update_content($existing_id, $post->post_title, $content);
        } else {
            $kb->add_content(
                $post->post_title,
                $content,
                array(
                    'source_type' => 'post',
                    'source_id' => $post_id,
                    'source_url' => get_permalink($post),
                    'category' => $post->post_type
                )
            );
        }
    }
});
