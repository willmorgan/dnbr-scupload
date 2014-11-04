<?php

/**
 * PodcastFeedParser
 * Extend RSSParser to extract the media URL from the podcast feed.
 * @author @willmorgan
 */

namespace SCUpload\Parser;

use DOMElement;
use FastFeed\Parser\RSSParser;
use FastFeed\Parser\ParserInterface;
use FastFeed\Item;

class PodcastFeedParser extends RSSParser implements ParserInterface
{

    protected static $namespace_prefix_map = array(
        'media' => 'http://search.yahoo.com/mrss/',
    );

    /**
     * We need to get the feedburner link as that's the MP3.
     * {@inheritdoc}
     */
	protected function getPropertiesMapping()
	{
		return array_merge(array(
			'setMedia' => 'media:content',
		), parent::getPropertiesMapping());
	}

	/**
	 * Override to handle the feedburner namespace.
	 * {@inheritdoc}
	 */
	protected function getNodeValueByTagName(DOMElement $node, $tagName)
	{
		if(strpos($tagName, ':') === false) {
			return parent::getNodeValueByTagName($node, $tagName);
        }
        list($namespacePrefix, $tagName) = explode(':', $tagName);
       	$results = $node->getElementsByTagNameNS(
            static::$namespace_prefix_map[$namespacePrefix],
            $tagName
        );
        for ($i = 0; $i < $results->length; $i++) {
            $result = $results->item($i);
            $value = $this->extractNodeValue(
                $result,
                $tagName,
                $namespacePrefix
            );
            if($value) {
                return $value;
            }
            else {
                continue;
            }
        }

        return false;
	}

    protected function extractNodeValue(DOMElement $node, $tagName, $namespace = null) {
        if($tagName == 'content' && $namespace == 'media') {
            return $node->getAttribute('url');
        }
        return $node->nodeValue;
    }

    /**
     * Override so we can chain a setMedia call with Item->setExtra
     * {@inheritdoc}
     */
    protected function setProperties(DOMElement $node, Item $item)
    {
        $propertiesMapping = $this->getPropertiesMapping();
        foreach ($propertiesMapping as $methodName => $propertyName) {
            $value = $this->getNodeValueByTagName($node, $propertyName);
            if(!$value) {
            	continue;
            }
            if (method_exists($item, $methodName)) {
                $item->$methodName($value);
            }
            else if (method_exists($this, $methodName)) {
            	$this->$methodName($value, $node, $item);
            }
        }
    }

    /**
     * This is the podcast's MP3 link.
     */
    protected function setMedia($value, DOMElement $node, Item $item)
    {
    	$item->setExtra('media', $value);
    }


}
