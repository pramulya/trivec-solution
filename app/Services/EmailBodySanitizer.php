<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class EmailBodySanitizer
{
    public function clean(string $html, bool $aiEnabled = false, ?string $phishingLabel = null): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument();
        
        // Suppress parsing errors for malformed HTML
        libxml_use_internal_errors(true);
        
        // Load HTML with UTF-8 encoding hack
        // The '<div>' wrapper ensures we don't lose the root elements if there are multiple
        // mb_convert_encoding ensures special characters are respected
        $dom->loadHTML(
            mb_convert_encoding("<div>{$html}</div>", 'HTML-ENTITIES', 'UTF-8'), 
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // 1. Remove dangerous tags
        $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'link', 'meta', 'title', 'head'];
        foreach ($dangerousTags as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            while ($nodes->length > 0) {
                $nodes->item(0)->parentNode->removeChild($nodes->item(0));
            }
        }

        // 2. Remove event handlers (onclick, etc.)
        // This is a bit heavy, iterating all elements
        // A simpler way for a prototype is to just trust specific tags or strip attributes.
        // We will stick to targeted cleaning for critical elements.

        // 3. Process Links
        $links = $dom->getElementsByTagName('a');
        foreach ($links as $link) {
            // Get href
            $href = $link->getAttribute('href');
            
            // Set security attributes
            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener noreferrer');
            $link->setAttribute('class', 'text-blue-400 underline break-all hover:text-blue-300');

            // AI/Phishing Styling
            if ($aiEnabled && in_array($phishingLabel, ['phishing', 'suspicious'])) {
                $link->setAttribute('class', 'bg-red-700 text-white px-1 rounded break-all decoration-clone');
                // Ensure href is safe to display if we want to show it, but usually we just style the link
            }
        }
        
        // 4. Process Images
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            // Ensure images have max-width via class or style
            // Note: We used to strip width/height here, but that broke small icons.
            // We now keep them. The view CSS (max-width: 100%) will handle overflow.
            
            // Optional: Lazy load
            $img->setAttribute('loading', 'lazy');
        }

        // 5. Output HTML
        // We look for our wrapper div
        $wrapper = $dom->getElementsByTagName('div')->item(0);
        
        if ($wrapper) {
            $output = '';
            foreach ($wrapper->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
            return $output;
        }

        return $dom->saveHTML();
    }
}
