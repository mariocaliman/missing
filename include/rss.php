<?php

class RSS
{
    public $title;
    public $link;
    public $description;
    public $language = "en-us";
    public $pubDate;
    public $items;
    public $tags;

    public function __construct()
    {
        $this->items = array();
        $this->tags  = array();
    }

    public function addItem($item)
    {
        $this->items[] = $item;
    }

    public function formatDate($when)
    {
        $timestamp = is_numeric($when) ? (int) $when : strtotime((string) $when);

        if ($timestamp === false) {
            return gmdate("D, d M Y H:i:s", time()) . " GMT";
        }

        return gmdate("D, d M Y H:i:s", $timestamp) . " GMT";
    }

    public function setPubDate($when)
    {
        $this->pubDate = $this->formatDate($when);
    }

    public function getPubDate()
    {
        if (empty($this->pubDate)) {
            return $this->formatDate(time());
        }

        return $this->pubDate;
    }

    public function addTag($tag, $value)
    {
        $this->tags[$tag] = $value;
    }

    public function xmlEscape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public function out()
    {
        $out  = $this->header();
        $out .= "<channel>\n";
        $out .= "<title>" . $this->xmlEscape($this->title) . "</title>\n";
        $out .= "<link>" . $this->xmlEscape($this->link) . "</link>\n";
        $out .= "<description>" . $this->xmlEscape($this->description) . "</description>\n";
        $out .= "<language>" . $this->xmlEscape($this->language) . "</language>\n";
        $out .= "<pubDate>" . $this->getPubDate() . "</pubDate>\n";

        foreach ($this->tags as $key => $val) {
            $out .= "<" . $key . ">" . $this->xmlEscape($val) . "</" . $key . ">\n";
        }

        foreach ($this->items as $item) {
            $out .= $item->out();
        }

        $out .= "</channel>\n";
        $out .= $this->footer();

        return $out;
    }

    public function serve($contentType = "application/xml")
    {
        $xml = $this->out();
        header("Content-Type: $contentType; charset=utf-8");
        echo $xml;
    }

    public function header()
    {
        $out  = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $out .= '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
        return $out;
    }

    public function footer()
    {
        return '</rss>';
    }
}

class RSSItem
{
    public $title;
    public $link;
    public $description;
    public $pubDate;
    public $guid;
    public $tags;
    public $attachment;
    public $length;
    public $mimetype;

    public function __construct()
    {
        $this->tags = array();
    }

    public function formatDate($when)
    {
        $timestamp = is_numeric($when) ? (int) $when : strtotime((string) $when);

        if ($timestamp === false) {
            return gmdate("D, d M Y H:i:s", time()) . " GMT";
        }

        return gmdate("D, d M Y H:i:s", $timestamp) . " GMT";
    }

    public function setPubDate($when)
    {
        $this->pubDate = $this->formatDate($when);
    }

    public function getPubDate()
    {
        if (empty($this->pubDate)) {
            return $this->formatDate(time());
        }

        return $this->pubDate;
    }

    public function addTag($tag, $value)
    {
        $this->tags[$tag] = $value;
    }

    public function xmlEscape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public function out()
    {
        $out  = "<item>\n";
        $out .= "<title>" . $this->xmlEscape($this->title) . "</title>\n";
        $out .= "<link>" . $this->xmlEscape($this->link) . "</link>\n";

        $description = strip_tags((string) $this->description);
        $description = preg_replace('/\s+/', ' ', trim($description));
        $description = str_replace(']]>', ']]]]><![CDATA[>', $description);
        $out .= "<description><![CDATA[" . $description . "]]></description>\n";
        $out .= "<pubDate>" . $this->getPubDate() . "</pubDate>\n";

        if ($this->attachment != "") {
            $out .= "<enclosure url='" . $this->xmlEscape($this->attachment) . "' length='" . $this->xmlEscape($this->length) . "' type='" . $this->xmlEscape($this->mimetype) . "' />\n";
        }

        if (empty($this->guid)) {
            $this->guid = $this->link;
        }

        $out .= "<guid>" . $this->xmlEscape($this->guid) . "</guid>\n";

        foreach ($this->tags as $key => $val) {
            $out .= "<" . $key . ">" . $this->xmlEscape($val) . "</" . $key . ">\n";
        }

        $out .= "</item>\n";
        return $out;
    }

    public function enclosure($url, $mimetype, $length)
    {
        $this->attachment = $url;
        $this->mimetype   = $mimetype;
        $this->length     = $length;
    }
}
?>