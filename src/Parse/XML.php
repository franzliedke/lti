<?php

namespace Franzl\Lti\Parse;

use DOMElement;
use Exception;

class XML
{
    public static function extractNodes($xmlString)
    {
        try {
            $extDoc = new DOMDocument();
            $extDoc->loadXML($xmlString);

            return static::domNodeToArray($extDoc->documentElement);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Convert DOM nodes to array.
     *
     * @param DOMElement $node XML element
     * @return array Array of XML document elements
     */
    protected static function domNodeToArray(DOMElement $node)
    {
        $output = '';
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0; $i < $node->childNodes->length; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = static::domNodeToArray($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($output[$t])) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } else {
                        $s = (string) $v;
                        if (strlen($s) > 0) {
                            $output = $s;
                        }
                    }
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = [];
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v)==1 && $t!='@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }
}
