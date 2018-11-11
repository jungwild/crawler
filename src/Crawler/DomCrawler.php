<?php
namespace Jungwild\Crawler;

use Symfony\Component\DomCrawler\Crawler;
use \Wa72\HtmlPageDom\HtmlPageCrawler;
use \DOMDocument;

class DomCrawler extends HtmlPageCrawler {


    public function safeText() {

        if($this->count() > 0) {

            return trim($this->text());
        }

        return null;
    }

    public function setValue($value)
    {
        $this->getNode(0)->nodeValue = $value;
    }

    public function safeHtml() {

        if($this->count() > 0) {

            $html = '';

            foreach ($this as $domnode) {
                /*
                $cra = new DomCrawler($domnode);
                $html .= $cra->html();
                */
                $html .= $domnode->ownerDocument->saveHTML($domnode);
            }

            return $html;
        }

        return null;
    }

    public function toUtf8() {

        $html = $this->safeHtml();
        $html = utf8_encode($html);
        return new DomCrawler($html);
    }

    public function removeNodesWithvalues($values) {
        if(!is_array($values)) {
            $values = [$values];
        }

        $this->filter('*')->each(function (Crawler $crawler) use ($values) {

            foreach ($crawler as $node) {

                foreach ($values as $value) {
                    if(strpos(strtolower(trim($node->nodeValue)), strtolower($value)) !== false) {
                        $node->parentNode->removeChild($node);
                    }
                }
            }
        });

        return $this;

    }

    public function cut($from = false, $to = false) {

        $string = $this->safeHtml();

        if($from === false && $to === false) {
            return $this;
        }

        if($from !== false) {
            if(($pos = strpos($string, $from)) !== false) {

                $string = substr($string,($pos+strlen($from)));
            }
            else {
                return $this;
            }
        }

        if($to !== false) {
            if(($pos = strpos($string, $to)) !== false) {

                $string = substr($string,0,$pos);
            }
            else {
                return $this;
            }
        }

        return new DomCrawler($string);

    }

    /*
     * Helper to get assoc array from html form
     * to rectrieve hidden input fields etc..
     */
    public function formToArray($css_selector) {

        $dom_crawler = $this;

        $inputs = $dom_crawler->filter($css_selector . ' input, ' . $css_selector . ' textarea');

        $out = [];

        foreach ($inputs as $input) {
            $name = $input->getAttribute('name');
            $value = $input->getAttribute('value');

            /*
             * hat feldname eckige klammern? ist array?
             */
            if(strpos($name,'[') !== false) {

                $tmp_name_array = false;
                $cur_name = $name;

                // @todo eine while schleife machen für tiefer subarrays
                // while(strpos($cur_name,'[') !== false) {
                if(strpos($cur_name,'[') !== false) {

                    $bracket_position = strpos($cur_name,'[');

                    $cur_subname = substr($cur_name, ($bracket_position+1));
                    $cur_subname = substr($cur_subname,0, strpos($cur_subname,']'));

                    $cur_name = substr($cur_name,0, $bracket_position);

                    if(!isset($out[$cur_name])) {
                        $out[$cur_name] = [];
                    }

                    $out[$cur_name][$cur_subname] = $value;
                }


            }
            else {
                $out[$name] = $value;
            }

        }

        /*
         * SELECT
         */
        $dom_crawler->filter($css_selector . ' select')->each(function($fcrawler) use (&$out){

            $name = $fcrawler->safeAttr('name');
            $out[$name] = '';
            $first_option = true;

            $fcrawler->filter('option')->each(function($ocrawler) use (&$out, &$name, &$first_option){

                if($first_option)
                {
                    $first_option = false;
                    $out[$name] = $ocrawler->safeAttr('value');
                }

                if($ocrawler->attr('selected'))
                {
                    $out[$name] = $ocrawler->safeAttr('value');
                }


            });
        });

        return $out;

    }

    public function safeAttr($attribute) {
        if($this->count() > 0) {

            return trim($this->attr($attribute));

        }

        return null;
    }

    // @todo läuft noch nicht rund
    /*
     * Entfernt alle Attribute von allen Tags
     */
    public function removeAttributes() {


        if($this->count() > 0) {

            $html = '';

            foreach ($this as $domnode) {

                echo 'NodeName: ' . $domnode->nodeName.'<br>';

                /*
                 * Alle attribute löschen
                 */
                $attributes = $domnode->attributes;
                while ($attributes->length) {
                    echo 'a: ' . $attributes->item(0)->name;
                    $domnode->removeAttribute($attributes->item(0)->name);
                }
                echo '<hr>';

                /*
                 * append html
                 */
                $cra = new DomCrawler($domnode);
                $html .= $cra->html();

            }

            return new DomCrawler(trim($html));
        }

    }

    public function stripTagsAndAttributes($allowed_tags = ['a','p','strong','ul','ol','li','h1','h2','h3','h4','h5'], $allowed_attrs = ['href']) {

        if($this->count() > 0) {

            $html = '';

            foreach ($this as $domnode) {
                $cra = new DomCrawler($domnode);
                $html .= $cra->html();
            }
            /*
             * Start Dom Oprtation
             */
            $xml = new DOMDocument();
            //Suppress warnings: proper error handling is beyond scope of example
            libxml_use_internal_errors(true);

            /*
             * fetch emtpy html error
             */
            if (!strlen($str_html)) {
                return $str_html;
            }

            if ($xml->loadHTML($str_html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)) {
                foreach ($xml->getElementsByTagName("*") as $tag) {
                    if (!in_array($tag->tagName, $allowed_tags)) {
                        $tag->parentNode->removeChild($tag);
                    } else {
                        foreach ($tag->attributes as $attr) {
                            if (!in_array($attr->nodeName, $allowed_attrs)) {
                                $tag->removeAttribute($attr->nodeName);
                            }
                        }
                    }
                }
            }
            /*
             * End Dom Operation
             */

            /*
             * output HTML
             */
            return new DomCrawler($xml);
        }

        return $this;
    }

    public function safeHtmlClean($options = []) {

        /*
         * inition options array
         */
        $options = array_merge([
            'allowed_tags' => []
        ], $options);

        /*
         * erlaubte tags options prepare
         */
        if(!empty($options['allowed_tags']))
        {
            $options['allowed_tags'] = ',' . implode(',', $options['allowed_tags']);
        }
        else
        {
            $options['allowed_tags'] = '';
        }


        if($this->count() > 0) {

            $html = $this->safeHtml();

            /*
             * allow only a few tags
             */
            $html = strip_tags($html,'<p>,<h1>,<h2>,<h3>,<h4>,<ul>,<li>,<a>' . $options['allowed_tags']);

            /*
             * delete all attributes from tags,
             * exept a tags
             */
            $html = preg_replace("/<(?!a\s)([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $html);
            // @todo alle attribute von a tags ausser href löschen
            $html = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $html);

            /*
             * Delete all empty tags
             */
            $html = preg_replace('/<([^>]*)><\/\\1>/', '', $html);


            /*
             * Delete whitespace between tags
             */
            $html = preg_replace('/(\>)\s*(\<)/m', '$1$2', $html);

            /*
             * return trimmed html
             */
            return trim($html);
        }

        return null;
    }



    public function removeElements($css_selector) {

        $crawler = new DomCrawler($this->html());
        $crawler->filter($css_selector)->each(function (Crawler $elcrawler) {
            foreach ($elcrawler as $node) {
                $node->parentNode->removeChild($node);
            }
        });

        return $crawler;
    }

    public function removeParents($css_selector) {

        $crawler = new DomCrawler($this->html());
        $crawler->filter($css_selector)->each(function (Crawler $elcrawler) {
            $elcrawler = $elcrawler->parents();

            foreach ($elcrawler as $node) {
                $node->parentNode->removeChild($node);
            }
        });

        return $crawler;
    }

    /**
     * löscht alle DomNodes die den übergebenen Inhalt enthalten
     * es kannn ein String oder ein array mit strings übergeben werden nachdem gesucht werden soll
     *
     * @param $values
     * @return $this
     */

    public function removeElementsByNodeValue($values) {

        //@todo läuft noch nicht..
        if(is_string($values)) {
            $values = [$values];
        }



        $this->each(function (Crawler $crawler) {
            foreach ($crawler as $node) {

                $node->parentNode->removeChild($node);
            }
        });

        return $this;
        /*

        return $this;

        $newdom = new \DOMDocument();
        foreach ($this as $node) {
            foreach ($values as $value) {
                if (strpos(strtolower(trim($node->nodeValue)), strtolower($value)) === false) {
                    $newdom->appendChild($node);
                }
            }
        }
*/

    }
}