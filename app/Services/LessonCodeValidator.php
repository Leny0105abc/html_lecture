<?php

namespace App\Services;

use App\Models\Lesson;
use DOMDocument;
use DOMElement;
use DOMXPath;

class LessonCodeValidator
{
    public function normalizeLegacyHtml(Lesson $lesson, string $html): string
    {
        if ($lesson->level === 'Advanced') {
            return $html;
        }

        // Older starter templates linked this file even in now HTML-only lessons.
        return preg_replace_callback('/<link\b[^>]*>/i', function (array $match): string {
            $link = $match[0];
            $stylesheet = preg_match('/\brel\s*=\s*(["\'])stylesheet\1/i', $link);
            $legacyFile = preg_match('/\bhref\s*=\s*(["\'])(?:\.\/)?styles\.css\1/i', $link);

            return $stylesheet && $legacyFile ? '' : $link;
        }, $html) ?? $html;
    }

    public function check(Lesson $lesson, string $html, string $css, array $extraFiles = []): array
    {
        $rules = $lesson->validation_rules ?? [];
        if (! $rules) {
            return []; // Historical lessons created outside the curriculum keep their existing workflow.
        }

        $errors = [];
        $pages = ['index.html' => $html] + $extraFiles;
        $allHtml = implode("\n", array_values($pages));
        $source = preg_replace('/<!--.*?-->/s', '', $allHtml) ?? $allHtml;
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($source, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);

        if ($lesson->level !== 'Advanced') {
            if (trim($css) !== '' || preg_match('/<style\b|\sstyle\s*=|<link\b[^>]*rel\s*=\s*["\']?stylesheet/i', $source)) {
                $errors[] = 'This lesson uses HTML only. Remove CSS from the CSS editor and HTML page.';
            }
        }

        foreach ($rules['tags'] ?? [] as $tag => $minimum) {
            $count = preg_match_all('/<'.preg_quote($tag, '/').'\b/i', $source);
            if ($count < $minimum) {
                $errors[] = "Your page needs at least {$minimum} <{$tag}> ".($minimum === 1 ? 'element' : 'elements').'. Add '.($minimum - $count).' more and run your code again.';
            }
        }
        foreach ($rules['any_tags'] ?? [] as $alternatives) {
            $found = false;
            foreach ($alternatives as $tag) {
                if (preg_match('/<'.preg_quote($tag, '/').'\b/i', $source)) {
                    $found = true;
                }
            }
            if (! $found) {
                $errors[] = 'Add at least one '.implode(' or ', array_map(fn ($tag) => "<{$tag}>", $alternatives)).' element.';
            }
        }
        foreach ($rules['attributes'] ?? [] as [$tag, $attribute]) {
            $elements = $document->getElementsByTagName($tag);
            $found = false;
            foreach ($elements as $element) {
                if ($element instanceof DOMElement && $element->hasAttribute($attribute) && ($attribute === 'controls' || trim($element->getAttribute($attribute)) !== '')) {
                    $found = true;
                }
            }
            if (! $found) {
                $errors[] = "Add a {$attribute} attribute to a <{$tag}> element.";
            }
        }
        foreach ($rules['input_types'] ?? [] as $type => $minimum) {
            $count = 0;
            foreach ($document->getElementsByTagName('input') as $input) {
                if (strtolower($input->getAttribute('type') ?: 'text') === $type) {
                    $count++;
                }
            }
            if ($count < $minimum) {
                $errors[] = "Add at least {$minimum} input ".($minimum === 1 ? 'field' : 'fields')." with type=\"{$type}\".";
            }
        }

        $inlineCss = '';
        foreach ($xpath->query('//*[@style]') as $element) {
            $inlineCss .= $element->getAttribute('style').';';
        }
        foreach ($document->getElementsByTagName('style') as $style) {
            $inlineCss .= $style->textContent;
        }
        $allCss = $css."\n".$inlineCss;
        foreach ($rules['css_properties'] ?? [] as $property) {
            if (! preg_match('/(?:^|[;{])\s*'.preg_quote($property, '/').'\s*:/im', $allCss)) {
                $errors[] = "Add the CSS {$property} property to style your page.";
            }
        }

        foreach ($rules['special'] ?? [] as $special) {
            $this->checkSpecial($special, $document, $xpath, $allHtml, $allCss, $pages, $errors);
        }

        return array_values(array_unique($errors));
    }

    private function checkSpecial(string $special, DOMDocument $document, DOMXPath $xpath, string $html, string $css, array $pages, array &$errors): void
    {
        switch ($special) {
            case 'paragraph-alignments':
                foreach (['left', 'center', 'right'] as $alignment) {
                    if ($xpath->query("//p[translate(@align, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')='{$alignment}']")->length === 0) {
                        $errors[] = "Add a paragraph with align=\"{$alignment}\".";
                    }
                }
                break;
            case 'linked-image':
                if ($xpath->query('//a[descendant::img]')->length === 0) {
                    $errors[] = 'Put an <img> inside an <a href="..."> to create an image link.';
                }
                break;
            case 'text-link':
                $found = false;
                foreach ($document->getElementsByTagName('a') as $anchor) {
                    if (trim($anchor->textContent) !== '') {
                        $found = true;
                    }
                }
                if (! $found) {
                    $errors[] = 'Add a text hyperlink, not only an image hyperlink.';
                }
                break;
            case 'three-lists':
                if ($document->getElementsByTagName('ul')->length + $document->getElementsByTagName('ol')->length < 3) {
                    $errors[] = 'Add three lists: foods, subjects, and hobbies.';
                }
                break;
            case 'two-list-types':
                if ($document->getElementsByTagName('ul')->length === 0 || $document->getElementsByTagName('ol')->length === 0) {
                    $errors[] = 'Use both an unordered <ul> list and an ordered <ol> list.';
                }
                break;
            case 'four-column-table':
                if ($xpath->query('//table/tr[1]/th | //table/thead/tr[1]/th')->length < 4) {
                    $errors[] = 'Give your grade table at least four <th> heading columns.';
                }
                break;
            case 'associated-labels':
                $associated = 0;
                foreach ($document->getElementsByTagName('label') as $label) {
                    $for = $label->getAttribute('for');
                    if (($for !== '' && $xpath->query('//*[@id='.self::xpathString($for).']')->length > 0) || $label->getElementsByTagName('input')->length > 0) {
                        $associated++;
                    }
                }
                if ($associated < 4) {
                    $errors[] = 'Connect each of the four labels to an input using matching for/id values, or wrap the input inside its label.';
                }
                break;
            case 'submit-button':
                if ($xpath->query('//button[not(@type) or @type="submit"] | //input[@type="submit"]')->length === 0) {
                    $errors[] = 'Add a Submit button inside the form.';
                }
                break;
            case 'radio-group':
                $names = [];
                foreach ($xpath->query('//input[@type="radio"]') as $input) {
                    $name = $input->getAttribute('name');
                    if ($name !== '') {
                        $names[$name] = ($names[$name] ?? 0) + 1;
                    }
                }
                if (! array_filter($names, fn ($count) => $count >= 2)) {
                    $errors[] = 'Give at least two radio choices the same name so they form one group.';
                }
                break;
            case 'element-selector':
                if (! preg_match('/(?:^|})\s*(?:p|h1|h2|body|div)\s*\{/i', $css)) {
                    $errors[] = 'Add an element selector such as p { ... } to your CSS.';
                }
                break;
            case 'two-classes':
                preg_match_all('/\.([a-z][\w-]*)\s*\{/i', $css, $matches);
                $classes = array_unique($matches[1]);
                $used = array_filter($classes, fn ($class) => (bool) preg_match('/\bclass\s*=\s*["\'][^"\']*\b'.preg_quote($class, '/').'\b/i', $html));
                if (count($used) < 2) {
                    $errors[] = 'Create two different CSS class selectors and use both classes in your HTML.';
                }
                break;
            case 'id-selector':
                preg_match_all('/#([a-z][\w-]*)\s*\{/i', $css, $matches);
                $used = array_filter($matches[1], fn ($id) => (bool) preg_match('/\bid\s*=\s*["\']'.preg_quote($id, '/').'["\']/i', $html));
                if (! $used) {
                    $errors[] = 'Add a CSS #id selector and a matching id attribute in HTML.';
                }
                break;
            case 'grid-three-columns':
                if (! preg_match('/grid-template-columns\s*:\s*(?:repeat\(\s*3\s*,|(?:[^;]*\b1fr\b){3})/i', $css)) {
                    $errors[] = 'Set grid-template-columns to three columns, such as repeat(3, 1fr).';
                }
                break;
            case 'six-gallery-items':
                if ($document->getElementsByTagName('img')->length < 6) {
                    $errors[] = 'Add at least six images to your gallery.';
                }
                break;
            case 'four-pages':
                foreach (['about.html', 'gallery.html', 'contact.html'] as $file) {
                    if (empty($pages[$file]) || ! preg_match('/<html\b/i', $pages[$file]) || ! preg_match('/<head\b/i', $pages[$file]) || ! preg_match('/<title\b/i', $pages[$file]) || ! preg_match('/<body\b/i', $pages[$file])) {
                        $errors[] = "Create {$file} with <html>, <head>, <title>, and <body>.";
                    } elseif (! preg_match('/<nav\b/i', $pages[$file]) || ! preg_match('/href\s*=\s*["\']index\.html["\']/i', $pages[$file])) {
                        $errors[] = "Add navigation with a Home link to {$file}.";
                    }
                }
                foreach (['index.html', 'about.html', 'gallery.html', 'contact.html'] as $file) {
                    if (! preg_match('/href\s*=\s*["\']'.preg_quote($file, '/').'["\']/i', $html)) {
                        $errors[] = "Add a navigation link to {$file}.";
                    }
                }
                break;
            case 'site-concepts':
                foreach ([['h1', 'a heading'], ['p', 'a paragraph'], ['img', 'an image'], ['table', 'a table'], ['form', 'a form'], ['div', 'a div section'], ['span', 'a span'], ['nav', 'navigation']] as [$tag, $label]) {
                    if (! preg_match('/<'.$tag.'\b/i', $html)) {
                        $errors[] = "Use {$label} somewhere in the four-page site.";
                    }
                }
                if (! preg_match('/<(?:ul|ol)\b/i', $html)) {
                    $errors[] = 'Use a list somewhere in the site.';
                }
                if (! preg_match('/<(?:audio|video)\b/i', $html)) {
                    $errors[] = 'Include audio or video on one of the pages.';
                }
                if (! preg_match('/<(?:b|strong|i|em)\b/i', $html)) {
                    $errors[] = 'Use bold or italic text formatting on one page.';
                }
                if (! preg_match('/\bclass\s*=/i', $html) || ! preg_match('/\bid\s*=/i', $html)) {
                    $errors[] = 'Use both class and id attributes in the site.';
                }
                if (! preg_match('/\.[a-z][\w-]*\s*\{/i', $css) || ! preg_match('/#[a-z][\w-]*\s*\{/i', $css)) {
                    $errors[] = 'Style at least one class and one ID in your CSS file.';
                }
                break;
        }
    }

    private static function xpathString(string $value): string
    {
        return '"'.str_replace('"', '', $value).'"';
    }
}
